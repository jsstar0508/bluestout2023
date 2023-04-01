<?php

namespace WPIDE\App\Classes;

use WPIDE\App\App;
use const WPIDE\Constants\DIR;
use const WPIDE\Constants\VERSION;

class Migrations
{

    public static $version_key;
    public static $installed_time_key;
    public static $changelog_viewed_key;

    public static $new_version;
    public static $old_version;
    public static $installed_time;
    public static $changelog_viewed;

    public static $migrations = array();

    public static function init()
    {

        self::$version_key = self::get_version_key();
        self::$installed_time_key = self::get_installed_time_key();
        self::$changelog_viewed_key = self::get_changelog_viewed_key();
        self::$new_version = VERSION;

        self::$changelog_viewed = (bool)get_option(self::$changelog_viewed_key);

        self::$old_version = get_option(self::$version_key);

        if(defined('WPIDE_DEBUG_MIGRATION_OLD_VERSION')) {
            self::$old_version = WPIDE_DEBUG_MIGRATION_OLD_VERSION;
        }

        self::$installed_time = intval(get_option(self::$installed_time_key));

        if (empty(self::$installed_time)) {
            self::$installed_time = time();
            update_option(self::$installed_time_key, self::$installed_time);
        }

        add_action('init', [ __CLASS__, 'upgrade'], 10);
    }

    public static function get_version_key(): string
    {

        return App::instance()->prefix('version');
    }

    public static function get_installed_time_key(): string
    {

        return App::instance()->prefix('installed_time');
    }

    public static function get_changelog_viewed_key(): string
    {

        return App::instance()->prefix('changelog_viewed');
    }

    public static function get_migrations(): array
    {

        $files = glob(DIR.'migrations/migration-*.php');

        $migrations = array();

        foreach ($files as $file) {

            preg_match('/migration\-(.+?)\.php/', $file, $matches);
            $migrations[] = $matches[1];
        }

        return $migrations;
    }

    public static function upgrade()
    {

        if (self::$new_version !== self::$old_version) {

            $migrations = self::get_migrations();

            foreach ($migrations as $migration) {

                if (self::$old_version < $migration) {

                    self::migrate($migration);
                }
            }
            // End Migrations

            update_option(self::$version_key, self::$new_version);

            self::after_upgrade();
        }

    }

    public static function migrate($version)
    {

        $path = DIR.'migrations/migration-' . $version . '.php';

        if (file_exists($path)) {

            require_once $path;
        }

    }

    public static function after_upgrade()
    {

        self::set_changelog_viewed(false);

        do_action(App::instance()->prefix('migration_complete'));
    }

    public static function has_unviewed_changelog(): bool
    {

        return !self::$changelog_viewed;
    }

    public static function set_changelog_viewed($viewed = true)
    {

        self::$changelog_viewed = $viewed;
        update_option(self::$changelog_viewed_key, intval($viewed));
    }

}
