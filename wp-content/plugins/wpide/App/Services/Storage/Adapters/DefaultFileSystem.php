<?php
namespace WPIDE\App\Services\Storage\Adapters;

use League\Flysystem\Adapter\Local;
use League\MimeTypeDetection\ExtensionMimeTypeDetector;
use League\MimeTypeDetection\FinfoMimeTypeDetector;

class DefaultFileSystem extends Local
{
    /**
     * @inheritdoc
     */
    public function getMimetype($path)
    {
        $location = $this->applyPathPrefix($path);
        if(extension_loaded('fileinfo')) {
            $mimeDetector = new FinfoMimeTypeDetector();
        }else{
            $mimeDetector = new ExtensionMimeTypeDetector();
        }
        $mimetype = $mimeDetector->detectMimeTypeFromPath($location);

        return ['path' => $path, 'type' => 'file', 'mimetype' => $mimetype];
    }

}