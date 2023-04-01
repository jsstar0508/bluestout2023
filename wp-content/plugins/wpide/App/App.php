<?php
namespace WPIDE\App;

use WPIDE\App\Classes\Freemius;
use WPIDE\App\Classes\Migrations;
use WPIDE\App\Classes\RecommendedPlugins;
use WPIDE\App\Classes\PromoNotice;
use WPIDE\App\Classes\ReviewNotice;
use WPIDE\App\Classes\Notices;
use WPIDE\App\Config\Config;
use WPIDE\App\Container\Container;
use WPIDE\App\Helpers\EmptyDir;
use WPIDE\App\Helpers\FileBackup;
use WPIDE\App\Helpers\ImageStateData;
use WPIDE\App\Kernel\Request;
use WPIDE\App\Kernel\Response;
use WPIDE\App\Kernel\StreamedResponse;
use WPIDE\App\Services\Services;
use WPIDE\App\Services\Storage\LocalFileSystem;
use const WPIDE\Constants\AUTHOR;
use const WPIDE\Constants\CONTENT_DIR;
use const WPIDE\Constants\DIR;
use const WPIDE\Constants\TMP_DIR;
use const WPIDE\Constants\ASSETS_URL;
use const WPIDE\Constants\FATAL_ERROR_DROPIN;
use const WPIDE\Constants\FATAL_ERROR_DROPIN_VERSION;
use const WPIDE\Constants\FATAL_ERROR_DROPIN_VERSION_OPT;
use const WPIDE\Constants\NAME;
use const WPIDE\Constants\SLUG;
use const WPIDE\Constants\PLUGIN_URL;
use const WPIDE\Constants\VERSION;

class App
{
    public static $_instance;

    private $container;
    private $dependencyNotice;
    private $isNetworkActive;

    protected $notices = [];
    public $services = [];

    public function __construct()
    {

        register_activation_hook(WPIDE_FILE, [$this, 'activate']);
        register_deactivation_hook(WPIDE_FILE, [$this, 'deactivate']);

        add_action( 'plugins_loaded', [$this, 'load']);

        $this->container = new Container();

    }

    public function activate() {
        $this->installDropIn();
    }

