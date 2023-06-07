<?php
/**
 *
 * The Redirects Tab.
 *
 * The main admin area for the redirects tab.
 *
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

global $EPS_Redirects_Plugin;
?>

<div class="wrap">
  <?php do_action('eps_redirects_admin_head'); ?>

  <table id="eps-redirect-entries" class="eps-table eps-table-striped">
    <tr>
      <th class="redirect-small"> <?php eps_get_ordered_filter('id', 'ID'); ?> </th>
      <th> <?php eps_get_ordered_filter('url_from', 'Redirect From'); ?> </th>
      <th> <?php eps_get_ordered_filter('url_to', 'Redirect To'); ?> </th>
      <th class="redirect-small"> <?php eps_get_ordered_filter('count', 'Hits'); ?> </th>
      <th class="redirect-actions">Actions</th>
    </tr>

    <tr id="eps-redirect-add" style="display:none;">
      <td style=" text-align: center;" colspan="5"><a href="#" class="button button-primary" id="eps-redirect-new">Add New Redirect Rule</a></td>
    </tr>

    <?php
        EPS_Redirects::wp_kses_wf(EPS_Redirects::get_inline_edit_entry());
        EPS_Redirects::wp_kses_wf(EPS_Redirects::list_redirects());
        ?>
  </table>

  <div class="right">
    <?php do_action('eps_redirects_panels_right'); ?>
  </div>
  <div class="left">
    <?php do_action('eps_redirects_panels_left'); ?>
  </div>
</div>
