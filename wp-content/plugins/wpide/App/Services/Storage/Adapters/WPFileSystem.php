<?php
namespace WPIDE\App\Services\Storage\Adapters;

use DirectoryIterator;
use FilesystemIterator;
use League\Flysystem\Exception;
use League\Flysystem\NotSupportedException;
use League\Flysystem\UnreadableFileException;
use League\Flysystem\Util;
use League\Flysystem\Adapter\AbstractAdapter;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use WP_Filesystem_Base;

class WPFileSystem extends AbstractAdapter
{
    /**
     * @var int
     */
    const SKIP_LINKS = 0001;

    /**
     * @var int
     */
    const DISALLOW_LINKS = 0002;

    /**
     * @var array
     */
    protected static $permissions = [
        'file' => [
            'public' => 0644,
            'private' => 0600,
        ],
        'dir' => [
            'public' => 0755,
            'private' => 0700,
        ],
    ];

    /**
     * @var string
     */
    protected $pathSeparator = DIRECTORY_SEPARATOR;

    /**
     * @var array
     */
    protected $permissionMap;

    /**
     * @var int
     */
    private $linkHandling;

    /**
     * @var WP_Filesystem_Base
     */
    protected $fs;

    /**
     * Constructor.
     *
     * @param string $root
     * @param WP_Filesystem_Base $wp_fs
     * @param int $linkHandling
     * @param array $permissions
     *
     * @throws LogicException
     * @throws Exception
     */
    public function __construct($root, $wp_fs, $linkHandling = self::DISALLOW_LINKS, array $permissions = [])
    {

        $this->fs  = $wp_fs;

        $root = is_link($root) ? realpath($root) : $root;
        $this->permissionMap = array_replace_recursive(static::$permissions, $permissions);
        $this->ensureDirectory($root);

        if ( ! $this->fs->is_dir($root) || ! $this->fs->is_readable($root)) {
            throw new LogicException('The root path ' . $root . ' is not readable.');
        }

        $this->setPathPrefix($root);
        $this->linkHandling = $linkHandling;
    }

    /**
     * Attempts to use the correct path for the FS method being used
     *
     * @param string $abs_path
     *
     * @return string
     */
    protected function getRelativePath( $abs_path ): string
    {
        return str_replace( ABSPATH, $this->fs->abspath(), $abs_path );
    }

    /**
     * Ensure the root directory exists.
     *
     * @param string $root root directory path
     *
     * @return void
     *
     * @throws Exception in case the root directory can not be created
     */
    protected function ensureDirectory($root)
    {
        $root = $this->getRelativePath( $root );

        if ( ! $this->fs->is_dir($root)) {
            $umask = umask(0);

            if ( ! wp_mkdir_p($root) ) {
                $mkdirError = error_get_last();
            }

            umask($umask);
            clearstatcache(false, $root);

            if ( ! $this->fs->is_dir($root)) {
                $errorMessage = isset($mkdirError['message']) ? $mkdirError['message'] : '';
                throw new Exception(sprintf('Impossible to create the root directory "%s". %s', $root, $errorMessage));
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function has($path)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);

        return $this->fs->exists($location);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function write($path, $contents, $config)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));

        if (($size = $this->fs->put_contents($location, $contents)) === false) {
            return false;
        }

        $type = 'file';
        $result = compact('contents', 'type', 'size', 'path');

        if ($visibility = $config->get('visibility')) {
            $result['visibility'] = $visibility;
            $this->setVisibility($path, $visibility);
        }

