<?php

namespace WPIDE\App\Services\Storage;

use Exception;
use League\Flysystem\FileNotFoundException;
use WPIDE\App\Services\Service;
use League\Flysystem\Filesystem as Flysystem;

class Filesystem implements Service
{
    protected $root;
    protected $separator;
    protected $excluded_dirs;
    protected $excluded_files;
    protected $path_prefix;

    /**
     * @var Flysystem
     */
    protected $storage;

    public function init(array $config = [])
    {
        $this->separator = $config['separator'] ?? '/';
        $this->root = $config['root'] ?? $this->separator;
        $this->excluded_dirs = $config['excluded_dirs'] ?? [];
        $this->excluded_files = $config['excluded_files'] ?? [];

        $this->path_prefix = $this->separator;

        $adapter = $config['adapter'];

        $config = $config['config'] ?? [];

        $this->storage = new Flysystem($adapter(), $config);
    }

    public function createDir(string $path, string $name)
    {
        $destination = $this->joinPaths($this->applyPathPrefix($path), $name);

        while (! empty($this->listContents($destination, true))) {
            $destination = $this->upcountName($destination);
        }

        return $this->storage->createDir($destination);
    }

    public function createFile(string $path, string $name)
    {
        $destination = $this->joinPaths($this->applyPathPrefix($path), $name);

        while ($this->storage->has($destination)) {
            $destination = $this->upcountName($destination);
        }

        $this->storage->put($destination, '');
    }

    public function fileExists(string $path)
    {
        $path = $this->applyPathPrefix($path);

        return $this->storage->has($path);
    }

    public function isDir(string $path): bool
    {
        $path = $this->applyPathPrefix($path);

        try {
            return $this->storage->getSize($path) === false;
        }catch (\Exception $error) {
            return false;
        }
    }

    public function copyFile(string $source, string $destination)
    {
        $source = $this->applyPathPrefix($source);
        $destination = $this->joinPaths($this->applyPathPrefix($destination), $this->getBaseName($source));

        while ($this->storage->has($destination)) {
            $destination = $this->upcountName($destination);
        }

        return $this->storage->copy($source, $destination);
    }

    public function copyDir(string $source, string $destination)
    {
        $source = $this->applyPathPrefix($this->addSeparators($source));
        $destination = $this->applyPathPrefix($this->addSeparators($destination));
        $source_dir = $this->getBaseName($source);
        $real_destination = $this->joinPaths($destination, $source_dir);

        while (! empty($this->listContents($real_destination, true))) {
            $real_destination = $this->upcountName($real_destination);
        }

        $contents = $this->listContents($source, true);

        if (empty($contents)) {
            $this->storage->createDir($real_destination);
        }

        foreach ($contents as $file) {
            $source_path = $this->separator.ltrim($file['path'], $this->separator);
            $path = substr($source_path, strlen($source), strlen($source_path));

            if ($file['type'] == 'dir') {

                $this->storage->createDir($this->joinPaths($real_destination, $path));

                continue;
            }

            if ($file['type'] == 'file') {
                $this->storage->copy($file['path'], $this->joinPaths($real_destination, $path));
            }
        }
    }

    public function deleteDir(string $path)
    {
        return $this->storage->deleteDir($this->applyPathPrefix($path));
    }

    public function deleteFile(string $path)
    {
        return $this->storage->delete($this->applyPathPrefix($path));
    }

    public function readStream(string $path): array
    {
        if ($this->isDir($path)) {
            throw new Exception('Cannot stream directory');
        }

        $path = $this->applyPathPrefix($path);

        return [
            'filename' => $this->getBaseName($path),
            'stream' => $this->storage->readStream($path),
            'filesize' => $this->storage->getSize($path),
        ];
    }

    public function read(string $path): array
    {
        if ($this->isDir($path)) {
            throw new Exception('Cannot read directory');
        }

        $path = $this->applyPathPrefix($path);

        return [
            'filename' => $this->getBaseName($path),
            'contents' => $this->storage->read($path),
            'filesize' => $this->storage->getSize($path),
        ];
    }

