<?php
/**
 * Outputs the edit form for a given $redirect_id. If $redirect_id is not set, assume this is a new redirect form.
 *
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

$redirect = self::get_redirect($redirect_id);
?>
<td>
    <input type="hidden" type="text" name="redirect[id][]" value="<?php echo ($redirect_id) ? esc_attr($redirect_id) : ''; ?>">

    <select name="redirect[status][]" class="eps-small-select">
        <option default value="301" <?php echo ($redirect && $redirect->status == '301') ? 'selected="selected"' : null; ?>>301</option>
        <option value="302" <?php echo ($redirect && $redirect->status == '302') ? 'selected="selected"' : null; ?>>302</option>
        <option value="307" <?php echo ($redirect && $redirect->status == '307') ? 'selected="selected"' : null; ?>>307</option>
        <option value="inactive" <?php echo ($redirect && $redirect->status == 'inactive') ? 'selected="selected"' : null; ?>>Off</option>
    </select>

    <div class="eps-url"><span class="eps-url-root"><?php bloginfo('url'); ?>/&nbsp;</span></div>
    <input class="eps-url-input" type="text" name="redirect[url_from][]" value="<?php echo ($redirect) ? esc_attr(stripslashes($redirect->url_from)) : ''; ?>"><br><small>Need wildcard support for matching multiple URLs? Check out the <a href="#" class="open-301-pro-dialog" data-pro-feature="new-rule-wildcard">PRO version.</small>
</td>
<td>
    <?php EPS_Redirects::wp_kses_wf(eps_get_selector($redirect)); ?>
</td>