        return $result;
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function writeStream($path, $resource, $config)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));
        $stream = @fopen($location, 'w+b');

        if ( ! $stream || stream_copy_to_stream($resource, $stream) === false || ! fclose($stream)) {
            return false;
        }

        $type = 'file';
        $result = compact('type', 'path');

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function readStream($path)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $stream = @fopen($location, 'rb');

        return ['type' => 'file', 'path' => $path, 'stream' => $stream];
    }

    /**
     * @inheritdoc
     */
    public function updateStream($path, $resource, $config)
    {
        $path = $this->getRelativePath( $path );
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * @inheritdoc
     */
    public function update($path, $contents, $config)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);

        $size = $this->fs->put_contents($location, $contents);

        if ($size === false) {
            return false;
        }

        $type = 'file';

        $result = compact('type', 'path', 'size', 'contents');

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
            $result['visibility'] = $visibility;
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function read($path)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $contents = $this->fs->get_contents($location);

        if ($contents === false) {
            return false;
        }

        return ['type' => 'file', 'path' => $path, 'contents' => $contents];
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function rename($path, $newpath)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        $parentDirectory = $this->applyPathPrefix(Util::dirname($newpath));
        $this->ensureDirectory($parentDirectory);

        return $this->fs->move($location, $destination);
    }

    /**
     * @inheritdoc
     * @throws Exception
     */
    public function copy($path, $newpath): bool
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $destination = $this->applyPathPrefix($newpath);
        $this->ensureDirectory(dirname($destination));

        return $this->fs->copy($location, $destination);
    }

    /**
     * @inheritdoc
     */
    public function delete($path): bool
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);

        return $this->fs->delete($location);
    }

    /**
     * @inheritdoc
     */
    public function listContents($directory = '', $recursive = false): array
    {

        $result = [];
        $directory = $this->getRelativePath( $directory );
        $location = $this->applyPathPrefix($directory);

        if ( ! $this->fs->is_dir($location)) {
            return [];
        }

        $iterator = $recursive ? $this->getRecursiveDirectoryIterator($location) : $this->getDirectoryIterator($location);

        foreach ($iterator as $file) {
            $path = $this->getFilePath($file);

            if (preg_match('#(^|/|\\\\)\.{1,2}$#', $path)) {
                continue;
            }

            $result[] = $this->normalizeFileInfo($file);
        }

        unset($iterator);

        return array_filter($result);
    }

    /**
     * @inheritdoc
     */
    public function getMetadata($path)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        clearstatcache(false, $location);
        $info = new SplFileInfo($location);

        return $this->normalizeFileInfo($info);
    }

    /**
     * @inheritdoc
     */
    public function getSize($path)
    {
        $path = $this->getRelativePath( $path );
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);

        if(extension_loaded('fileinfo')) {
            $mimeDetector = new FinfoMimeTypeDetector();
        }else{
            $mimeDetector = new ExtensionMimeTypeDetector();
        }
        $mimetype = $mimeDetector->detectMimeTypeFromPath($location);

        return ['path' => $path, 'type' => 'file', 'mimetype' => $mimetype];
    }

    /**
     * @inheritdoc
     */
    public function getTimestamp($path)
    {
        $path = $this->getRelativePath( $path );
        return $this->getMetadata($path);
    }

    /**
     * @inheritdoc
     */
    public function getVisibility($path)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        clearstatcache(false, $location);
        $permissions = octdec(substr(sprintf('%o', fileperms($location)), -4));
        $type = $this->fs->is_dir($location) ? 'dir' : 'file';

        foreach ($this->permissionMap[$type] as $visibility => $visibilityPermissions) {
            if ($visibilityPermissions == $permissions) {
                return compact('path', 'visibility');
            }
        }

        $visibility = substr(sprintf('%o', fileperms($location)), -4);

        return compact('path', 'visibility');
    }

    /**
     * @inheritdoc
     */
    public function setVisibility($path, $visibility)
    {
        $path = $this->getRelativePath( $path );
        $location = $this->applyPathPrefix($path);
        $type = $this->fs->is_dir($location) ? 'dir' : 'file';
        $success = $this->fs->chmod($location, $this->permissionMap[$type][$visibility]);

        if ($success === false) {
            return false;
        }

        return compact('path', 'visibility');
    }

    /**
     * @inheritdoc
     */
    public function createDir($dirname, $config)
    {

        $dirname = $this->getRelativePath( $dirname );
        $location = $this->applyPathPrefix($dirname);
        $umask = umask(0);
        $return = ['path' => $dirname, 'type' => 'dir'];

        if ( ! is_dir($location)) {
            if (!wp_mkdir_p($location)) {
                error_log('cannot create dir '.$location);
                $return = false;
            }
        }

        umask($umask);

        return $return;
    }

    /**
     * @inheritdoc
     */
    public function deleteDir($dirname): bool
    {
        $dirname = $this->getRelativePath( $dirname );
        $location = $this->applyPathPrefix($dirname);

        if ( ! $this->fs->is_dir($location)) {
            return false;
        }

        $contents = $this->getRecursiveDirectoryIterator($location, RecursiveIteratorIterator::CHILD_FIRST);

        /** @var SplFileInfo $file */
        foreach ($contents as $file) {
            $this->guardAgainstUnreadableFileInfo($file);
            $this->deleteFileInfoObject($file);
        }

        unset($contents);

        return $this->fs->rmdir($location);
    }

    /**
     * @param SplFileInfo $file
     */
    protected function deleteFileInfoObject(SplFileInfo $file)
    {
        switch ($file->getType()) {
            case 'dir':
                $this->fs->rmdir($file->getRealPath());
                break;
            case 'link':
                $this->fs->delete($file->getPathname());
                break;
            default:
                $this->fs->delete($file->getRealPath());
        }
    }

    /**
     * Normalize the file info.
     *
     * @param SplFileInfo $file
     *
     * @return array|void
     *
     * @throws NotSupportedException
     */
    protected function normalizeFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isLink()) {
            return $this->mapFileInfo($file);
        }

        if ($this->linkHandling & self::DISALLOW_LINKS) {
            throw NotSupportedException::forLink($file);
        }
    }

    /**
     * Get the normalized path from a SplFileInfo object.
     *
     * @param SplFileInfo $file
     *
     * @return string
     */
    protected function getFilePath(SplFileInfo $file): string
    {
        $location = $file->getPathname();
        $path = $this->removePathPrefix($location);

        return trim(str_replace('\\', '/', $path), '/');
    }

    /**
     * @param string $path
     * @param int    $mode
     *
     * @return RecursiveIteratorIterator
     */
    protected function getRecursiveDirectoryIterator($path, $mode = RecursiveIteratorIterator::SELF_FIRST)
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            $mode
        );
    }

    /**
     * @param string $path
     *
     * @return DirectoryIterator
     */
    protected function getDirectoryIterator($path): DirectoryIterator
    {
        $path = $this->getRelativePath( $path );
        return new DirectoryIterator($path);
    }

    /**
     * @param SplFileInfo $file
     *
     * @return array
     */
    protected function mapFileInfo(SplFileInfo $file): array
    {
        $normalized = [
            'type' => $file->getType(),
            'path' => $this->getFilePath($file),
        ];

        $normalized['timestamp'] = $file->getMTime();

        if ($normalized['type'] === 'file') {
            $normalized['size'] = $file->getSize();
        }

        return $normalized;
    }

    /**
     * @param SplFileInfo $file
     *
     * @throws UnreadableFileException
     */
    protected function guardAgainstUnreadableFileInfo(SplFileInfo $file)
    {
        if ( ! $file->isReadable()) {
            throw UnreadableFileException::forFileInfo($file);
        }
    }
}
