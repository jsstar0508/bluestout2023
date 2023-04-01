<?php

namespace WPIDE\App\Services\Storage;

use  WPIDE\App\Classes\Freemius ;
use const  WPIDE\Constants\DIR ;
use const  WPIDE\Constants\CONTENT_DIR ;
use const  WPIDE\Constants\FATAL_ERROR_DROPIN ;
use const  WPIDE\Constants\IMAGE_DATA_DIR ;
use const  WPIDE\Constants\TMP_DIR ;
use const  WPIDE\Constants\WP_PATH ;
use  WPIDE\App\AppConfig ;
use  WPIDE\App\Services\Storage\Adapters\WPFileSystem ;
use  WPIDE\App\Services\Storage\Adapters\DefaultFileSystem ;
class LocalFileSystem
{
    public static function load( $root = null ) : Filesystem
    {
        $fs = new Filesystem();
        $fs->init( self::getConfig( $root ) );
        return $fs;
    }
    
    public static function getConfig( $root = null ) : array
    {
        if ( empty($root) ) {
            $root = self::getRootDir();
        }
        if ( !str_contains( $root, WP_PATH ) ) {
            $root = WP_PATH . $root;
        }
        $root .= '/';
        $root = wp_normalize_path( $root );
        $excluded = [
            'dirs'  => LocalFileSystem::excludedDirs(),
            'files' => LocalFileSystem::excludedFiles(),
        ];
        return [
            'root'           => $root,
            'separator'      => '/',
            'excluded_dirs'  => ( !empty($excluded['dirs']) ? $excluded['dirs'] : [] ),
            'excluded_files' => ( !empty($excluded['files']) ? $excluded['files'] : [] ),
            'config'         => [],
            'adapter'        => function () use( $root ) {
            $permissions = [
                'file' => [
                'public'  => ( defined( 'FS_CHMOD_FILE' ) ? FS_CHMOD_FILE : (( fileperms( WP_PATH . '/index.php' ) ? 0644 : 0777 )) ),
                'private' => 0600,
            ],
                'dir'  => [
                'public'  => ( defined( 'FS_CHMOD_DIR' ) ? FS_CHMOD_DIR : (( fileperms( WP_PATH ) ? 0755 : 0777 )) ),
                'private' => 0700,
            ],
            ];
            global  $wp_filesystem ;
            $use_wp_filesystem = false;
            // Try and use WP filesystem
            
            if ( function_exists( 'request_filesystem_credentials' ) ) {
                
                if ( defined( 'FS_METHOD' ) ) {
                    if ( !defined( 'WPIDE_FS_METHOD_FORCED_ELSEWHERE' ) ) {
                        define( 'WPIDE_FS_METHOD_FORCED_ELSEWHERE', FS_METHOD );
                    }
                } else {
                    define( 'FS_METHOD', 'direct' );
                }
                
                ob_start();
                $credentials = request_filesystem_credentials( '', FS_METHOD );
                ob_end_clean();
                $use_wp_filesystem = !empty($wp_filesystem) && wp_filesystem( $credentials ) === true;
            }
            
            
            if ( $use_wp_filesystem ) {
                return new WPFileSystem(
                    $root,
                    $wp_filesystem,
                    1,
                    $permissions
                );
            } else {
                return new DefaultFileSystem(
                    $root,
                    LOCK_EX,
                    1,
                    $permissions
                );
            }
        
        },
        ];
    }
    
    public static function getRootDir()
    {
        return AppConfig::get( 'file.root', '/' );
    }
    
    public static function advancedModeEnabled() : bool
    {
        $advanced_mode = (bool) AppConfig::get( 'file.advanced_mode', false );
        if ( $advanced_mode ) {
            AppConfig::update( 'file.advanced_mode', false );
        }
        $rootDefault = AppConfig::getField( 'file.root.default' );
        if ( self::isRootExcluded() ) {
            AppConfig::update( 'file.root', $rootDefault );
        }
        return false;
    }
    
    public static function excludedEntries( $type ) : array
    {
        $entries = AppConfig::get( 'file.filter_entries', [] );
        $entries = ( is_array( $entries ) ? $entries : [] );
        return array_filter( $entries, function ( $entry ) use( $type ) {
            
            if ( $type === 'dir' ) {
                return substr( $entry, -1 ) === '/';
            } else {
                return substr( $entry, -1 ) !== '/';
            }
        
        } );
    }
    
    public static function excludedDirs( $forceSafeMode = false ) : array
    {
        $user_excluded_dirs = self::excludedEntries( 'dir' );
        $user_excluded_dirs = array_merge( [ IMAGE_DATA_DIR, TMP_DIR ], $user_excluded_dirs );
        if ( $forceSafeMode || !self::advancedModeEnabled() ) {
            $user_excluded_dirs = array_merge( [
                DIR,
                rtrim( DIR, '/' ) . '-pro/',
                wp_normalize_path( WP_PLUGIN_DIR . '/wpide/' ),
                wp_normalize_path( WP_PLUGIN_DIR . '/wpide-pro/' ),
                wp_normalize_path( WP_PATH . '/wp-admin/' ),
                wp_normalize_path( WP_PATH . '/wp-includes/' )
            ], $user_excluded_dirs );
        }
        return $user_excluded_dirs;
    }
    
    public static function excludedFiles( $forceSafeMode = false ) : array
    {
        $user_excluded_files = self::excludedEntries( 'file' );
        $user_excluded_files = array_merge( [ CONTENT_DIR . '/' . FATAL_ERROR_DROPIN ], $user_excluded_files );
        if ( $forceSafeMode || !self::advancedModeEnabled() ) {
            $user_excluded_files = array_merge( [ wp_normalize_path( WP_PATH . '/wp-*.php' ), wp_normalize_path( WP_PATH . '/index.php' ), wp_normalize_path( WP_PATH . '/xmlrpc.php' ) ], $user_excluded_files );
        }
        return $user_excluded_files;
    }
    
    public static function isDirExcluded( $path ) : bool
    {
        
        if ( $path !== '/' ) {
            $path = wp_normalize_path( WP_PATH . '/' . $path . '/' );
            foreach ( self::excludedDirs( true ) as $excluded ) {
                if ( str_contains( $path, $excluded ) ) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public static function isRootExcluded() : bool
    {
        $root = self::getRootDir();
        return self::isDirExcluded( $root );
    }

}