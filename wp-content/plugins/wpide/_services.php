<?php
defined( 'ABSPATH' ) || exit;

use WPIDE\App\Services\Storage\LocalFileSystem;
use WPIDE\App\Classes\Freemius;

use const WPIDE\Constants\DIR;
use const WPIDE\Constants\SLUG;
use const WPIDE\Constants\TMP_DIR;
use const WPIDE\Constants\WP_PATH;

return [
    'WPIDE\App\Services\Logger\LoggerInterface' => [
        'handler' => '\WPIDE\App\Services\Logger\Adapters\MonoLogger',
        'config' => [
            'monolog_handlers' => [
                function () {
                    return new \Monolog\Handler\StreamHandler(
                        ini_get('error_log'),
                        \Monolog\Logger::DEBUG
                    );
                },
            ],
        ],
    ],
    'WPIDE\App\Services\Cors\Cors' => [
        'handler' => '\WPIDE\App\Services\Cors\Cors',
        'config' => [
            'enabled' => defined('WP_DEBUG') && WP_DEBUG,
        ],
    ],
    'WPIDE\App\Services\Tmpfs\TmpfsInterface' => [
        'handler' => '\WPIDE\App\Services\Tmpfs\Adapters\Tmpfs',
        'config' => [
            'path' => TMP_DIR,
            'gc_probability_perc' => 10,
            'gc_older_than' => 60 * 60 * 24 * 2, // 2 days
        ],
    ],
    'WPIDE\App\Services\Security\Security' => [
        'handler' => '\WPIDE\App\Services\Security\Security',
        'config' => [
            'ip_allowlist' => [],
            'ip_denylist' => []
        ],
    ],
    'WPIDE\App\Services\View\ViewInterface' => [
        'handler' => '\WPIDE\App\Services\View\Adapters\Vuejs',
        'config' => [],
    ],
    'WPIDE\App\Services\Storage\Filesystem' => [
        'handler' => '\WPIDE\App\Services\Storage\Filesystem',
        'config' => LocalFileSystem::getConfig(),
    ],
    'WPIDE\App\Services\Database\Database' => [
        'handler' => Freemius::sdk()->can_use_premium_code__premium_only() ? '\WPIDE\App\Services\Database\Database' : null,
        'config' => [],
    ],
    'WPIDE\App\Services\ConfigManager\ConfigManager' => [
        'handler' => Freemius::sdk()->can_use_premium_code__premium_only() ? '\WPIDE\App\Services\ConfigManager\ConfigManager' : null,
        'config' => [],
    ],
    'WPIDE\App\Services\Cache\Cache' => [
        'handler' => '\WPIDE\App\Services\Cache\Cache',
        'config' => [
            'group' => SLUG
        ],
    ],
    'WPIDE\App\Services\Transient\Transient' => [
        'handler' => '\WPIDE\App\Services\Transient\Transient',
        'config' => [
            'prefix' => SLUG.'_'
        ],
    ],
    'WPIDE\App\Services\Archiver\ArchiverInterface' => [
        'handler' => '\WPIDE\App\Services\Archiver\Adapters\ZipArchiver',
        'config' => [],
    ],
    'WPIDE\App\Services\Auth\AuthInterface' => [
        'handler' => '\WPIDE\App\Services\Auth\Adapters\WPAuth',
        'config' => [
            'wp_dir' => WP_PATH,
            'permissions' => ['read', 'write', 'upload', 'download', 'batchdownload', 'zip'],
            'private_repos' => false,
        ],
    ],
    'WPIDE\App\Services\Router\Router' => [
        'handler' => '\WPIDE\App\Services\Router\Router',
        'config' => [
            'query_param' => 'req',
            'routes_file' => DIR.'_routes.php',
        ],
    ],
];
