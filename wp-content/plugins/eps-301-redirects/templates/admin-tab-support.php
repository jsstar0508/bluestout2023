<?php
/**
 *
 * The Support Tab.
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
?>

<div class="wrap">
    <?php do_action('eps_redirects_admin_head'); ?>

    <div class="eps-panel eps-margin-top group">
        <h1>Support</h1><br>
        <ul class="plain-list">
            <li>Support for the free version is available through plugin's <a href="https://wordpress.org/support/plugin/eps-301-redirects/" target="_blank">WP.org forum</a></li>
            <li>For <a href="#" class="open-301-pro-dialog" data-pro-feature="support">PRO users</a>, our priority support is available via email or via the <a href="https://wp301redirects.com/contact/" target="_blank">contact form</a></li>
            <li>Please send comments, questions, bugs and feature requests on the <a href="https://wordpress.org/support/plugin/eps-301-redirects/" target="_blank">forum</a> too</li>
            <li>Make sure you check out the <a href="https://wordpress.org/plugins/eps-301-redirects/#faq-header" target="_blank">FAQ</a></li>
            <li><a href="https://docs.wp301redirects.com/" target="_blank">PRO documentation</a></li>
            <li><a href="https://trello.com/b/dSf4gcyz/wp-301-redirects-public-roadmap" target="_blank">Public roadmap</a> - vote for new features and suggest your ideas</li>
        </ul>
    </div>

    <div class="right">
        <?php  ?>
    </div>
    <div class="left">
    </div>
</div>
