<?php
/**
 * Class EPS_Redirects_Plugin
 *
 * Inits the EPS_Redirects Plugin's core functionality and admin management.
 *
 *
 */


// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

class EPS_Redirects_Plugin
{

  protected $config = array(
    'version'           => EPS_REDIRECT_VERSION,
    'option_slug'       => 'eps_redirects',
    'page_slug'         => 'eps_redirects',
    'page_title'        => '301 Redirects',
    'menu_location'     => 'options',
    'page_permission'   => 'manage_options',
    'directory'         => 'eps-301-redirects'
  );

  protected $dependancies = array();

  protected $tables = array();

  public $name = '301 Redirects';


  protected $resources = array(
    'css' => array(
      'admin.css'
    ),
    'js' => array(
      'admin.js'
    )
  );


  protected $options;
  protected $messages = array();

  public function __construct()
  {
    $this->config['url'] = plugins_url() . $this->config['directory'] . '/';
    $this->config['path'] = EPS_REDIRECT_PATH . $this->config['directory'] . '/';

    if (class_exists('EPS_Redirects_Plugin_Options'))
      $this->settings = new EPS_Redirects_Plugin_Options($this);

    register_activation_hook(__FILE__, array($this, '_activation'));
    register_deactivation_hook(__FILE__, array($this, '_deactivation'));

    if (!self::is_current_version())  self::update_self();
    add_action('init',                  array($this, 'plugin_resources'));

    // Template Hooks
    add_action('redirects_admin_tab', array($this, 'admin_tab_redirects'), 10, 1);
    add_action('404s_admin_tab', array($this, 'admin_tab_404s'), 10, 1);
    add_action('support_admin_tab', array($this, 'admin_tab_support'), 10, 1);
    add_action('link-scanner_admin_tab', array($this, 'admin_tab_link_scanner'), 10, 1);
    add_action('error_admin_tab', array($this, 'admin_tab_error'), 10, 1);
    add_action('import-export_admin_tab', array($this, 'admin_tab_import_export'), 10, 1);
    add_action('eps_redirects_panels_left', array($this, 'admin_panel_cache'));
    //add_action('eps_redirects_panels_right', array($this, 'admin_panel_donate'));
    add_action('admin_notices', array($this, 'show_review_notice'));
    add_action('admin_action_301_dismiss_notice', array($this, 'dismiss_notice'));

    // Actions
    add_action('admin_init',            array($this, 'check_plugin_actions'));
  }

  private function resource_path($path, $resource)
  {
    return strtolower(
      $this->config['url']
        . $path . '/'
        . $resource
    );
  }

  private function resource_name($resource)
  {
    return strtolower($this->name . '_' . key($resource));
  }

  public static function _activation()
  {
    self::_create_redirect_table(); // Maybe create the tables
    if (!self::is_current_version()) {
      self::update_self();
    }

    self::reset_pointers();
    delete_option('301-redirects-notices');
  }

  public static function _deactivation()
  { }

  // handle dismiss button for notices
  static function dismiss_notice()
  {
    check_admin_referer( '301_dismiss_notice' );

    if (empty($_GET['notice'])) {
      wp_safe_redirect(admin_url());
      exit;
    }

    $notices = get_option('301-redirects-notices', array());

    if (sanitize_text_field($_GET['notice']) == 'rate') {
      $notices['dismiss_rate'] = true;
    } else {
      wp_safe_redirect(admin_url());
      exit;
    }

    update_option('301-redirects-notices', $notices);

    if (!empty($_GET['redirect'])) {
      wp_safe_redirect($_GET['redirect']);
    } else {
      wp_safe_redirect(admin_url());
    }

    exit;
  } // dismiss_notice