    public function deactivate() {
        $this->unInstallDropIn();
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function resolve($name)
    {
        return $this->container->get($name);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function cache() {

        return $this->resolve('WPIDE\App\Services\Cache\Cache');
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function transient() {

        return $this->resolve('WPIDE\App\Services\Transient\Transient');
    }

    public function call($callable, array $parameters)
    {
        return $this->container->call($callable, $parameters);
    }

    public function prefix($suffix = null): string
    {
        return SLUG . (!empty($suffix) ? '_' . $suffix : '');
    }

    public function isLatestDropIn(): bool
    {

        $currentVersion = get_option(FATAL_ERROR_DROPIN_VERSION_OPT, null);

        return $currentVersion === FATAL_ERROR_DROPIN_VERSION;
    }

    public function dropInExists(): bool
    {
        $fs = LocalFileSystem::load(CONTENT_DIR);

        return $fs->fileExists(FATAL_ERROR_DROPIN);
    }

    public function installDropIn() {

        $fs = LocalFileSystem::load(CONTENT_DIR);

        if($fs->fileExists(FATAL_ERROR_DROPIN)) {
            $this->unInstallDropIn();
        }

        $fs->copyFile('plugins/'.basename(DIR).'/wp-content/'.FATAL_ERROR_DROPIN, './');

        update_option(FATAL_ERROR_DROPIN_VERSION_OPT, FATAL_ERROR_DROPIN_VERSION);
    }

    public function unInstallDropIn() {

        $fs = LocalFileSystem::load(CONTENT_DIR);

        if($fs->fileExists(FATAL_ERROR_DROPIN)) {
            $fs->deleteFile(FATAL_ERROR_DROPIN);
        }
    }

    /**
     * @throws \Exception
     */
    public function load()
    {

        if(!$this->dependencyCheck() || !is_admin()) {
            return;
        }

        if(!$this->dropInExists() || !$this->isLatestDropIn()) {
            $this->installDropIn();
        }

        $this->loadTextDomain();
        $this->loadHooks();

        Migrations::init();
        PromoNotice::init();
        if(!PromoNotice::enabled()) {
            ReviewNotice::init();
        }
        RecommendedPlugins::init();
    }

    protected function loadTextDomain() {

        load_plugin_textdomain(
            SLUG,
            false,
            basename( dirname( WPIDE_FILE ) ) . '/languages/'
        );
    }

    protected function dependencyCheck(): bool
    {

        if(!empty($this->dependencyNotice)) {
            echo $this->dependencyNotice;
        }

        // Perform checks here
        // $class = 'notice notice-error';
        // $message = '';
        // $this->dependencyNotice = sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message);

        if(!empty($this->dependencyNotice)) {
            add_action('admin_notices', [$this, 'dependencyCheck']);
            return false;
        }

        return true;
    }

    protected function loadHooks()
    {

        if($this->showAdminMenu()) {
            add_action('admin_menu', [$this, 'adminMenu' ]);
            add_action('network_admin_menu', [$this, 'adminMenu']);
        }

        add_action("wp_ajax_wpide_request", [$this, "ajaxRequest"]);
        add_action("admin_init", [$this, "registerTasks"]);
        add_action('admin_enqueue_scripts', [ $this, 'enqueueCommonAssets' ]);

        if($this->isPluginScreen()) {

            // When network active, If trying to load plugin admin url, redirect back to network root
            if(!$this->showAdminMenu() && !$this->isFreemiusScreen() && !Freemius::sdk()->is_activation_mode() && !Freemius::showSubmenus()) {
                wp_redirect(network_admin_url());
                die();
            }

            add_filter('admin_title', [$this, 'setAdminTitle']);

            add_action('network_admin_menu', [$this, 'addAdminFavicon']);
            add_action("network_admin_menu", [$this, "setCurrentScreen"], -1);
            add_action('network_admin_menu', [Notices::class, 'init']);

            add_action('admin_head', [$this, 'addAdminFavicon']);
            add_action("admin_menu", [$this, "setCurrentScreen"], -1);
            add_action('admin_menu', [Notices::class, 'init']);

            add_filter('screen_options_show_screen', '__return_false');
            add_action('admin_enqueue_scripts', [ $this, 'deregisterWpStyles' ]);
        }
    }

    public function registerTasks() {

        //TestTask::register();
    }

    public function setCurrentScreen() {

        set_current_screen(SLUG);
    }

    public function canManage(): bool
    {
        return current_user_can('manage_options');
    }

    public function showAdminMenu(): bool
    {

        if(fs_is_network_admin()) {

            return $this->isNetworkActive() && (WP_FS__SHOW_NETWORK_EVEN_WHEN_DELEGATED || !Freemius::sdk()->is_network_delegated_connection());
        }

        return true;
    }

    public function adminMenu()
    {

        $menuTopItems = apply_filters('wpide_admin_menu_top_items', [
            [
                'title' => __( 'File Manager', 'wpide' ),
                'slug' => SLUG.'#/file-manager',
                'callback' => [$this, 'bootstrap'],
                'position' => 10
            ],
            [
                'title' => __( 'File Editor', 'wpide' ),
                'slug' => SLUG.'#/file-editor',
                'callback' => [$this, 'bootstrap'],
                'position' => 20
            ],
            [
                'title' => __( 'Image Editor', 'wpide' ),
                'slug' => SLUG.'#/image-editor',
                'callback' => [$this, 'bootstrap'],
                'position' => 20
            ],
            [
                'title' => __( 'DB Manager', 'wpide' ),
                'slug' => SLUG.'#/db-manager',
                'callback' => [$this, 'bootstrap'],
                'position' => 30
            ],
            [
                'title' => __( 'Config Manager', 'wpide' ),
                'slug' => SLUG.'#/config-manager',
                'callback' => [$this, 'bootstrap'],
                'position' => 30
            ],
            [
                'title' => __( 'Settings', 'wpide' ),
                'slug' => SLUG.'#/settings',
                'callback' => [$this, 'bootstrap'],
                'position' => 40
            ]
        ]);

        $menuBottomItems = apply_filters('wpide_admin_menu_bottom_items', []);

        add_menu_page(
            NAME,
            NAME,
            'manage_options',
            SLUG,
            [$this, 'bootstrap'],
            'dashicons-editor-code'
        );

        $highestPosition = 0;
        foreach ($menuTopItems as $item) {
            add_submenu_page(
                SLUG,
                $item['title'],
                $item['title'],
                'manage_options',
                $item['slug'],
                $item['callback'],
                $item['position']
            );

            if ($item['position'] > $highestPosition) {
                $highestPosition = $item['position'];
            }
        }

        add_submenu_page(
            SLUG,
            '',
            '',
            'manage_options',
            '#divider',
            '',
            $highestPosition + 10
        );

        foreach ($menuBottomItems as $item) {
            add_submenu_page(
                SLUG,
                $item['title'],
                $item['title'],
                'manage_options',
                $item['slug'],
                $item['callback'],
                $item['position']
            );
        }

        remove_submenu_page(SLUG, SLUG);

        do_action('wpide_admin_menu');

    }

    public function bootstrap() {

        FileBackup::init();
        ImageStateData::init();
        EmptyDir::create(TMP_DIR);

        $config = AppConfig::load();
        $services = AppServices::load();
        $request = Request::createFromGlobals();
        $response = new Response();
        $sresponse = new StreamedResponse();

        $this->container->set(Config::class, $config);
        $this->container->set(Services::class, $services);
        $this->container->set(Container::class, $this->container);
        $this->container->set(Request::class, $request);
        $this->container->set(Response::class, $response);
        $this->container->set(StreamedResponse::class, $sresponse);

        $this->services = [];
        foreach ($services->get() as $key => $service) {
            if(empty($service['handler'])) {
                continue;
            }
            $this->container->set($key, $this->container->get($service['handler']));
            $this->container->get($key)->init(isset($service['config']) ? $service['config'] : []);
            $this->services[$key] = $this->container->get($key);
        }

        if(!defined('WPIDE_DOING_TASK')) {
            if (wp_doing_ajax()) {
                $response->send();
            } else {
                $response->sendContent();
            }
        }
    }

    public function deregisterWpStyles()
    {

        wp_deregister_style('color');
        wp_deregister_style('forms');
        wp_deregister_style('dashboard');
        wp_deregister_style('list-tables');
        wp_deregister_style('edit');
        wp_deregister_style('revisions');
        wp_deregister_style('media');
        wp_deregister_style('themes');
        wp_deregister_style('about');
        wp_deregister_style('nav-menus');
        wp_deregister_style('widgets');
        wp_deregister_style('l10n');
        wp_deregister_style('site-icon');

        wp_register_style('color', null);
        wp_register_style('forms', null);
        wp_register_style('dashboard', null);
        wp_register_style('list-tables', null);
        wp_register_style('edit', null);
        wp_register_style('revisions', null);
        wp_register_style('media', null);
        wp_register_style('themes', null);
        wp_register_style('about', null);
        wp_register_style('nav-menus', null);
        wp_register_style('widgets', null);
        wp_register_style('l10n', null);
        wp_register_style('site-icon', null);

    }

    public function enqueueCommonAssets()
    {

        wp_enqueue_style(SLUG.'-fs-notices', ASSETS_URL.'global/css/global.css', [], VERSION);
    }

    public function ajaxRequest()
    {
        if(!$this->canManage()) {
            die(esc_html__('Restricted Access', 'wpide'));
        }

        $this->bootstrap();
        die();
    }

    public function isPluginScreen($section = null): bool
    {

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        $slug = SLUG;

        if(!empty($section)) {
            $slug .= '-'. $section;
        }

        if(!empty($screen)) {
            return strpos($screen->base, 'page_' . $slug) !== false;
        }

        if(!empty($_GET['page'])) {
            $page = sanitize_text_field($_GET['page']);
            return strpos($page, $slug) !== false;
        }

        return false;
    }

    public function isFreemiusScreen($section = null): bool
    {

        if($section) {
            return $this->isPluginScreen($section);
        }

        if(
            $this->isPluginScreen('account') ||
            $this->isPluginScreen('pricing') ||
            $this->isPluginScreen('contact') ||
            $this->isPluginScreen('affiliates')
        ) {
            return true;
        }

        return false;
    }

    public function isNetworkActive(): bool
    {

        if(!isset($this->isNetworkActive)) {

            $plugin = basename(WPIDE_DIR) . '/' . basename(WPIDE_FILE);
            $this->isNetworkActive = is_plugin_active_for_network($plugin);
        }

        return $this->isNetworkActive;
    }

    public function getAdminUrl($section = null, $network = false): string {

        $admin_url_function = $network ? 'network_admin_url' : 'admin_url';

        $url = 'admin.php?page='.SLUG;
        if(!empty($section)) {
            $url .= '#/'.$section;
        }
        return $admin_url_function( $url );
    }

    public function getAjaxUrl($req = ''): string
    {
        return admin_url('admin-ajax.php').'?action=wpide_request&req='.$req;
    }

    /**
     * Get external url with utm campaign / content
     *
     * @param string|null $utm_content
     * @param string|null $url
     *
     * @return string $link;
     * @since     1.0.0
     */
    public function getExternalUrl(string $utm_content = null, string $url = null): string
    {

        $url = !empty($url) ? $url : PLUGIN_URL;

        if(!empty($utm_content)) {
            $url .= '?utm_source='.AUTHOR.'&utm_medium=Plugin&utm_campaign='.SLUG.'&utm_content='.$utm_content;
        }

        return $url;
    }

    /**
     * Filter admin page title
     */
    public function setAdminTitle(): string
    {
        return NAME;
    }

    /**
     * render backend favicon
     */
    public function addAdminFavicon () {

        echo '<link rel="shortcut icon" type="image/svg+xml" href="' . ASSETS_URL. '/img/favicon.svg">',"\n";
        echo '<link rel="shortcut icon" type="image/x-icon" href="' . ASSETS_URL. '/img/favicon.ico">',"\n";
        echo '<link rel="shortcut icon" type="image/png" href="' . ASSETS_URL. '/img/favicon.png">',"\n";
        echo '<meta name="theme-color" content="#ffffff">',"\n";
    }


    /**
     * @return App
     */
    public static function instance(): App
    {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

}
