<?php

namespace WPIDE\App\Controllers\FileManager;

use Exception;
use PhpParser\Error;
use WPIDE\App\App;
use WPIDE\App\Config\Config;
use WPIDE\App\Helpers\FileBackup;
use WPIDE\App\Helpers\ImageStateData;
use WPIDE\App\Helpers\PhpValidator;
use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;
use WPIDE\App\Services\Archiver\ArchiverInterface;
use WPIDE\App\Services\Auth\AuthInterface;
use WPIDE\App\Services\Transient\Transient;
use WPIDE\App\Services\Storage\Filesystem;
use WPIDE\App\Services\Storage\LocalFileSystem;
use const WPIDE\Constants\WP_PATH;


/**
 *
 */
class FileController
{
    /**
     *
     */
    const SESSION_CWD = 'current_path';

    /**
     * @var Transient
     */
    protected $transient;

    /**
     * @var AuthInterface
     */
    protected $auth;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Filesystem
     */
    protected $storage;

    /**
     * @var
     */
    protected $separator;

    /**
     * @param Config $config
     * @param Transient $transient
     * @param AuthInterface $auth
     * @param Filesystem $storage
     */
    public function __construct(Config $config, Transient $transient, AuthInterface $auth, Filesystem $storage)
    {
        $this->transient = $transient;
        $this->config = $config;
        $this->auth = $auth;

        $user = $this->auth->user() ?: $this->auth->getGuest();

        $this->storage = $storage;
        $this->storage->setPathPrefix($user->getHomeDir());

        $this->separator = $this->storage->getSeparator();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function changeDirectory(Request $request, Response $response)
    {
        $path = $request->input('to', $this->separator);

        $this->transient->set(self::SESSION_CWD, $path);

        return $response->json($this->storage->getDirectoryCollection($path));
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function getDirectory(Request $request, Response $response)
    {
        $path = $request->input('dir', $this->transient->get(self::SESSION_CWD, $this->separator));
        $root = $request->input('root', false);
        $filter = $request->enumInput('filter', ['image']);

        if($root) {
            $storage = LocalFileSystem::load(WP_PATH);
        }else{
            $storage = $this->storage;
        }

        $content = $storage->getDirectoryCollection($path, false, $filter);

        return $response->json($content);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function getDirectorySize(Request $request, Response $response)
    {
        $path = $request->input('dir', $this->transient->get(self::SESSION_CWD, $this->separator));

        $size = $this->storage->getDirSize($path);

        return $response->json($size);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function createNew(Request $request, Response $response)
    {
        $type = $request->input('type', 'file');
        $name = $request->input('name');
        $path = $request->input('destination', $this->transient->get(self::SESSION_CWD, $this->separator));

        if(empty($name)) {
            return $response->json(sprintf(__('%s name cannot be empty!', 'wpide'), ucfirst($type)), 422);
        }

        if ($type == 'dir') {
            $this->storage->createDir($path, $name);
        }
        if ($type == 'file') {
            $this->storage->createFile($path, $name);
        }

        return $response->json(true);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function copyItems(Request $request, Response $response)
    {
        $items = $request->input('items', []);
        $destination = $request->input('destination', $this->separator);

        foreach ($items as $item) {
            if ($item->type == 'dir') {
                $this->storage->copyDir($item->path, $destination);
            }
            if ($item->type == 'file') {
                $this->storage->copyFile($item->path, $destination);
            }
        }

        return $response->json(true);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function moveItems(Request $request, Response $response)
    {
        $items = $request->input('items', []);
        $destination = $request->input('destination', $this->separator);

        foreach ($items as $item) {
            $full_destination = trim($destination, $this->separator)
                . $this->separator
                . ltrim($item->name, $this->separator);
            $this->storage->move($item->path, $full_destination);
        }

        return $response->json(true);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param ArchiverInterface $archiver
     * @return void
     */
    public function zipItems(Request $request, Response $response, ArchiverInterface $archiver)
    {

        return App::instance()->call([DownloadController::class, 'batchDownloadChunks'], [$request, $response, $archiver]);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param ArchiverInterface $archiver
     * @return void
     */
    public function unzipItem(Request $request, Response $response, ArchiverInterface $archiver)
    {
        $source = $request->input('item');
        $destination = $request->input('destination', $this->separator);
        $archiver->uncompress($source, $destination, $this->storage);

        return $response->json(true);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function renameItem(Request $request, Response $response)
    {
        $destination = $request->input('destination', $this->separator);
        $from = $request->input('from');
        $to = $request->input('to');

        $this->storage->rename($destination, $from, $to);

        return $response->json(true);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function deleteItems(Request $request, Response $response)
    {
        $items = $request->input('items', []);

        foreach ($items as $item) {
            if ($item->type == 'dir' && $this->storage->fileExists($item->path)) {
                $this->storage->deleteDir($item->path);
            }
            if ($item->type == 'file' && $this->storage->fileExists($item->path)) {
                $this->storage->deleteFile($item->path);
            }
        }

        return $response->json(true);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return void
     * @throws Exception
     */
    public function saveContent(Request $request, Response $response)
    {

        $item = $request->input('item');
        $content = $request->input('content');

        $dir = !empty($item->dir) ? $item->dir : $this->transient->get(self::SESSION_CWD, $this->separator);

        $isImage = false;

        // Check if data uri encoded image
        if (substr($content, 0, 11) === 'data:image/') {

            $content = base64_decode(substr($content, strpos($content, ',') + 1));
            $imageState = $request->input('state');
            $isImage = true;
        }

        if ($item->ext === 'php') {
            try {
                PhpValidator::validate($content);
            } catch (Error | Exception $error) {

                $message = "Parse error: {$error->getMessage()}\n";
                return $response->json($message, 422);
            }
        }

        // Backup files before saving and keep copy of modified files
        // For PHP files, in case of a fatal error after saving a file, our custom error handler drop-in will then take care of restoring files if a backup file is found.
        if(!FileBackup::backup($item, $content)) {

            return $response->json(__('Failed file backup!', 'wpide'), 422);
        }

        if($isImage && !empty($imageState)){

            if(!ImageStateData::save($item, $imageState)) {

                return $response->json(__('Failed saving image state!', 'wpide'), 422);
            }
        }

        if(!$this->storage->storeStreamFromContent($dir, $item->name, $content, true)) {

            return $response->json(__('Failed saving file!', 'wpide'), 422);
        }

        return $response->json([
            'success' => true
        ]);
    }
}
