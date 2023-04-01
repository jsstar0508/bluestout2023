<?php
namespace WPIDE\App\Helpers;

use WPIDE\App\Services\Storage\LocalFileSystem;

class EmptyDir
{

    public static function create($path) {

        if ( ! is_dir( $path ) ) {
            wp_mkdir_p( $path );
        }

        $fs = LocalFileSystem::load($path);

        if(!$fs->fileExists('/.htaccess')){

            $fs->store('/', '.htaccess', 'Deny from all', true);
        }

        if(!$fs->fileExists('/index.html')){

            $fs->store('/', 'index.html', '', true);
        }
    }

}
