<?php

namespace WPIDE\App\Controllers\FileManager;

use WPIDE\App\Config\Config;
use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;
use WPIDE\App\Kernel\StreamedResponse;
use WPIDE\App\Services\Archiver\ArchiverInterface;
use WPIDE\App\Services\Auth\AuthInterface;
use WPIDE\App\Services\Storage\Filesystem;
use WPIDE\App\Services\Tmpfs\TmpfsInterface;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\Mime\MimeTypes;

/**
 *
 */
class DownloadController
{
    /**
     * @var AuthInterface
     */
    protected $auth;

    /**
     * @var Mixed
     */
    protected $user;

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
     * @param AuthInterface $auth
     * @param Filesystem $storage
     */
    public function __construct(Config $config, AuthInterface $auth, Filesystem $storage)
    {
        $this->config = $config;
        $this->auth = $auth;

        $this->user = $this->auth->user() ?: $this->auth->getGuest();

        $this->storage = $storage;
        $this->storage->setPathPrefix($this->user->getHomeDir());

        $this->separator = $this->storage->getSeparator();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param StreamedResponse $streamedResponse
     * @return void
     */
    public function download(Request $request, Response $response, StreamedResponse $streamedResponse)
    {

        $path = (string) base64_decode($request->input('path'));

        if(!$this->storage->fileExists($path)) {
            return $response->json(__('Requested file does not exist', 'wpide'), 404);
        }

        try {
            $file = $this->storage->readStream($path);
        } catch (\Exception $e) {
            return $response->redirect('/');
        }

        $this->downloadFile($file, $request, $response, $streamedResponse);
    }

    /**
     * @param $file array
     * @param Request $request
     * @param StreamedResponse $streamedResponse
     * @return void
     */
    public function downloadFile(array $file, Request $request, Response $response, StreamedResponse $streamedResponse)
    {

        if($file['stream'] === false) {
            return $response->json('Cannot read file, please check file permissions!', 422);
        }

        $streamedResponse->setCallback(function () use ($file) {
            // @codeCoverageIgnoreStart
            set_time_limit(0);

            if ($file['stream']) {
                while (! feof($file['stream'])) {
                    echo fread($file['stream'], 1024 * 8);
                    @ob_flush();
                    @flush();
                }
                fclose($file['stream']);
            }
            // @codeCoverageIgnoreEnd
        });

        $disposition = HeaderUtils::DISPOSITION_ATTACHMENT;
        $extension = pathinfo($file['filename'], PATHINFO_EXTENSION);
        $mimes = (new MimeTypes())->getMimeTypes($extension);
        $contentType = !empty($mimes) ? $mimes[0] : 'application/octet-stream';

        $download_inline = ['pdf'];
        if (in_array($extension, $download_inline) || in_array('*', $download_inline)) {
            $disposition = HeaderUtils::DISPOSITION_INLINE;
        }

        $contentDisposition = HeaderUtils::makeDisposition($disposition, $file['filename'], 'file');

        $streamedResponse->headers->set(
            'Content-Disposition',
            $contentDisposition
        );
        $streamedResponse->headers->set(
            'Content-Type',
            $contentType
        );
        $streamedResponse->headers->set(
            'Content-Transfer-Encoding',
            'binary'
        );
        if (isset($file['filesize'])) {
            $streamedResponse->headers->set(
                'Content-Length',
                $file['filesize']
            );
        }
        // @codeCoverageIgnoreStart
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $streamedResponse->headers->set(
                'Access-Control-Allow-Origin',
                $request->headers->get('Origin')
            );
            $streamedResponse->headers->set(
                'Access-Control-Allow-Credentials',
                'true'
            );
        }
        // @codeCoverageIgnoreEnd

        $streamedResponse->send();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param ArchiverInterface $archiver
     * @return void
     */
    public function batchDownloadChunks(Request $request, Response $response, ArchiverInterface $archiver)
    {
        $items = $request->input('items', []);
        $recursive = (bool) $request->input('recursive', false);
        $zipaction = $request->input('zipaction', 'create');

        $destination = $request->input('destination', $this->separator);
        $name = $request->input('name', $this->config->get('file.default_archive_name'));

        if($zipaction === 'create') {
            $uniqid = $archiver->createArchive($this->storage);
        }else {
            $uniqid = $request->input('uniqid');
            $archiver->setArchive($this->storage, $uniqid);
        }

        foreach ($items as $item) {
            if ($item->type == 'dir') {
                if($recursive) {
                    $archiver->addDirectoryFromStorage($item->path, $destination);
                }else{
                    $archiver->addEmptyDirectory($item->path, $destination);
                }
            }
            if ($item->type == 'file') {
                $archiver->addFileFromStorage($item->path, $destination);
            }
        }

        if($zipaction === 'store') {

            $archiver->storeArchive($destination, $name);

        }else{

            $archiver->closeArchive();
        }

        return $response->json(['uniqid' => $uniqid]);
    }

    /**
     * @param Request $request
     * @param StreamedResponse $streamedResponse
     * @param TmpfsInterface $tmpfs
     * @return void
     */
    public function batchDownloadStart(Request $request, StreamedResponse $streamedResponse, TmpfsInterface $tmpfs)
    {


        $uniqid = (string) preg_replace('/[^0-9a-zA-Z_]/', '', (string) $request->input('uniqid'));
        $name = $request->input('name', $this->config->get('file.default_archive_name'));

        $file = $tmpfs->readStream($uniqid);

        $streamedResponse->setCallback(function () use ($file, $tmpfs, $uniqid) {
            // @codeCoverageIgnoreStart
            set_time_limit(0);
            if ($file['stream']) {
                while (! feof($file['stream'])) {
                    echo fread($file['stream'], 1024 * 8);
                    @ob_flush();
                    @flush();
                }
                fclose($file['stream']);
            }
            $tmpfs->remove($uniqid);
            // @codeCoverageIgnoreEnd
        });

        $streamedResponse->headers->set(
            'Content-Disposition',
            HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $name,
                $this->config->get('file.default_archive_name')
            )
        );
        $streamedResponse->headers->set(
            'Content-Type',
            'application/octet-stream'
        );
        $streamedResponse->headers->set(
            'Content-Transfer-Encoding',
            'binary'
        );
        if (isset($file['filesize'])) {
            $streamedResponse->headers->set(
                'Content-Length',
                $file['filesize']
            );
        }

        $streamedResponse->send();
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param TmpfsInterface $tmpfs
     * @return void
     */
    public function batchDownloadCancelled(Request $request, Response $response, TmpfsInterface $tmpfs)
    {

        $uniqid = (string) preg_replace('/[^0-9a-zA-Z_]/', '', (string) $request->input('uniqid'));
        $tmpfs->remove($uniqid);

        return $response->json('Deleted');
    }
}
