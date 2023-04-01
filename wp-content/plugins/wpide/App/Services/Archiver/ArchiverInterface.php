<?php

namespace WPIDE\App\Services\Archiver;

use WPIDE\App\Services\Storage\Filesystem;

interface ArchiverInterface
{
    public function createArchive(Filesystem $storage): string;

    public function setArchive(Filesystem $storage, string $uniqid);

    public function uncompress(string $source, string $destination, Filesystem $storage);

    public function addEmptyDirectory(string $path, string $destination);

    public function addDirectoryFromStorage(string $path, string $destination);

    public function addFileFromStorage(string $path, string $destination);

    public function closeArchive();

    public function storeArchive($destination, $name);
}
