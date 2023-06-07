<?php
/**
 *
 * The 404 Tab.
 *
 * The main admin area for the 404 tab.
 *
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

require EPS_REDIRECT_PATH . '/libs/UserAgentParser.php';
?>

<div class="wrap">
  <?php do_action('eps_redirects_admin_head'); ?>

  <div class="eps-panel eps-margin-top group">
  <?php
  $log = get_option('eps_redirects_404_log', array());
  if (!sizeof($log)) {
    echo '<p>You currently don\'t have any data in the 404 error log. That means that you either just installed the plugin, or that you never had a 404 error happen which is <b>awesome</b>!</p>';
  } else {
    echo '<div class="log-ad-box">Need a more detailed 404 error log? With more data, more insights, per-day stats &amp; an easier way to create redirect rules from 404 errors? Want to have a centralized log for all your websites in one place? <a href="#" class="open-301-pro-dialog pro-feature" data-pro-feature="404-log-banner">Upgrade to WP 301 Redirects PRO.</a></div>';
    echo '<table class="striped widefat">';
    echo '<tr>';
    echo '<th>Date &amp; Time <span class="dashicons dashicons-arrow-down"></span></th>';
    echo '<th>Target URL</th>';
    echo '<th>User Device</th>';
    echo '<th>User Location</th>';
    echo '<th>Referal URL</th>';
    echo '</tr>';

    foreach ($log as $l) {
      $ua = \epsdonatj\UserAgent\parse_user_agent($l['user_agent']);
      $agent = trim(@$ua['platform'] . ' ' . @$ua['browser']);
      if (empty($agent)) {
        $agent = '<i>unknown</i>';
      }
      echo '<tr>';
      echo '<td nowrap><abbr title="' . esc_attr(date(get_option('date_format'), $l['timestamp']) . ' @ ' . date(get_option('time_format'), $l['timestamp']))  . '">' . esc_attr(human_time_diff(current_time('timestamp'), $l['timestamp'])) . ' ago</abbr></td>';
      echo '<td><a target="_blank" href="' . esc_attr($l['url']) . '">' . esc_attr($l['url']) . '</a></td>';
      echo '<td nowrap>' . esc_attr($agent) . '</td>';
      echo '<td nowrap><a href="#" class="open-301-pro-dialog pro-feature" data-pro-feature="404-log-user-location">Available in PRO</a></td>';
      echo '<td nowrap><a href="#" class="open-301-pro-dialog pro-feature" data-pro-feature="404-log-referral-url">Available in PRO</a></td>';
      echo '</tr>';
    } // foreach

    echo '</table>';

    echo '<p><br><i>By default, the log is limited to the last fifty (chronologically) 404 errors. This is a safe number that ensures the log works on all sites and doesn\'t slow anything down. ';
    echo 'The code imposes no limits on the log size and you can easily overwrite the default limit by using the <code>eps_301_max_404_logs</code> filter.</i> Details are available in the <a href="https://wordpress.org/plugins/eps-301-redirects/#faq-header" target="_blank">FAQ</a>.</p>';
    echo '<p>If your site gets hundreds and thousands of 404 errors a day we suggesting upgrading to <a href="#" class="open-301-pro-dialog pro-feature" data-pro-feature="404-log-footer">WP 301 Redirects PRO</a> as it automatically fixes 404 errors caused by URL typos, provides a more robust log that can handle tens of thousands of entries, and offers more tools to manage 404 errors.</p>';
  } // if 404
  ?>

  </div>

  <div class="right">
    <?php do_action('eps_redirects_panels_right'); ?>
  </div>
  <div class="left">
    <?php
    // do_action('eps_redirects_panels_left');
        ?>
  </div>
</div>
