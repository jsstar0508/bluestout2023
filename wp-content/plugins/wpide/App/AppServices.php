<?php
namespace WPIDE\App;

use WPIDE\App\Services\Services;
use const WPIDE\Constants\DIR;

class AppServices {

    protected static $services;

    public static function load(): Services
    {
        if(!empty(self::$services )) {
            return self::$services;
        }

        self::$services = new Services(require DIR.'_services.php');

        return self::$services;
    }
}