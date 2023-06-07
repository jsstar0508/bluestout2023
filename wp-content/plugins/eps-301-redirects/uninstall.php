<?php
//if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit();
}

global $wpdb;
$redirect_table = $wpdb->prefix . "redirects";
$wpdb->query('DROP TABLE IF EXISTS ' . $redirect_table);

delete_option('eps_pointers');
delete_option('eps_redirects_404_log');
delete_option('301-redirects-notices');
delete_option('eps_redirects_version');