  function show_review_notice()
  {
    global $wpdb;
    $table_name = $wpdb->prefix . "redirects";

    if (empty($_GET['page']) || sanitize_text_field($_GET['page']) != 'eps_redirects') {
      return;
    }

    $notices = get_option('301-redirects-notices', array());
    if (!empty($notices['dismiss_rate'])) {
      return false;
    }

    $tmp1 = $wpdb->get_var("SELECT SUM(count) FROM $table_name");
    $tmp2 = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");

    if ($tmp1 < 4 || $tmp2 < 2) {
      return;
    }

    $rate_url = 'https://wordpress.org/support/plugin/eps-301-redirects/reviews/?filter=5&rate=5#new-post';
    $dismiss_url = add_query_arg(array('action' => '301_dismiss_notice', 'notice' => 'rate', 'redirect' => urlencode($_SERVER['REQUEST_URI'])), admin_url('admin.php'));
    $dismiss_url = wp_nonce_url($dismiss_url, '301_dismiss_notice');

    echo '<div id="301_rate_notice" style="font-size: 14px;" class="notice-info notice"><p>Hi!<br>Saw that you already have ' . esc_attr($tmp2) . ' redirect rules that got used ' . esc_attr($tmp1) . ' times - that\'s awesome! We wanted to ask for your help to <b>make the plugin better</b>.<br>We just need a minute of your time to rate the plugin. It helps us out a lot!';

    echo '<br><a target="_blank" href="' . esc_url($rate_url) . '" style="vertical-align: baseline; margin-top: 15px;" class="button-primary">Help make the plugin better by rating it</a>';
    echo '&nbsp;&nbsp;&nbsp;&nbsp;<a href="' . esc_url($dismiss_url) . '">I\'ve already rated the plugin</a>';
    echo '<br><br><b>Thank you very much!</b> The 301 Redirects team';
    echo '</p></div>';
  } // show_review_notice

  public function admin_url($vars = array())
  {
    $vars = array('page' => $this->config['page_slug']) + $vars;
    $url = 'options-general.php?' . http_build_query($vars);
    return admin_url($url);
  }
  /**
     *
     * update_self
     *
     * This function will check the current version and do any fixes required
     *
     * @return string - version number.
     * @author WebFactory Ltd
     *
     */
  public function update_self()
    {
      $version = get_option('eps_redirects_version');

      if (version_compare($version, '2.0.0', '<')) {
        // migrate old format to new format.
        add_action('admin_init', array($this, '_migrate_to_v2'), 1);
      }
      $this->set_current_version(EPS_REDIRECT_VERSION);
      return EPS_REDIRECT_VERSION;
    }

  /**
     *
     * _migrate_to_v2
     *
     * Will migrate the old storage method to the new tables.
     *
     * @return nothing
     * @author WebFactory Ltd
     *
     */
  public static function _migrate_to_v2()
  {
    $redirects = get_option(self::$option_slug);

    if (empty($redirects)) return false; // No redirects to migrate.

    $new_redirects = array();

    foreach ($redirects as $from => $to) {
      $new_redirects[] = array(
        'id'        => false,
        'url_to'    => urldecode($to),
        'url_from'  => $from,
        'type'      => 'url',
        'status'    => '301'
      );
    }

    EPS_Redirects::_save_redirects($new_redirects);
  }

