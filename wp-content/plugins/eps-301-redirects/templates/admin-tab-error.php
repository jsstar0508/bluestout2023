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
?>


<div class="wrap">
    <?php do_action('eps_redirects_admin_head'); ?>

    <div class="eps-notice eps-warning">
        <?php EPS_Redirects::wp_kses_wf($options['description']); ?>
    </div>


    <div class="right">
        <?php do_action('eps_redirects_panels_right'); ?>
    </div>
    <div class="left">
        <?php do_action('eps_redirects_panels_left'); ?>
    </div>
</div> 
