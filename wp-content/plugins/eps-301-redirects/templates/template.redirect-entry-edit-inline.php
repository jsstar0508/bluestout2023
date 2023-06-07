<?php
/**
 *
 * Outputs the whole inline entry edit html.
 *
 *
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 *
 */


// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}
?>
<tr id="eps-redirect-edit">
    <td colspan="5">
        <form id="eps-redirect-save" method="post" action="">
            <table class="eps-table">
                <tr class="id-<?php echo ($redirect_id) ? esc_attr($redirect_id) : 'new'; ?>">
                    <?php include(EPS_REDIRECT_PATH . 'templates/template.redirect-entry-edit.php'); ?>
                    <td class="redirect-actions">
                        <?php if ($redirect_id) { ?>
                        <a class="button eps-redirect-cancel">Close</a>
                        <?php 
                      } ?>
                        <?php wp_nonce_field('eps_redirect_nonce', 'eps_redirect_nonce_submit');   ?>
                        <input type="submit" name="eps_redirect_submit" class="button button-primary eps-redirect-edit" value="Save">
                    </td>
                </tr>
            </table>
        </form>
    </td>
</tr> 