  /**
     *
     * _create_tables
     *
     * Creates the database architecture
     *
     * @return nothing
     * @author WebFactory Ltd
     *
     */
  public static function _create_redirect_table()
  {
    global $wpdb;

    $table_name = $wpdb->prefix . "redirects";

    $sql = "CREATE TABLE $table_name (
          id mediumint(9) NOT NULL AUTO_INCREMENT,
          url_from VARCHAR(256) DEFAULT '' NOT NULL,
          url_to VARCHAR(256) DEFAULT '' NOT NULL,
          status VARCHAR(12) DEFAULT '301' NOT NULL,
          type VARCHAR(12) DEFAULT 'url' NOT NULL,
          count mediumint(9) DEFAULT 0 NOT NULL,
          UNIQUE KEY id (id)
       );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    return dbDelta($sql);
  }


  /**
     *
     * plugin_resources
     *
     * Enqueues the resources, and makes sure we have what we need to proceed.
     *
     * @return nothing
     * @author WebFactory Ltd
     *
     */
  public static function plugin_resources()
  {
    global $EPS_Redirects_Plugin, $wp_rewrite;

    $pointers = get_option('eps_pointers');

    if (is_admin() && $pointers && !empty($wp_rewrite->permalink_structure) && (empty($_GET['page']) || sanitize_text_field($_GET['page']) != $EPS_Redirects_Plugin->config('page_slug'))) {
      $pointers['_nonce_dismiss_pointer'] = wp_create_nonce('eps_dismiss_pointer');
      wp_enqueue_script('wp-pointer');
      wp_enqueue_script('eps-pointers', plugins_url('js/eps-admin-pointers.js', __FILE__), array('jquery'), EPS_REDIRECT_VERSION, true);
      wp_enqueue_style('wp-pointer');
      wp_localize_script('wp-pointer', 'eps_pointers', $pointers);
    }

    if (is_admin() && isset($_GET['page']) && sanitize_text_field($_GET['page']) == $EPS_Redirects_Plugin->config('page_slug')) {
      // uncomment if we want to remove pointer after plugin settings are open
      // unset($pointers['welcome']);
      // update_option('eps_pointers', $pointers);

      $notices = get_option('301-redirects-notices');

      wp_enqueue_script('jquery');

      wp_enqueue_script('eps_redirect_script', EPS_REDIRECT_URL . 'js/scripts.js');
      wp_enqueue_style('eps_redirect_styles', EPS_REDIRECT_URL . 'css/eps_redirect.css');

      wp_enqueue_style('wp-jquery-ui-dialog');
      wp_enqueue_script('jquery-ui-dialog');

      $js_vars = array(
        'nonce_get_entry' => wp_create_nonce('eps_301_get_entry'),
        'nonce_save_redirect' => wp_create_nonce('eps_301_save_redirect'),
        'nonce_delete_entry' => wp_create_nonce('eps_301_delete_entry'),
        'nonce_get_inline_edit_entry' => wp_create_nonce('eps_301_get_inline_edit_entry'),
        'auto_open_pro_dialog' => empty($notices['dismiss_auto_pro_modal']),
      );
      wp_localize_script('eps_redirect_script', 'eps_301', $js_vars);

      $notices['dismiss_auto_pro_modal'] = true;
      update_option('301-redirects-notices', $notices);
    }

    global $wp_rewrite;
    if (!isset($wp_rewrite->permalink_structure) || empty($wp_rewrite->permalink_structure)) {
      $EPS_Redirects_Plugin->add_admin_message('<b>WARNING:</b> 301 Redirects plugin requires that a permalink structure is set. The default (plain) WordPress permalink structure is not compatible with 301 Redirects.<br>Please update the <a href="options-permalink.php" title="Permalinks">Permalink Structure</a>.', "error");
    }

    global $wpdb;
    $table_name = $wpdb->prefix . "redirects";
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
      $url = $EPS_Redirects_Plugin->admin_url(array('action' => 'eps_create_tables'));
      $EPS_Redirects_Plugin->add_admin_message('WARNING: It looks like we need to <a href="' . $url . '" title="Permalinks">Create the Database Tables First!</a>', "error");
    }
  }

  // reset all pointers to default state - visible
  static function reset_pointers() {
    $pointers = array();

    $pointers['welcome'] = array('target' => '#menu-settings', 'edge' => 'left', 'align' => 'right', 'content' => 'Thank you for installing the <b style="font-weight: 800;">301 Redirects plugin</b>! Please open <a href="' . admin_url('options-general.php?page=eps_redirects'). '">Settings - 301 Redirects</a> to manage redirect rules and view the 404 error log.');

    update_option('eps_pointers', $pointers);
  } // reset_pointers

  /**
     *
     * check_plugin_actions
     *
     * This function handles various POST requests.
     *
     * @return nothing
     * @author WebFactory Ltd
     *
     */
  public function check_plugin_actions()
  {
    if (is_admin() && isset($_GET['page']) && sanitize_text_field($_GET['page']) == $this->config('page_slug')) {
      // Upload a CSV
      if (isset($_POST['eps_redirect_upload']) && wp_verify_nonce($_POST['eps_redirect_nonce_submit'], 'eps_redirect_nonce')) {
        self::_upload();
      }
      // Export a CSV
      if (isset($_POST['eps_redirect_export']) && wp_verify_nonce($_POST['eps_redirect_nonce_submit'], 'eps_redirect_nonce')) {
        self::export_csv();
      }

      // Refresh the Transient Cache
      if (isset($_POST['eps_redirect_refresh']) && wp_verify_nonce($_POST['eps_redirect_nonce_submit'], 'eps_redirect_nonce')) {
        $post_types = get_post_types(array('public' => true), 'objects');
        foreach ($post_types as $post_type) {
          $options = eps_dropdown_pages(array('post_type' => $post_type->name));
          set_transient('post_type_cache_' . $post_type->name, $options, HOUR_IN_SECONDS);
        }
        self::empty_3rd_party_cache();
        $this->add_admin_message("Success: Cache Emptied.", "updated");
      }

      // delete all rules
      if (isset($_POST['eps_delete_rules']) && wp_verify_nonce($_POST['eps_redirect_nonce_submit'], 'eps_redirect_nonce')) {
        self::delete_all_rules();
        $this->add_admin_message("Success: All Redirect Rules Deleted.", "updated");
      }

      // reset redirect hits
      if (isset($_POST['eps_reset_stats']) && wp_verify_nonce($_POST['eps_redirect_nonce_submit'], 'eps_redirect_nonce')) {
        self::reset_stats();
        $this->add_admin_message("Success: Redirect hits have been reset.", "updated");
      }

      // Save Redirects
      if (isset($_POST['eps_redirect_submit']) && wp_verify_nonce($_POST['eps_redirect_nonce_submit'], 'eps_redirect_nonce')) {
        self::_save_redirects(EPS_Redirects::_parse_serial_array($_POST['redirect']));
      }

      // Create tables
      if (isset($_GET['action']) && sanitize_text_field($_GET['action']) == 'eps_create_tables') {
        $result = self::_create_redirect_table();
      }
    }
  }

  static function delete_all_rules() {
    global $wpdb;
    $table = $wpdb->prefix . 'redirects';
    $wpdb->query('TRUNCATE TABLE ' . $table);

    return true;
  }

  static function reset_stats() {
    global $wpdb;
    $table = $wpdb->prefix . 'redirects';

    $wpdb->query('UPDATE ' . $table . ' SET count = 0');

    return true;
  }

  static function empty_3rd_party_cache() {
    wp_cache_flush();
    if (function_exists('w3tc_flush_all')) {
      w3tc_flush_all();
    }
    if (function_exists('wp_cache_clear_cache')) {
      wp_cache_clear_cache();
    }
    if (method_exists('LiteSpeed_Cache_API', 'purge_all')) {
      LiteSpeed_Cache_API::purge_all();
    }
    if (class_exists('Endurance_Page_Cache')) {
      $epc = new Endurance_Page_Cache;
      $epc->purge_all();
    }
    if (class_exists('SG_CachePress_Supercacher') && method_exists('SG_CachePress_Supercacher', 'purge_cache')) {
      SG_CachePress_Supercacher::purge_cache(true);
    }
    if (class_exists('SiteGround_Optimizer\Supercacher\Supercacher')) {
      SiteGround_Optimizer\Supercacher\Supercacher::purge_cache();
    }
    if (isset($GLOBALS['wp_fastest_cache']) && method_exists($GLOBALS['wp_fastest_cache'], 'deleteCache')) {
      $GLOBALS['wp_fastest_cache']->deleteCache(true);
    }
    if (is_callable(array('Swift_Performance_Cache', 'clear_all_cache'))) {
      Swift_Performance_Cache::clear_all_cache();
    }
    if (is_callable(array('Hummingbird\WP_Hummingbird', 'flush_cache'))) {
      Hummingbird\WP_Hummingbird::flush_cache(true, false);
    }
    if (function_exists('rocket_clean_domain')) {
      rocket_clean_domain();
    }
    do_action('cache_enabler_clear_complete_cache');
  } // empty_cache


  /**
     *
     * export_csv
     *
     * @return nothing
     * @author WebFactory Ltd
     *
     */
  public static function export_csv()
  {
    $entries = EPS_Redirects::get_all();
    $filename = sprintf(
      "%s-redirects-export.csv",
      date('Y-m-d')
    );
    if ($entries) {
      header('Content-disposition: attachment; filename=' . $filename);
      header('Content-type: text/csv');

      foreach ($entries as $entry) {
        $csv = array(
          $entry->status,
          $entry->url_from,
          $entry->url_to,
          $entry->count
        );
        echo esc_attr(implode(',', $csv));
        echo "\n";
      }

      die();
    }
  }

  /**
     *
     * _upload
     *
     * This function handles the upload of CSV files, in accordance to the upload method specified.
     *
     * @return html string
     * @author WebFactory Ltd
     *
     */
  private function _upload()
  {
    $new_redirects = array();

    $counter = array(
      'new' => 0,
      'updated' => 0,
      'skipped' => 0,
      'errors' => 0,
      'total' => 0
    );

    $mimes = array(
      'text/csv',
      'text/tsv',
      'text/plain',
      'application/csv',
      'text/comma-separated-values',
      'application/excel',
      'application/vnd.ms-excel',
      'application/vnd.msexcel',
      'text/anytext',
      'application/octet-stream',
      'application/txt'
    );
    ini_set('auto_detect_line_endings', true);

    if (!in_array($_FILES['eps_redirect_upload_file']['type'], $mimes)) {
      $this->add_admin_message(sprintf(
        "WARNING: Not a valid CSV file - the Mime Type '%s' is wrong! No new redirects have been added.",
        $_FILES['eps_redirect_upload_file']['type']
      ), "error");
      return false;
    }

    // open the file.
    if (($handle = fopen($_FILES['eps_redirect_upload_file']['tmp_name'], "r")) !== false) {
      $counter['total'] = 1;
      while (($redirect = fgetcsv($handle, 0, ",")) !== false) {
        $redirect = array_filter($redirect);

        if (empty($redirect)) continue;

        $args = count($redirect);

        if ($args > 4 || $args < 2) {
          // Bad line. Too many/few arguments.
          $this->add_admin_message(
            sprintf(
              "WARNING: Encountered a badly formed entry in your CSV file on line %d (we skipped it).",
              $counter['total']
            ),
            "error"
          );
          $counter['errors']++;
          continue;
        }

        $status     = (isset($redirect[0])) ? esc_attr(strip_tags($redirect[0])) : false;
        $url_from   = (isset($redirect[1])) ? esc_attr(strip_tags($redirect[1])) : false;
        $url_to     = (isset($redirect[2])) ? esc_attr(strip_tags($redirect[2])) : false;
        $count      = (isset($redirect[3])) ? esc_attr(strip_tags($redirect[3])) : false;

        switch (strtolower($status)) {
          case '404':
            $status = 404;
            break;
          case '302':
            $status = 302;
            break;
          case '307':
              $status = 307;
              break;
          case 'off':
          case 'no':
          case 'inactive':
            $status = 'inactive';
            break;
          default:
            $status = 301;
            break;
        }

        // If the user supplied a post_id, is it valid? If so, use it!
        if ($url_to && $post_id = url_to_postid($url_to)) {
          $url_to = $post_id;
        }

        // new redirect!
        $new_redirect = array(
          'id'        => false, // new
          'url_from'  => $url_from,
          'url_to'    => $url_to,
          'type'      => (is_numeric($url_to)) ? 'post' : 'url',
          'status'    => $status,
          'count'     => $count
        );

        array_push($new_redirects, $new_redirect);
        $counter['total']++;
      }
      fclose($handle); // close file.
    }


    if ($new_redirects) {
      $save_redirects = array();
      foreach ($new_redirects as $redirect) {
        // Decide how to handle duplicates:
        switch (strtolower(sanitize_text_field($_POST['eps_redirect_upload_method']))) {
          case 'skip':
            if (!EPS_Redirects::redirect_exists($redirect)) {
              $save_redirects[] = $redirect;
              $counter['new']++;
            } else {
              $counter['skipped']++;
            }
            break;
          case 'update':
            if ($entry = EPS_Redirects::redirect_exists($redirect)) {
              $redirect['id'] = $entry->id;
              $counter['updated']++;
              $save_redirects[] = $redirect;
            } else {
              $save_redirects[] = $redirect;
              $counter['new']++;
            }
            break;
          default:
            $save_redirects[] = $redirect;
            $counter['new']++;
            break;
        }
      }

      if (!empty($save_redirects)) {
        EPS_Redirects::_save_redirects($save_redirects);
      }

      $this->add_admin_message(sprintf(
        "Success: %d New Redirects, %d Updated, %d Skipped, %d Errors. (Attempted to import %d redirects).",
        $counter['new'],
        $counter['updated'],
        $counter['skipped'],
        $counter['errors'],
        $counter['total']
      ), "updated");
    } else {
      $this->add_admin_message("WARNING: Something's up. No new redirects were added, please review your CSV file.", "error");
    }
    ini_set('auto_detect_line_endings', false);
  }



  /**
     *
     * Template Hooks
     *
     * @author WebFactory Ltd
     *
     */
  public static function admin_panel_cache()
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-panel-cache.php');
  }
  public static function admin_panel_donate()
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-panel-donate.php');
  }

  public static function admin_tab_redirects($options)
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-tab-redirects.php');
  }
  public static function admin_tab_404s($options)
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-tab-404s.php');
  }
  public static function admin_tab_support($options)
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-tab-support.php');
  }
  public static function admin_tab_link_scanner($options)
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-tab-link-scanner.php');
  }
  public static function admin_tab_import_export($options)
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-tab-import-export.php');
  }
  public static function admin_tab_error($options)
  {
    include(EPS_REDIRECT_PATH . 'templates/admin-tab-error.php');
  }


  /**
     *
     * CHECK VERSION
     *
     * This function will check the current version and do any fixes required
     *
     * @return string - version number.
     * @author WebFactory Ltd
     *
     */

  public function config($name)
  {
    return (isset($this->config[$name])) ? $this->config[$name] : false;
  }


  public static function is_current_version()
  {
    return version_compare(self::current_version(), EPS_REDIRECT_VERSION, '=') ? true : false; // TODO decouple
  }
  public static function current_version()
  {
    return get_option('eps_redirects_version'); // TODO decouple
  }
  public static function set_current_version($version)
  {
    update_option('eps_redirects_version', $version);
  }


  /**
     *
     * Notices
     *
     * These functions will output a variable containing the admin ajax url for use in javascript.
     *
     * @author WebFactory Ltd
     *
     */
  protected function add_admin_message($message, $code)
  {
    $this->messages[] = array($code => $message);
    add_action('admin_notices', array($this, 'display_admin_messages'));
  }

  public static function display_admin_messages()
  {
    global $EPS_Redirects_Plugin;
    if (is_array($EPS_Redirects_Plugin->messages) && !empty($EPS_Redirects_Plugin->messages)) {
      foreach ($EPS_Redirects_Plugin->messages as $entry) {
        $code = key($entry);
        $message = reset($entry);

        if (!in_array($code, array('error', 'updated'))) {
          $code = 'updated';
        }
        $EPS_Redirects_Plugin->admin_notice($message, $code);
      }
    }
  }
  public function admin_notice($string, $type = "updated")
  {
    printf(
      '<div class="%s"><p>%s</p></div>',
      esc_attr($type),
      wp_kses_post($string)
    );

  }


	public static function protect_from_translation_plugins()
	{
		global $original_request_uri;

		$original_request_uri = strtolower(urldecode($_SERVER['REQUEST_URI']));
	}
}

// Init the plugin.
$EPS_Redirects_Plugin = new EPS_Redirects_Plugin();
