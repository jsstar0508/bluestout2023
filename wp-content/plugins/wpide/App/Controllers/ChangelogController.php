<?php
namespace WPIDE\App\Controllers;

use WPIDE\App\Classes\Parsedown;
use WPIDE\App\Kernel\Response;
use const WPIDE\Constants\DIR;

class ChangelogController {

    public function get(Response $response) {

        $parsedown = new Parsedown;

        $changelog = '';

        $data = file_get_contents( DIR.'readme.txt' );

        if ( ! empty( $data ) ) {
            $data = explode( '== Changelog ==', $data );
            if ( ! empty( $data[1] ) ) {

                $changelog = $data[1];
                $changelog = preg_replace(
                    array(
                        '/\[\/\/\]\: \# fs_.+?_only_begin/',
                        '/\[\/\/\]\: \# fs_.+?_only_end/',
                    ),
                    '',
                    $changelog
                );

                $changelog = $parsedown->text( $changelog );

                $changelog = preg_replace(
                    array(
                        '/\<strong\>(.+?)\<\/strong>\:(.+?)\n/i',
                        '/\<p\>/',
                        '/\<\/p\>/'
                    ),
                    array(
                        '<span class="update-type $1">$1</span><span class="update-txt">$2</span>',
                        '',
                        ''
                    ),
                    $changelog
                );

            }
        }

        return $response->html($changelog);

    }
}