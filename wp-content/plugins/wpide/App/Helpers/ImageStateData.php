<?php
namespace WPIDE\App\Helpers;

use Exception;
use WPIDE\App\Services\Storage\Filesystem;
use WPIDE\App\Services\Storage\LocalFileSystem;

use const WPIDE\Constants\IMAGE_DATA_DIR;

class ImageStateData
{

    protected static $storage = null;

    public static function init() {

        EmptyDir::create(IMAGE_DATA_DIR);
    }

    public static function getStateFile($id): string
    {
        return $id.'.json';
    }

    /**
     * @throws Exception
     */
    public static function save($item, $state): bool
    {

        self::createStateFile($item->id, $state);

        $dataFile = $item->id.'.'.$item->ext;

        if(!self::imageFileExists($dataFile)) {

            $rootStorage = LocalFileSystem::load();
            $stream = $rootStorage->readStream($item->path);
            if (!self::storage()->storeStream('/', $dataFile, $stream['stream'], true)) {
                return false;
            }
        }

        return true;
    }

    public static function createStateFile($id, $state) {

        $stateFile = self::getStateFile($id);

        if(!self::storage()->storeStreamFromContent('/', $stateFile, $state, true)) {
            throw new Exception('Cannot create image state file!');
        }
    }

    public static function stateFileExists($id): bool
    {
        $stateFile = self::getStateFile($id);
        return self::storage()->fileExists($stateFile);
    }

    public static function imageFileExists($name): bool
    {
        return self::storage()->fileExists($name);
    }

    /**
     * @throws Exception
     */
    public static function getState($id): ?array
    {

        if(!self::stateFileExists($id)) {
            throw new Exception('Image state file not found');
        }

        $stateFile = self::getStateFile($id);

        return self::storage()->read($stateFile);
    }

    /**
     * @throws Exception
     */
    public static function deleteState($id): bool
    {

        if(!self::stateFileExists($id)) {
            throw new Exception('Image state file not found');
        }

        $stateFile = self::getStateFile($id);

        return self::storage()->deleteFile($stateFile);
    }

    /**
     * @throws Exception
     */
    public static function getImage($name): ?array
    {
        return self::storage()->readStream($name);
    }

    protected function getEmptyState(): string
    {

        return '{
          "canvas": {
            "version": "4.6.0",
            "objects": []
          },
          "editor": {
            "frame": null,
            "zoom": 1,
            "activeObjectId": null
          },
          "canvasWidth": 3000,
          "canvasHeight": 2500
        }';
    }

    protected static function storage(): Filesystem
    {
        if(!self::$storage) {
            self::$storage = LocalFileSystem::load(IMAGE_DATA_DIR);
        }

        return self::$storage;
    }

}
