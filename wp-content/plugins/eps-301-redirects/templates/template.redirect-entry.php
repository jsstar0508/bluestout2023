<?php
/**
 *
 * The Redirect List Entry Template.
 *
 *
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

global $EPS_Redirects_Plugin;
$query_args = array('page' => $EPS_Redirects_Plugin->config('page_slug'), 'delete_redirect' => esc_attr($redirect->id));

?>
<tr class="redirect-entry <?php echo esc_attr($redirect->status); ?> id-<?php echo esc_attr($redirect->id); ?>"
  data-id="<?php echo esc_attr($redirect->id); ?>" data-status="<?php echo esc_attr($redirect->status); ?>">
  <td>
    <p class="eps-grey-text eps-text-center eps-small"><?php echo esc_attr($redirect->id); ?></p>
  </td>
  <td>
    <a target="_blank" class="eps-url" href="<?php echo esc_attr(get_bloginfo('url')); ?>/<?php echo esc_attr(stripslashes($dfrom)); ?>"
      title="<?php echo esc_attr(get_bloginfo('url')); ?>/<?php echo esc_attr(stripslashes($dfrom)); ?>">
      <span class="eps-url-root eps-url-startcap">
        <?php 
        $rstatus = ($redirect->status == 'inactive') ? 'OFF' : $redirect->status;
        echo esc_attr($rstatus); 
        ?></span><span
        class="eps-url-root"><?php echo esc_attr(get_bloginfo('url')); ?>/</span><span class="eps-url-fragment eps-url-endcap"><?php echo esc_attr(stripslashes($dfrom)); ?></span>
    </a>
  </td>
  <td>
    <?php echo esc_attr(eps_get_destination($redirect)); ?>
  </td>
  <td class="redirect-hits" nowrap><strong><?php echo esc_attr(number_format($redirect->count)); ?></strong> <a class="open-301-pro-dialog pro-feature" data-pro-feature="redirect-rules-chart-icon-<?php echo esc_attr($i); ?>" href="#"><span class="dashicons dashicons-chart-area"></span></a></td>
  <td class="redirect-actions">
    <a class="button eps-redirect-edit" href="#eps-redirect-edit" data-id="<?php echo esc_attr($redirect->id); ?>">Edit</a>
    <a class="button eps-redirect-remove" href="<?php echo esc_attr(add_query_arg($query_args, admin_url('/options-general.php'))); ?>"
      data-id="<?php echo esc_attr($redirect->id); ?>">Delete</a>
  </td>
</tr>
<?php
if ($i == 5) {
  EPS_Redirects::wp_kses_wf('<tr class="row-banner"><td colspan="5"><a href="#" class="open-301-pro-dialog pro-feature" data-pro-feature="redirect-rules-banner-1"><p><b>Are you tired of adding so many redirect rules one-by-one for each URL?</b><br>
  <b>WP 301 Redirects PRO</b> automatically fixes URL typos, has advanced URL matching rules, and watcher over permalink changes so you don\'t have to write so many rules.</p></a></td></tr>');
}

if ($i == 14) {
  EPS_Redirects::wp_kses_wf('<tr class="row-banner"><td colspan="5"><a href="#" class="open-301-pro-dialog pro-feature" data-pro-feature="redirect-rules-banner-2"><p><b>Need a better way to organize &amp; monitor your redirect rules?</b><br>
  <b>WP 301 Redirects PRO</b> makes it easy to tag &amp; search rules so it\'s always easy to find them. And with advanced stats for redirects &amp; 404 errors you\'ll always know what\'s going on with your traffic.</p></a></td></tr>');
}
?>
