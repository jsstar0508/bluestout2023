<?php
 /*
Plugin Name: 301 Redirects
Description: Easily create and manage redirect rules, and view 404 error log.
Version: 2.73
Author: WebFactory Ltd
Author URI: https://www.webfactoryltd.com/
Plugin URI: https://wp301redirects.com/
Text Domain: eps-301-redirects
Requires at least: 3.6
Tested up to: 6.2
Requires PHP: 5.2

  Copyright 2015 - 2023  WebFactory Ltd  (email: 301redirects@webfactoryltd.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

if (!defined('WF301_PLUGIN_FILE')) {

  define('EPS_REDIRECT_PATH',       plugin_dir_path(__FILE__));
  define('EPS_REDIRECT_URL',        plugins_url() . '/eps-301-redirects/');
  define('EPS_REDIRECT_PRO',        false);

  $plugin_data = get_file_data(__FILE__, array('version' => 'Version'), 'plugin');
  define('EPS_REDIRECT_VERSION', $plugin_data['version']);

  include(EPS_REDIRECT_PATH . 'eps-form-elements.php');
  include(EPS_REDIRECT_PATH . 'class.drop-down-pages.php');
  include(EPS_REDIRECT_PATH . 'libs/eps-plugin-options.php');
  include(EPS_REDIRECT_PATH . 'plugin.php');

  require_once 'wf-flyout/wf-flyout.php';
  new wf_flyout(__FILE__);

  register_activation_hook(__FILE__, array('EPS_Redirects_Plugin', '_activation'));
  register_deactivation_hook(__FILE__, array('EPS_Redirects_Plugin', '_deactivation'));
  add_action('plugins_loaded', array('EPS_Redirects_Plugin', 'protect_from_translation_plugins'), -9999);
  
  class EPS_Redirects
  {
    /**
     *
     * Constructor
     *
     * Add some actions.
     *
     */
    public function __construct()
    {
      global $EPS_Redirects_Plugin;

      if (is_admin()) {

        if (isset($_GET['page']) && sanitize_text_field($_GET['page']) == $EPS_Redirects_Plugin->config('page_slug')) {
          add_action('admin_init', array($this, 'clear_cache'));
        }

        add_action('wp_ajax_eps_redirect_get_new_entry',            array($this, 'ajax_get_entry'));
        add_action('wp_ajax_eps_redirect_delete_entry',             array($this, 'ajax_eps_delete_entry'));
        add_action('wp_ajax_eps_redirect_get_inline_edit_entry',    array($this, 'ajax_get_inline_edit_entry'));
        add_action('wp_ajax_eps_redirect_save',                     array($this, 'ajax_save_redirect'));
        add_action('wp_ajax_eps_dismiss_pointer', array($this, 'dismiss_pointer_ajax'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_action_links'));
        add_filter('admin_footer_text', array($this, 'admin_footer_text'));
        add_action('wp_dashboard_setup', array($this, 'add_widget'));
      } else {
        if (defined('WP_CLI') && WP_CLI) {
        } else {
          add_action('init', array($this, 'do_redirect'), 1); // Priority 1 for redirects.
          add_action('template_redirect', array($this, 'check_404'), 1);
        }
      }
    }

    function plugin_action_links($links)
    {
      $pro_link = '<a href="' . admin_url('options-general.php?page=eps_redirects#open-pro-dialog') . '" title="' . __('Get PRO', 'eps-301-redirects') . '"><b>' . __('Get PRO', 'eps-301-redirects') . '</b></a>';

      $settings_link = '<a href="' . admin_url('options-general.php?page=eps_redirects') . '" title="' . __('Manage Redirects', 'eps-301-redirects') . '">' . __('Manage Redirects', 'eps-301-redirects') . '</a>';

      array_unshift($links, $settings_link);
      $links[] = $pro_link;

      return $links;
    } // plugin_action_links


  // permanently dismiss a pointer
  function dismiss_pointer_ajax() {
    check_ajax_referer('eps_dismiss_pointer');

    $pointers = get_option('eps_pointers');
    $pointer = trim(sanitize_text_field($_POST['pointer_name']));

    if (empty($pointers) || empty($pointers[$pointer])) {
      wp_send_json_error();
    }

    unset($pointers[$pointer]);
    update_option('eps_pointers', $pointers);

    wp_send_json_success();
  } // dismiss_pointer_ajax

  // add widget to dashboard
  function add_widget() {
    if (current_user_can('manage_options')) {
      add_meta_box('wp301_404_errors', '404 Error Log', array($this, 'widget_content'), 'dashboard', 'side', 'high');
    }
  } // add_widget

  static function wp_kses_wf($html)
    {
        add_filter('safe_style_css', function ($styles) {
            $styles_wf = array(
                'text-align',
                'margin',
                'color',
                'float',
                'border',
                'background',
                'background-color',
                'border-bottom',
                'border-bottom-color',
                'border-bottom-style',
                'border-bottom-width',
                'border-collapse',
                'border-color',
                'border-left',
                'border-left-color',
                'border-left-style',
                'border-left-width',
                'border-right',
                'border-right-color',
                'border-right-style',
                'border-right-width',
                'border-spacing',
                'border-style',
                'border-top',
                'border-top-color',
                'border-top-style',
                'border-top-width',
                'border-width',
                'caption-side',
                'clear',
                'cursor',
                'direction',
                'font',
                'font-family',
                'font-size',
                'font-style',
                'font-variant',
                'font-weight',
                'height',
                'letter-spacing',
                'line-height',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'overflow',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width',
                'display',
            );

            foreach ($styles_wf as $style_wf) {
                $styles[] = $style_wf;
            }
            return $styles;
        });

        $allowed_tags = wp_kses_allowed_html('post');
        $allowed_tags['input'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'size' => true,
            'disabled' => true
        );

        $allowed_tags['textarea'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'cols' => true,
            'rows' => true,
            'disabled' => true
        );

        $allowed_tags['select'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'data-*' => true,
            'multiple' => true,
            'disabled' => true
        );

        $allowed_tags['option'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'selected' => true,
            'data-*' => true
        );
        $allowed_tags['optgroup'] = array(
            'type' => true,
            'style' => true,
            'class' => true,
            'id' => true,
            'checked' => true,
            'disabled' => true,
            'name' => true,
            'size' => true,
            'placeholder' => true,
            'value' => true,
            'selected' => true,
            'data-*' => true,
            'label' => true
        );

        $allowed_tags['a'] = array(
            'href' => true,
            'data-*' => true,
            'class' => true,
            'style' => true,
            'id' => true,
            'target' => true,
            'data-*' => true,
            'role' => true,
            'aria-controls' => true,
            'aria-selected' => true,
            'disabled' => true
        );

        $allowed_tags['div'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'role' => true,
            'aria-labelledby' => true,
            'value' => true,
            'aria-modal' => true,
            'tabindex' => true
        );

        $allowed_tags['li'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'role' => true,
            'aria-labelledby' => true,
            'value' => true,
            'aria-modal' => true,
            'tabindex' => true
        );

        $allowed_tags['span'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'data-*' => true,
            'aria-hidden' => true
        );

        $allowed_tags['form'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'method' => true,
            'action' => true,
            'data-*' => true
        );

        $allowed_tags['style'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'method' => true,
            'action' => true,
            'data-*' => true
        );

        $allowed_tags['p'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'method' => true,
            'action' => true,
            'data-*' => true
        );

        $allowed_tags['br'] = array(
            'style' => true,
            'class' => true,
            'id' => true,
            'method' => true,
            'action' => true,
            'data-*' => true
        );
        
        echo wp_kses($html, $allowed_tags);

        add_filter('safe_style_css', function ($styles) {

            $styles_wf = array(
                'text-align',
                'margin',
                'color',
                'float',
                'border',
                'background',
                'background-color',
                'border-bottom',
                'border-bottom-color',
                'border-bottom-style',
                'border-bottom-width',
                'border-collapse',
                'border-color',
                'border-left',
                'border-left-color',
                'border-left-style',
                'border-left-width',
                'border-right',
                'border-right-color',
                'border-right-style',
                'border-right-width',
                'border-spacing',
                'border-style',
                'border-top',
                'border-top-color',
                'border-top-style',
                'border-top-width',
                'border-width',
                'caption-side',
                'clear',
                'cursor',
                'direction',
                'font',
                'font-family',
                'font-size',
                'font-style',
                'font-variant',
                'font-weight',
                'height',
                'letter-spacing',
                'line-height',
                'margin-bottom',
                'margin-left',
                'margin-right',
                'margin-top',
                'overflow',
                'padding',
                'padding-bottom',
                'padding-left',
                'padding-right',
                'padding-top',
                'text-decoration',
                'text-indent',
                'vertical-align',
                'width'
            );

            foreach ($styles_wf as $style_wf) {
                if (($key = array_search($style_wf, $styles)) !== false) {
                    unset($styles[$key]);
                }
            }
            return $styles;
        });
    }

  // render widget
  function widget_content() {
    require EPS_REDIRECT_PATH . '/libs/UserAgentParser.php';

    $log = get_option('eps_redirects_404_log', array());
    $widget_html = '';
    if (!sizeof($log)) {
      $widget_html .= '<p>You currently don\'t have any data in the 404 error log. That means that you either just installed the plugin, or that you never had a 404 error happen which is <b>awesome 🚀</b>!</p>';
      $widget_html .= '<p>Don\'t like seeing an empty error log? Or just want to see see if the log works? Open any <a target="_blank" title="Open an nonexistent URL to see if the 404 error log works" href="' . home_url('/nonexistent/url/') . '">nonexistent URL</a> and then reload this page.</p>';
    } else {
      $widget_html .= '<style>#wp301_404_errors .inside { padding: 0; margin: 0; }';
      $widget_html .= '#wp301_404_errors table td { max-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }';
      $widget_html .= '#wp301_404_errors table th { font-weight: 500; }';
      $widget_html .= '#wp301_404_errors table { border-left: none; border-right: none; border-top: none; }';
      $widget_html .= '#wp301_404_errors p { padding: 0 12px 12px 12px; }';
      $widget_html .= '#wp301_404_errors .dashicons { opacity: 0.75; font-size: 17px; line-height: 17px; width: 17px; height: 17px;vertical-align: bottom; }</style>';
      $widget_html .= '<table class="striped widefat">';
      $widget_html .= '<tr>';
      $widget_html .= '<th>Date &amp;<br>Time <span class="dashicons dashicons-arrow-down"></span></th>';
      $widget_html .= '<th>Target URL</th>';
      $widget_html .= '<th>User Device</th>';
      $widget_html .= '</tr>';

      $i = 1;
      foreach ($log as $l) {
        $ua = \epsdonatj\UserAgent\parse_user_agent($l['user_agent']);
        $agent = trim(@$ua['platform'] . ' ' . @$ua['browser']);
        if (empty($agent)) {
          $agent = '<i>unknown</i>';
        }
        $widget_html .= '<tr>';
        $widget_html .= '<td nowrap><abbr title="' . date(get_option('date_format'), $l['timestamp']) . ' @ ' . date(get_option('time_format'), $l['timestamp'])  . '">' . human_time_diff(current_time('timestamp'), $l['timestamp']) . ' ago</abbr></td>';
        $widget_html .= '<td><a title="Open target URL in a new tab" target="_blank" href="' . $l['url'] . '">' . $l['url'] . '</a> <span class="dashicons dashicons-external"></span></td>';
        $widget_html .= '<td>' . $agent . '</td>';
        $widget_html .= '</tr>';
        $i++;
        if ($i >= 6) {
          break;
        }
      } // foreach
      $widget_html .= '</table>';

      $widget_html .= '<p>View the entire <a href="' . admin_url('options-general.php?page=eps_redirects&tab=404s') . '">404 error log</a> in the 301 Redirects plugin or <a href="' . admin_url('options-general.php?page=eps_redirects') . '">create new redirect rules</a> to fix 404 errors.</p>';
    }
    self::wp_kses_wf($widget_html);
  } // widget_content


    /**
     *
     * DO_REDIRECT
     *
     * This function will redirect the user if it can resolve that this url request has a redirect.
     *
     * @author WebFactory Ltd
     *
     */
    public function do_redirect()
    {
      if (is_admin()) return false;
      $redirects = self::get_redirects(true); // True for only active redirects.

      if (empty($redirects)) return false; // No redirects.

      // Get current url
      $url_request = self::get_url();

      $query_string = explode('?', $url_request);
      $query_string = (isset($query_string[1])) ? $query_string[1] : false;


      foreach ($redirects as $redirect) {
        $from = urldecode(html_entity_decode($redirect->url_from));

        if ($redirect->status != 'inactive' && rtrim(trim($url_request), '/')  === self::format_from_url(trim($from))) {

          // Match, this needs to be redirected
          // increment this hit counter.
          self::increment_field($redirect->id, 'count');

          if ($redirect->status == '301') {
            header('HTTP/1.1 301 Moved Permanently');
          } elseif ($redirect->status == '302') {
            header('HTTP/1.1 302 Moved Temporarily');
          } elseif ($redirect->status == '307') {
            header('HTTP/1.1 307 Temporary Redirect');
          }

          $to = ($redirect->type == "url" && !is_numeric($redirect->url_to)) ? urldecode(html_entity_decode($redirect->url_to)) : get_permalink($redirect->url_to);
          $to = ($query_string) ? $to . "?" . $query_string : $to;

          header('Location: ' . $to, true, (int)$redirect->status);
          exit();
        }
      }
    }

    // additional powered by text in admin footer; only on 301 page
  function admin_footer_text($text) {
    if (!$this->is_plugin_page()) {
      return $text;
    }

    $text = '<i><a href="https://wp301redirects.com/?ref=free-eps-301-redirects" title="Visit WP 301 Redirects site for more info" target="_blank">WP 301 Redirects</a> v' . EPS_REDIRECT_VERSION . ' by <a href="https://www.webfactoryltd.com/" title="Visit our site to get more great plugins" target="_blank">WebFactory Ltd</a>. Please <a target="_blank" href="https://wordpress.org/support/plugin/eps-301-redirects/reviews/#new-post" title="Rate the plugin">rate the plugin <span>★★★★★</span></a> to help us spread the word. Thank you!</i>';

    return $text;
  } // admin_footer_text


  // test if we're on plugin's page
  function is_plugin_page() {
    $current_screen = get_current_screen();

    if ($current_screen->id == 'settings_page_eps_redirects') {
      return true;
    } else {
      return false;
    }
  } // is_plugin_page

    /**
     *
     * FORMAT FROM URL
     *
     * Will construct and format the from url from what we have in storage.
     *
     * @return url string
     * @author WebFactory Ltd
     *
     */
    private function format_from_url($string)
    {
      $complete = rtrim(home_url(), '/') . '/' . $string;

      list($uprotocol, $uempty, $uhost, $from) = explode('/', $complete, 4);
      $from = '/' . $from;
      $from = strtolower(rtrim($from,'/'));
      return strtolower(rtrim($from, '/'));
    }

    /**
     *
     * GET_URL
     *
     * This function returns the current url.
     *
     * @return URL string
     * @author WebFactory Ltd
     *
     */
    public static function get_url()
    {
	    global $original_request_uri;

	    return $original_request_uri;
    }


    /**
     *
     * PARSE SERIAL ARRAY
     *
     * A necessary data parser to change the POST arrays into save-able data.
     *
     * @return array of redirects
     * @author WebFactory Ltd
     *
     */
    public static function _parse_serial_array($array)
    {
      $new_redirects = array();
      $total = count($array['url_from']);

      for ($i = 0; $i < $total; $i++) {

        if (empty($array['url_to'][$i]) || empty($array['url_from'][$i])) continue;
        $new_redirects[] = array(
          'id'        => isset($array['id'][$i]) ? $array['id'][$i] : null,
          'url_from'  => sanitize_text_field($array['url_from'][$i]),
          'url_to'    => sanitize_text_field($array['url_to'][$i]),
          'type'      => (is_numeric($array['url_to'][$i])) ? 'post' : 'url',
          'status'    => isset($array['status'][$i]) ? $array['status'][$i] : '301'
        );
      }
      return $new_redirects;
    }

    /**
     *
     * AJAX SAVE REDIRECTS
     *
     * Saves a single redirectvia ajax.
     *
     * TODO: Maybe refactor this to reduce the number of queries.
     *
     * @return nothing
     * @author WebFactory Ltd
     */
    public function ajax_save_redirect()
    {

      check_ajax_referer('eps_301_save_redirect');

      if (!current_user_can('manage_options')) {
        wp_die('You are not allowed to run this action.');
      }

      $update = array(
        'id'        => ($_POST['id']) ? intval($_POST['id']) : false,
        'url_from'  => sanitize_text_field($_POST['url_from']), // remove the $root from the url if supplied, and a leading /
        'url_to'    => sanitize_text_field($_POST['url_to']),
        'type'      => (is_numeric($_POST['url_to']) ? 'post' : 'url'),
        'status'    => sanitize_text_field($_POST['status'])
      );

      $ids = self::_save_redirects(array($update));

      $updated_id = $ids[0]; // we expect only one returned id.

      // now get the new entry...
      $redirect = self::get_redirect($updated_id);
      $html = '';

      ob_start();
      $dfrom = urldecode($redirect->url_from);
      $dto   = urldecode($redirect->url_to);
      $i=0;
      include(EPS_REDIRECT_PATH . 'templates/template.redirect-entry.php');
      $html = ob_get_contents();
      ob_end_clean();
      echo json_encode(array(
        'html'          => $html,
        'redirect_id'   => $updated_id
      ));

      exit();
    }

    /**
     *
     * redirect_exists
     *
     * Checks if a redirect exists for a given url_from
     *
     * @param $redirect
     * @return bool
     */
    public static function redirect_exists($redirect)
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $query = $wpdb->prepare("SELECT id FROM $table_name WHERE url_from = %s", $redirect['url_from']);
      $result = $wpdb->get_row($query);
      return ($result) ? $result : false;
    }

    /**
     *
     * SAVE REDIRECTS
     *
     * Saves the array of redirects.
     *
     * TODO: Maybe refactor this to reduce the number of queries.
     *
     * @return nothing
     * @author WebFactory Ltd
     */
    public static function _save_redirects($array)
    {
      if (empty($array)) return false;
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $root = get_bloginfo('url') . '/';
      $ids = array();


      foreach ($array as $redirect) {

        if (!isset($redirect['id']) || empty($redirect['id'])) {

          // If the user supplied a post_id, is it valid? If so, use it!
          if ($post_id = url_to_postid($redirect['url_to'])) {
            $redirect['url_to'] = $post_id;
          }

          // new
          $entry = array(
            'url_from'      => trim(ltrim(str_replace($root, null, $redirect['url_from']), '/')),
            'url_to'        => trim($redirect['url_to']),
            'type'          => trim($redirect['type']),
            'status'        => trim($redirect['status'])
          );
          // Add count if exists:
          if (isset($redirect['count']) && is_numeric($redirect['count'])) $entry['count'] = $redirect['count'];

          $wpdb->insert(
            $table_name,
            $entry
          );
          $ids[] = $wpdb->insert_id;
        } else {
          // existing
          $entry = array(
            'url_from'  => trim(ltrim(str_replace($root, null, $redirect['url_from']), '/')),
            'url_to'    => trim($redirect['url_to']),
            'type'      => trim($redirect['type']),
            'status'    => trim($redirect['status'])
          );
          // Add count if exists:
          if (isset($redirect['count']) && is_numeric($redirect['count'])) $entry['count'] = $redirect['count'];

          $wpdb->update(
            $table_name,
            $entry,
            array('id' => $redirect['id'])
          );

          $ids[] = $redirect['id'];
        }
      }
      return $ids; // return array of affected ids.
    }
    /**
     *
     * GET REDIRECTS
     *
     * Gets the redirects. Can be switched to return Active Only redirects.
     *
     * @return array of redirects
     * @author WebFactory Ltd
     *
     */
    public static function get_redirects($active_only = false)
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $orderby = (isset($_GET['orderby']))  ?  esc_sql(sanitize_text_field($_GET['orderby'])) : 'id';
      $order = (isset($_GET['order']))    ? esc_sql(sanitize_text_field($_GET['order'])) : 'desc';
      $orderby = (in_array(strtolower($orderby), array('id', 'url_from', 'url_to', 'count'))) ? $orderby : 'id';
      $order = (in_array(strtolower($order), array('asc', 'desc'))) ? $order : 'desc';

      $query = $wpdb->prepare("SELECT *
            FROM $table_name
            WHERE 1 = %d AND status != 404 " . (($active_only) ? "AND status != 'inactive'" : null) . "
            ORDER BY $orderby $order", 1);
      //die($query);
      $results = $wpdb->get_results($query);

      return $results;
    }

    public static function get_all()
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";

      $results = $wpdb->get_results(
        "SELECT * FROM $table_name ORDER BY id DESC"
      );

      return $results;
    }

    public static function get_redirect($redirect_id)
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $query = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d LIMIT 1", intval($redirect_id));
      $results = $wpdb->get_results($query);
      return array_shift($results);
    }

    /**
     *
     * INCREMENT FIELD
     *
     * Add +1 to the specified field for a given id
     *
     * @return the result
     * @author WebFactory Ltd
     *
     */
    public static function increment_field($id, $field)
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $id = intval($id);
      $results = $wpdb->query("UPDATE $table_name SET count = count + 1 WHERE id = $id");
      return $results;
    }

    /**
     *
     * DO_INPUTS
     *
     * This function will list out all the current entries.
     *
     * @return html string
     * @author WebFactory Ltd
     *
     */
    public static function list_redirects()
    {
      $redirects = self::get_redirects();
      $html = '';
      if (empty($redirects)) return false;
      ob_start();
      $i = 1;
      foreach ($redirects as $redirect) {
        $dfrom = urldecode($redirect->url_from);
        $dto   = urldecode($redirect->url_to);
        include(EPS_REDIRECT_PATH . 'templates/template.redirect-entry.php');
        $i++;
      }
      $html = ob_get_contents();
      ob_end_clean();
      return $html;
    }

    /**
     *
     * DELETE_ENTRY
     *
     * This function will remove an entry.
     *
     * @return nothing
     * @author WebFactory Ltd
     *
     */
    public static function ajax_eps_delete_entry()
    {
      check_ajax_referer('eps_301_delete_entry');

      if (!current_user_can('manage_options')) {
        wp_die('You are not allowed to run this action.');
      }

      if (!isset($_POST['id'])) exit();

      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $results = $wpdb->delete($table_name, array('ID' => intval($_POST['id'])));
      echo json_encode(array('id' => intval($_POST['id'])));
      exit();
    }
    private static function _delete($id)
    {
      global $wpdb;
      $table_name = $wpdb->prefix . "redirects";
      $wpdb->delete($table_name, array('ID' => intval($id)));
    }

    /**
     *
     * GET_ENTRY
     * AJAX_GET_ENTRY
     * GET_EDIT_ENTRY
     *
     * This function will return a blank row ready for user input.
     *
     * @return html string
     * @author WebFactory Ltd
     *
     */
    public static function get_entry($redirect_id = false)
    {
      ob_start();
      ?>
<tr class="id-<?php echo ($redirect_id) ? esc_html($redirect_id) : 'new'; ?>">
  <?php include(EPS_REDIRECT_PATH . 'templates/template.redirect-entry-edit.php'); ?>
</tr>
<?php
$html = ob_get_contents();
ob_end_clean();
return $html;
}

