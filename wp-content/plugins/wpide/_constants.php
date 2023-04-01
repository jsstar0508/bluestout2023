<?php
namespace WPIDE\Constants;

defined( 'ABSPATH' ) || exit;

define(__NAMESPACE__ . '\DIR', wp_normalize_path(plugin_dir_path(__FILE__)));
define(__NAMESPACE__ . '\URL', plugin_dir_url(__FILE__));

define(__NAMESPACE__ . '\SLUG', 'wpide');
define(__NAMESPACE__ . '\NAME', 'WPIDE');

define(__NAMESPACE__ . '\VERSION', '3.4.6');
define(__NAMESPACE__ . '\FM_VERSION', '7.8.1');

define(__NAMESPACE__ . '\AUTHOR', 'XplodedThemes');
define(__NAMESPACE__ . '\AUTHOR_URL', 'https://xplodedthemes.com');
define(__NAMESPACE__ . '\PLUGIN_URL', 'https://wpide.com');

define(__NAMESPACE__ . '\ASSETS_DIR', DIR . 'dist/');
define(__NAMESPACE__ . '\ASSETS_URL', URL . 'dist/');
define(__NAMESPACE__ . '\UPLOADS_DIR', wp_normalize_path(wp_upload_dir()['basedir']).'/'.SLUG.'/');
define(__NAMESPACE__ . '\BACKUPS_DIR', UPLOADS_DIR.'backups/');
define(__NAMESPACE__ . '\BACKUPS_TODAY_DIR', BACKUPS_DIR.date('Y-m-d'));
define(__NAMESPACE__ . '\IMAGE_DATA_DIR', UPLOADS_DIR.'imagedata/');
define(__NAMESPACE__ . '\TMP_DIR', UPLOADS_DIR.'tmp/');
define(__NAMESPACE__ . '\CONTENT_DIR', wp_normalize_path(realpath(DIR . '/../../')));
define(__NAMESPACE__ . '\WP_PATH', wp_normalize_path(realpath(CONTENT_DIR.'/../')));

define(__NAMESPACE__ . '\FATAL_ERROR_DROPIN_VERSION', '1.1');
define(__NAMESPACE__ . '\FATAL_ERROR_DROPIN_VERSION_OPT', SLUG.'_dropin_version');
define(__NAMESPACE__ . '\FATAL_ERROR_DROPIN', 'fatal-error-handler.php');
define(__NAMESPACE__ . '\IS_DEV', empty(getenv('SERVER_SOFTWARE')) && defined('WPIDE_DEV_ENV') && WPIDE_DEV_ENV === true && defined('WPIDE_DEV_URL'));

define(__NAMESPACE__ . '\GTM_ID', 'GTM-MRT34RM');

define(__NAMESPACE__ . '\FS_ID', '10410');
define(__NAMESPACE__ . '\FS_KEY', 'pk_cbf88d3e3daa650c15108ecc2a7b8');
