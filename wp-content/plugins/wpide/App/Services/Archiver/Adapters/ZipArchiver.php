<?php

namespace WPIDE\App\Services\Archiver\Adapters;

use WPIDE\App\Services\Archiver\ArchiverInterface;
use WPIDE\App\Services\Service;
use WPIDE\App\Services\Storage\Filesystem as Storage;
use WPIDE\App\Services\Tmpfs\TmpfsInterface;
use League\Flysystem\Config as Flyconfig;
use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;

class ZipArchiver implements Service, ArchiverInterface
{
    /* @var $archive Flysystem */
    protected $archive;

    /* @var $storage Storage */
    protected $storage;

    /* @var $tmpfs TmpfsInterface */
    protected $tmpfs;

    protected $uniqid;

    protected $tmp_files = [];

    public function __construct(TmpfsInterface $tmpfs)
    {
        $this->tmpfs = $tmpfs;
    }

    public function init(array $config = [])
    {
    }

    public function createArchive(Storage $storage): string
    {
        $this->uniqid = uniqid();

        $this->archive = new Flysystem(
            new ZipAdapter($this->tmpfs->getFileLocation($this->uniqid))
        );

        $this->storage = $storage;

        return $this->uniqid;
    }

    public function setArchive(Storage $storage, string $uniqid)
    {
        $this->uniqid = $uniqid;

        $this->archive = new Flysystem(
            new ZipAdapter($this->tmpfs->getFileLocation($this->uniqid))
        );

        $this->storage = $storage;
    }

    public function addEmptyDirectory(string $path, string $destination){

        $path = str_replace($destination, "/", $path);
        $this->archive->createDir($path);
    }

    /**
     * @throws \Exception
     */
    public function addDirectoryFromStorage(string $path, string $destination)
    {
        $content = $this->storage->getDirectoryCollection($path, true);
        $path = str_replace($destination, "/", $path);
        $this->archive->createDir($path);

        foreach ($content->all() as $item) {
            if ($item['type'] == 'dir') {
                $this->archive->createDir($item['path']);
            }
            if ($item['type'] == 'file') {
                $this->addFileFromStorage($item['path']);
            }
        }
    }

    public function addFileFromStorage(string $path, string $destination)
    {

        if(!$this->archive->has($path)){

            $file_uniqid = uniqid();

            $file = $this->storage->readStream($path);

            $this->tmpfs->write($file_uniqid, $file['stream']);

            $path = str_replace($destination, "/", $path);

            $this->archive->write($path, $this->tmpfs->getFileLocation($file_uniqid));

            $this->tmp_files[] = $file_uniqid;
        }
    }

    public function uncompress(string $source, string $destination, Storage $storage)
    {
        $name = uniqid().'.zip';

        $remote_archive = $storage->readStream($source);
        $this->tmpfs->write($name, $remote_archive['stream']);

        $archive = new Flysystem(
            new ZipAdapter($this->tmpfs->getFileLocation($name))
        );

        $contents = $archive->listContents('/', true);

        $firstItem = current($contents);
        $existingDirBackupPath = $firstItem['path'] . '--tmp--';

        if($storage->isDir($destination.'/'.$firstItem['path'])) {
            $storage->rename($destination, $firstItem['path'], $existingDirBackupPath);
        }

        foreach ($contents as $item) {
            $stream = null;
            if ($item['type'] == 'dir') {
                $storage->createDir($destination, $item['path']);
            }
            if ($item['type'] == 'file') {
                $stream = $archive->readStream($item['path']);
                $storage->storeStream($destination.'/'.$item['dirname'], $item['basename'], $stream);
            }
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        if($storage->isDir($destination.'/'.$existingDirBackupPath)) {
            if ($storage->isDir($destination.'/'.$firstItem['path'])) {
                $storage->deleteDir($destination.'/'.$existingDirBackupPath);
            } else {
                $storage->rename($destination, $existingDirBackupPath, $firstItem['path']);
            }
        }

        $this->tmpfs->remove($name);
    }

    public function closeArchive()
    {
        $this->archive->getAdapter()->getArchive()->close();

        foreach ($this->tmp_files as $file) {
            $this->tmpfs->remove($file);
        }
    }

    public function storeArchive($destination, $name)
    {
        $this->closeArchive();

        $file = $this->tmpfs->readStream($this->uniqid);

        $this->storage->storeStream($destination, $name, $file['stream']);
        if (is_resource($file['stream'])) {
            fclose($file['stream']);
        }

        $this->tmpfs->remove($this->uniqid);
    }
}

class ZipAdapter extends ZipArchiveAdapter
{
    public function write($path, $contents, Flyconfig $config)
    {
        $location = $this->applyPathPrefix($path);

        // using addFile instead of addFromString
        // is more memory efficient
        $this->archive->addFile($contents, $location);

        return compact('path', 'contents');
    }
}
