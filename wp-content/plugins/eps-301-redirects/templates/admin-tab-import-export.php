<?php
/**
 *
 * The Import/Export Tab.
 *
 * The main admin area for the import/export tab.
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



  <div class="eps-panel eps-margin-top">
    <h3>PRO Options</h3>
    <input class="open-301-pro-dialog" data-pro-feature="pro-option-email-reports" type="checkbox" id="pro-email-reports"> <label for="pro-email-reports">Send me daily or weekly email reports about 404 errors &amp; redirects</label><br>
    <input class="open-301-pro-dialog" data-pro-feature="pro-option-typos" type="checkbox" id="pro-typos"> <label for="pro-typos">Automatically fix URL typos without having to create redirect rules</label><br>
    <input class="open-301-pro-dialog" data-pro-feature="pro-option-permalinks" type="checkbox" id="pro-monitor"> <label for="pro-monitor">Monitor permalink changes on posts &amp; pages and automatically create redirect rules so no traffic is lost</label><br>
    <input class="open-301-pro-dialog" data-pro-feature="pro-option-custom-404" type="checkbox" id="pro-custom-404"> <label for="pro-custom-404">Set a custom 404 page from any page</label>
    <p><a href="#" class="open-301-pro-dialog" data-pro-feature="pro-options">WP 301 Redirects PRO</a> offers advanced options to easily fix 2 most overlooked SEO issues - redirections and 404 errors.<br>If you have more than one site, the centralized Dashboard will save you hours of work with its centralized log for all sites.</p>
  </div>

  <div class="eps-panel eps-margin-top">
    <h3>Tools</h3>
    <form method="post" action="">
        <?php wp_nonce_field('eps_redirect_nonce', 'eps_redirect_nonce_submit');   ?>
        <input type="submit" name="eps_redirect_refresh" id="submit" class="button button-secondary" value="Empty Cache" />
         <p class="eps-grey-text">Empty the cache if you're having problems with redirect rules or if dropdowns on "add new rule" are out of date.</p>
    </form>
<br>
    <form method="post" action="">
        <?php wp_nonce_field('eps_redirect_nonce', 'eps_redirect_nonce_submit');   ?>
        <input type="submit" name="eps_delete_rules" id="eps_delete_rules" class="button button-secondary" value="Delete all Redirect Rules" />
         <p class="eps-grey-text">If you have a lot of rules and don't want to delete them one by one, use this tool. Please be carefull. There is NO UNDO.</p>
    </form>

    <br>
    <form method="post" action="">
        <?php wp_nonce_field('eps_redirect_nonce', 'eps_redirect_nonce_submit');   ?>
        <input type="submit" name="eps_reset_stats" id="eps_reset_stats" class="button button-secondary" value="Reset Redirect Rules' Hits" />
         <p class="eps-grey-text">Use this tool to reset the hits count on all redirect rules to zero. Redirect rules are NOT affected by this tool, just the hits count. Please be carefull. There is NO UNDO.</p>
    </form>
  </div>

  <div class="eps-panel eps-margin-top">
    <h3>Import Redirect Rules</h3>
    <form method="post" action="" class="eps-padding" enctype="multipart/form-data">
      <?php wp_nonce_field('eps_redirect_nonce', 'eps_redirect_nonce_submit'); ?>
      <input accept="csv" type="file" name="eps_redirect_upload_file" value="">
      <input type="submit" name="eps_redirect_upload" id="submit" class="button button-secondary" value="Upload CSV" />
      <p>
        <input type="radio" name="eps_redirect_upload_method" value="skip" checked="checked"> Skip Duplicates
        &nbsp;&nbsp;&nbsp;<input type="radio" name="eps_redirect_upload_method" value="update"> Update Duplicates
      </p>

      <p class="eps-grey-text">Supply Columns: <strong>Status</strong> (301,302,307,inactive), <strong>Request URL</strong>, <strong>Redirect
          To</strong> (ID or URL). <a href="<?php echo esc_attr(EPS_REDIRECT_URL . 'misc/example-import.csv'); ?>" target="_blank">Download Example CSV</a></p>
    </form>
  </div>

  <div class="eps-panel eps-margin-top">
    <h3>Export Redirect Rules</h3>
    <form method="post" action="">
      <?php wp_nonce_field('eps_redirect_nonce', 'eps_redirect_nonce_submit');   ?>
      <input type="submit" name="eps_redirect_export" id="submit" class="button button-secondary" value="Export Redirects" />
      <p class="eps-grey-text">Export a backup copy of your redirects.</p>
    </form>
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