public static function get_inline_edit_entry($redirect_id = false)
{
  include(EPS_REDIRECT_PATH . 'templates/template.redirect-entry-edit-inline.php');
}


public static function ajax_get_inline_edit_entry()
{
  check_ajax_referer('eps_301_get_inline_edit_entry');

  if (!current_user_can('manage_options')) {
    wp_die('You are not allowed to run this action.');
  }

  $redirect_id = isset($_REQUEST['redirect_id']) ? intval($_REQUEST['redirect_id']) : false;

  ob_start();
  self::get_inline_edit_entry($redirect_id);
  $html = ob_get_contents();
  ob_end_clean();
  echo json_encode(array(
    'html' => $html,
    'redirect_id' => $redirect_id
  ));
  exit();
}


public static function ajax_get_entry()
{
  check_ajax_referer('eps_301_get_entry');

  if (!current_user_can('manage_options')) {
    wp_die('You are not allowed to run this action.');
  }

  self::wp_kses_wf(self::get_entry());
  exit();
}

public function clear_cache()
{
  header("Cache-Control: no-cache, must-revalidate");
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Content-Type: application/xml; charset=utf-8");
}


public static function check_404()
{
  if (!is_404()) {
    return;
  }

  $log404 = get_option('eps_redirects_404_log', array());

  if (!is_array($log404)) {
    $log404 = array();
  }

  $last['timestamp'] = current_time('timestamp');
  $last['url'] = @strip_tags($_SERVER['REQUEST_URI']);
  $last['user_agent'] = @strip_tags($_SERVER['HTTP_USER_AGENT']);
  array_unshift($log404, $last);

  $max = abs(apply_filters('eps_301_max_404_logs', 50));
  $log404 = array_slice($log404, 0, $max);

  update_option('eps_redirects_404_log', $log404);
} // check_404
} // EPS_redirects


/**
 * Outputs an object or array in a readable form.
 *
 * @return void
 * @param $string = the object to prettify; Typically a string.
 * @author WebFactory Ltd
 */
if (!function_exists('eps_prettify')) {
  function eps_prettify($string)
  {
    return ucwords(str_replace("_", " ", $string));
  }
}

if (!function_exists('eps_view')) {
  function eps_view($object)
  {
    echo '<pre>';
    print_r($object);
    echo '</pre>';
  }
}

// Run the plugin.
$EPS_Redirects = new EPS_Redirects();
} else {
    add_action('admin_notices', 'eps_redirects_pro_conflict');
    function eps_redirects_pro_conflict() {
      $deactivate = 'plugins.php?action=deactivate&plugin=eps-301-redirects/eps-301-redirects.php&plugin_status=all&paged=1';
      $deactivate = wp_nonce_url($deactivate, 'deactivate-plugin_eps-301-redirects/eps-301-redirects.php');
      printf(
        '<div class="%s"><p>%s</p></div>',
        "error",
        '<b>WARNING</b>: <a href="' . admin_url($deactivate) . '">Deactivate</a> the 301 Redirects free plugin. PRO version is active. You can\'t use both at the same time.'
      );
    }
}
