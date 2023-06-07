<?php
$config = array();

$config['plugin_screen'] = 'settings_page_eps_redirects';
$config['icon_border'] = '1px solid #FF6246';
$config['icon_right'] = '40px';
$config['icon_bottom'] = '40px';
$config['icon_image'] = '301-logo.png';
$config['icon_padding'] = '6px';
$config['icon_size'] = '55px';
$config['menu_accent_color'] = '#FF6246';
$config['custom_css'] = '#wf-flyout .ucp-icon .wff-icon img { max-width: 70%; } #wf-flyout .ucp-icon .wff-icon { line-height: 57px; } #wf-flyout .wp301-icon .wff-icon img { max-width: 70%; } #wf-flyout .wp301-icon .wff-icon { line-height: 57px; }';

$config['menu_items'] = array(
  array('href' => '#', 'label' => 'Get WP 301 Redirects PRO Lifetime deal for the price of a 1-year license', 'icon' => '301-logo.png', 'class' => 'open-301-pro-dialog accent wp301-icon', 'data' => 'data-pro-feature="flyout"'),
  array('href' => 'https://wpreset.com/?ref=wff-wp-reset', 'target' => '_blank', 'label' => 'Get WP Reset PRO with 50% off', 'icon' => 'wp-reset.png'),
  array('href' => 'https://underconstructionpage.com/?ref=wff-eps-301-redirects&coupon=welcome', 'target' => '_blank', 'label' => 'Create the perfect Under Construction Page', 'icon' => 'ucp.png', 'class' => 'ucp-icon'),
  array('href' => 'https://wpsticky.com/?ref=wff-eps-301-redirects', 'target' => '_blank', 'label' => 'Make a menu sticky with WP Sticky', 'icon' => 'dashicons-admin-post'),
  array('href' => 'https://wordpress.org/support/plugin/eps-301-redirects/reviews/?filter=5#new-post', 'target' => '_blank', 'label' => 'Rate the Plugin', 'icon' => 'dashicons-thumbs-up'),
  array('href' => 'https://wordpress.org/support/plugin/eps-301-redirects/#new-post', 'target' => '_blank', 'label' => 'Get Support', 'icon' => 'dashicons-sos'),
);
