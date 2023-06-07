<?php
/**
 *
 * EPS 301 REDIRECTS
 *
 *
 * eps-form-elements.php
 *
 * Responsible for some of the more 'complex' form elements such as post type dropdowns.
 *
 *
 *
 * @package    EPS 301 Redirects
 * @author     WebFactory Ltd
 * @version    2.1.0
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

/**
 *
 * GET_TYPE_SELECT
 *
 * This function will initialze a series of html form elements so a user can narrow down their redirect destination.
 *
 * @return html string
 * @author WebFactory Ltd
 *
 */

function eps_get_selector($redirect = false)
{
  $current_post = (isset($redirect->url_to) && is_numeric($redirect->url_to)) ? get_post(intval($redirect->url_to)) : null;

  $post_types = get_post_types(array(
    'public'                => true
  ), 'objects');

  $html = eps_get_type_select($post_types, $current_post);

  // Get all the post type select boxes.
  foreach ($post_types as $post_type) {
    $html .= eps_get_post_type_selects($post_type->name, $current_post);
  }

  // Get the term select box.
  $html .= eps_get_term_archive_select();

  // The default input, javascript will populate this input with the final URL for submission.
  $html .= '<input class="eps-url-input select-eps-url-input"
                     type="text"
                     name="redirect[url_to][]"
                     value="' . (isset($redirect->url_to) ? stripslashes(esc_attr($redirect->url_to)) : null) . '"
                     placeholder="' . get_bloginfo('url') . '" ' . (!isset($redirect->type) || ((isset($redirect->type) && $redirect->type != 'post')) ? null : ' style="display:none;"') .
    '" /><br><small>Remember: all external URLs have to start with <code>https://</code></small>';

  return $html;
}


/**
 *
 * GET_DESTINATION
 *
 * This function will output the formatted destination string.
 *
 * @return html string
 * @author WebFactory Ltd
 *
 */
function eps_get_destination($redirect = false)
{
  if (isset($redirect->url_to)) {
    if (is_numeric($redirect->url_to)) {
      // This redirect points to a post
      if (get_permalink($redirect->url_to)) {
        ?>
<a target="_blank" class="eps-url" href="<?php echo esc_url(get_permalink($redirect->url_to)); ?>" title="<?php echo esc_url(get_permalink($redirect->url_to)); ?>">
  <span class="eps-url-root eps-url-startcap"><?php echo esc_attr(strtoupper(get_post_type($redirect->url_to))); ?></span><span class="eps-url-root">ID:
    <?php echo esc_url($redirect->url_to); ?> </span><span class="eps-url-fragment eps-url-endcap "><?php echo esc_attr(get_the_title($redirect->url_to)); ?> </span>
</a>
<?php

} else {
  ?>
<span class="eps-url eps-warning">
  <span class="eps-url-root eps-url-startcap">ID: <?php echo esc_attr($redirect->url_to); ?> </span>
  <span class="eps-url-fragment eps-url-endcap ">DOES NOT EXIST</span>
</span>
<?php

}
} else {
  // This is redirect points to a url
  ?>
<a target="_blank" class="eps-url" href="<?php echo esc_url(stripslashes($redirect->url_to)); ?>" title="<?php echo esc_attr(stripslashes($redirect->url_to)); ?>">
  <span class="eps-url-root eps-url-startcap">URL:</span><span
    class="eps-url-fragment eps-url-endcap "><?php echo esc_attr(stripslashes($redirect->url_to)); ?></span>
</a>
<?php

}
} else {
  echo '<span class="eps-warning">Invalid Destination URL</span>';
}
}



/**
 *
 * GET_TYPE_SELECT
 *
 * This function will output the available destination types.
 *
 * @return html string
 * @author WebFactory Ltd
 *
 */
function eps_get_type_select($post_types, $current_post = false)
{
  $html = '<select class="type-select eps-small-select">';
  $html .= '<option value="eps-url-input">Custom</option>';

  foreach ($post_types as $post_type) {
    $html .= '<option value="' . $post_type->name . '" ' . (isset($current_post) && $current_post->post_type == $post_type->name  ? 'selected="selected"' : null) . '>' . $post_type->labels->singular_name . '</option>';
  }
  $html .= '<option value="term">Term Archive</option>';
  $html .= '</select>';
  return $html;
}


/**
 *
 * GET_POST_TYPE_SELECT
 *
 * This function will output the available post types.
 *
 * @return html string
 * @author WebFactory Ltd
 *
 */
function eps_get_post_type_selects($post_type, $current_post = false)
{
  // Start the select.

  $html = '<select class="select-' . $post_type . ' url-selector eps-small-select" ' . ((isset($current_post) && $current_post->post_type == $post_type) ? null : 'style="display:none;"') . '>';
  $html .= '<option value="">...</option>';

  if (false === ($options = get_transient('post_type_cache_' . $post_type))) {
    $options = eps_dropdown_pages(array('post_type' => $post_type));
    set_transient('post_type_cache_' . $post_type, $options, HOUR_IN_SECONDS);
  }

  foreach ($options as $option => $value) {
    $html .= '<option value="' . $value . '" ' . (isset($current_post) && $current_post->ID == $value ? 'selected="selected"' : null) . ' >' .  ucwords($option)  . '</option>';
  }
  $html .= '</select>';

  return $html;
}

/**
 *
 * GET_TERM_ARCHIVE_SELECT
 *
 * This function will output a select box with all the taxonomies and terms.
 *
 * @return html string
 * @author WebFactory Ltd
 *
 */
function eps_get_term_archive_select()
{
  $taxonomies = get_taxonomies('', 'objects');

  if (!$taxonomies) return false;

  // Start the select.
  $html = '<select class="select-term url-selector eps-small-select" style="display:none;">';
  $html .= '<option value="" selected default>...</option>';

  // Loop through all taxonomies.
  foreach ($taxonomies as $tax) {
    $terms = get_terms($tax->name, array('hide_empty' => false)); // show empty terms.
    $html .= '<option value="' . $tax->name . '" disabled>' . $tax->labels->singular_name . '</option>';

    // Loop through all terms in this taxonomy and insert them as options.
    foreach ($terms as $term)
      $html .= '<option value="' . get_term_link($term) . '">&nbsp;&nbsp;- ' . $term->name . '</option>';
  }
  $html .= '</select>';
  return $html;
}

function eps_get_ordered_filter($field, $label, $classes = array())
{
  global $EPS_Redirects_Plugin;
  $nextOrder = 'asc';
  $arrow = false;

  if (isset($_GET['orderby']) &&  sanitize_text_field($_GET['orderby']) ==  $field) {
    $arrow = '&darr;';

    if (isset($_GET['order']) && sanitize_text_field($_GET['order']) != 'desc') {
      $nextOrder = 'desc';
      $arrow = '&uarr;';
    }
  }

  printf(
    '<a class="%s" href="%s">%s %s</a>',
    esc_attr(implode(' ', $classes)),
    esc_url($EPS_Redirects_Plugin->admin_url(array('orderby' => $field, 'order' => $nextOrder))),
    esc_attr($label),
    esc_attr($arrow)
  );
}