    public function move(string $from, string $to): bool
    {
        $from = $this->applyPathPrefix($from);
        $to = $this->applyPathPrefix($to);

        while ($this->storage->has($to)) {
            $to = $this->upcountName($to);
        }

        return $this->storage->rename($from, $to);
    }

    public function rename(string $destination, string $from, string $to): bool
    {
        $from = $this->joinPaths($this->applyPathPrefix($destination), $from);
        $to = $this->joinPaths($this->applyPathPrefix($destination), $to);

        while ($this->storage->has($to)) {
            $to = $this->upcountName($to);
        }

        return $this->storage->rename($from, $to);
    }

    public function store(string $path, string $name, $content, bool $overwrite = false): bool
    {
        $destination = $this->joinPaths($this->applyPathPrefix($path), $name);

        while ($this->storage->has($destination)) {
            if ($overwrite) {
                $this->storage->delete($destination);
            } else {
                $destination = $this->upcountName($destination);
            }
        }

        return $this->storage->put($destination, $content);
    }

    /**
     * @throws FileNotFoundException
     */
    public function storeStream(string $path, string $name, $resource, bool $overwrite = false): bool
    {
        $destination = $this->joinPaths($this->applyPathPrefix($path), $name);

        while ($this->storage->has($destination)) {
            if ($overwrite) {
                $this->storage->delete($destination);
            } else {
                $destination = $this->upcountName($destination);
            }
        }

        return $this->storage->putStream($destination, $resource);
    }

    /**
     * @throws FileNotFoundException
     */
    public function storeStreamFromContent(string $path, string $name, $content, bool $overwrite = false): bool
    {
        $stream = tmpfile();
        fwrite($stream, $content);
        rewind($stream);

        $res = $this->storeStream($path, $name, $stream, $overwrite);

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $res;
    }

    public function setPathPrefix(string $path_prefix)
    {
        $this->path_prefix = $this->addSeparators($path_prefix);
    }

    public function getSeparator()
    {
        return $this->separator;
    }

    public function getPathPrefix(): string
    {
        return $this->path_prefix;
    }

    public function listContents($path, $recursive = false, $filter = null): array
    {

        $results = $this->storage->listContents($path, $recursive);

        if(!empty($this->excluded_dirs) || !empty($this->excluded_files)) {

            $results = array_filter($results, function ($item) {

                $item_path = realpath($this->root . $this->stripPathPrefix($item['path']));

                if ($item['type'] === 'dir' && !empty($this->excluded_dirs)) {

                    $item_path .= $this->separator;

                    return !$this->isPathExcluded($item_path, $this->excluded_dirs);

                }else if ($item['type'] === 'file' && !empty($this->excluded_files)) {

                    return !$this->isPathExcluded($item_path, $this->excluded_files);
                }

            });
        }

        if(!empty($filter)) {
            if($filter === 'image') {
                $results = array_filter($results, function ($item) {
                    if($item['type'] === 'file' && !in_array($item['extension'], ['jpg', 'jpeg', 'gif', 'png'])) {
                        return false;
                    }
                    return true;
                });
            }
        }

        return $results;
    }

    /**
     * @throws Exception
     */
    public function getDirectoryCollection(string $path, bool $recursive = false, $filter = null): DirectoryCollection
    {
        $collection = new DirectoryCollection($path);

        foreach ($this->listContents($this->applyPathPrefix($path), $recursive, $filter) as $entry) {

            // By default, only 'path' and 'type' is present

            $is_dir =  $entry['type'] === 'dir';
            $name = $this->getBaseName($entry['path']);
            $user_path = $this->stripPathPrefix($entry['path']);
            $size = isset($entry['size']) ? $entry['size'] : 0;
            $timestamp = isset($entry['timestamp']) ? $entry['timestamp'] : 0;

            $mime = $this->getMimetype($entry['path']);
            $dir_info = $is_dir ? $this->dirInfo($entry['path'], $filter) : [];

            $collection->addFile($entry['type'], $dir_info, $user_path, $path, $mime, $name, $size, $timestamp);
        }

        if (empty($filter) && ! $recursive && $this->addSeparators($path) !== $this->separator) {
            $collection->addFile('back', [], $this->getParent($path), $path, '', '..', 0, 0);
        }

        return $collection;
    }

    protected function isPathExcluded($path, $excluded): bool
    {
        $path = wp_normalize_path($path);
        
        foreach ($excluded as $exclude) {

            $force_include = str_contains($exclude, "!");
            $exclude = str_replace("!", "", $exclude);

            $pattern = '/'.str_replace("\*", "[^\/]+", preg_quote($exclude, '/')).'/';
            if(str_contains($path, $exclude) || preg_match($pattern, $path, $matches) === 1) {

                if($force_include) {
                    return false;
                }

                return true;
            }
        }

        return false;
    }

    public function dirInfo($path, $filter = null): array
    {

        $path = $this->applyPathPrefix($path);
        $content = $this->listContents($path, false, $filter);

        $has_dirs = false;
        $has_files = false;

        foreach($content as $entry) {

            if(!$has_dirs && $entry['type'] === 'dir') {
                $has_dirs = true;
            }

            if(!$has_files && $entry['type'] === 'file') {
                $has_files = true;
            }

            if($has_dirs && $has_files) {
                break;
            }
        }

        return [
            'is_empty' => !count($content),
            'has_dirs' => $has_dirs,
            'has_files' => $has_files
        ];
    }

    /**
     * @throws Exception
     */
    public function getDirSize($path, $filter = null): int
    {

        $size = 0;

        foreach ($this->getDirectoryCollection($path, false, $filter = null)->all() as $entry) {

            if($entry['type'] === 'back') {
                continue;
            }

            $size += $entry['type'] === 'file' && isset($entry['size']) ? $entry['size'] : $this->getDirSize($entry['path'], $filter);
        }

        return $size;
    }

    protected function upcountCallback($matches): string
    {
        $index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        $ext = isset($matches[2]) ? $matches[2] : '';

        return ' ('.$index.')'.$ext;
    }

    public function upcountName($name)
    {
        return preg_replace_callback(
            '/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/',
            [$this, 'upcountCallback'],
            $name,
            1
        );
    }

    private function applyPathPrefix(string $path): string
    {
        if ($path == '..'
            || strpos($path, '..'.$this->separator) !== false
            || strpos($path, $this->separator.'..') !== false
        ) {
            $path = $this->separator;
        }
        return $this->joinPaths($this->getPathPrefix(), $path);
    }

    private function stripPathPrefix(string $path): string
    {
        $path = $this->separator.ltrim($path, $this->separator);

        if (substr($path, 0, strlen($this->getPathPrefix())) == $this->getPathPrefix()) {
            $path = $this->separator.substr($path, strlen($this->getPathPrefix()));
        }

        return $path;
    }

    private function addSeparators(string $dir): string
    {
        if (! $dir || $dir == $this->separator || ! trim($dir, $this->separator)) {
            return $this->separator;
        }

        return $this->separator.trim($dir, $this->separator).$this->separator;
    }

    private function joinPaths(string $path1, string $path2): string
    {
        if (! $path2 || ! trim($path2, $this->separator)) {
            return $this->addSeparators($path1);
        }

        return $this->addSeparators($path1).ltrim($path2, $this->separator);
    }

    private function getParent(string $dir): string
    {
        if (! $dir || $dir == $this->separator || ! trim($dir, $this->separator)) {
            return $this->separator;
        }

        $tmp = explode($this->separator, trim($dir, $this->separator));
        array_pop($tmp);

        return $this->separator.trim(implode($this->separator, $tmp), $this->separator);
    }

    private function getBaseName(string $path): string
    {
        if (! $path || $path == $this->separator || ! trim($path, $this->separator)) {
            return $this->separator;
        }

        $tmp = explode($this->separator, trim($path, $this->separator));

        return  (string) array_pop($tmp);
    }

    private function getMimetype(string $path): string
    {
        if (! $path || $path == $this->separator || ! trim($path, $this->separator)) {
            return $this->separator;
        }

        $mime = $this->storage->getMimetype($path);
        return !empty($mime) && is_string($mime) ? explode('/', $mime)[0] : false;
    }
}
