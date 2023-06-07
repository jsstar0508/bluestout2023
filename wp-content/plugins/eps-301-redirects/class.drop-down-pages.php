<?php
/**
 * Drop Down Pages
 *
 * Will return a heirarchical list of pages in a key->value pair.
 *
 * @since 2.1.0
 *
 * @param array|string $args Optional. Override default arguments.
 * @return string or HTML content, if not displaying.
 */

// include only file
if (!defined('ABSPATH')) {
  die('Do not open this file directly.');
}

if (!function_exists('eps_dropdown_pages')) {
  function eps_dropdown_pages($args = '')
  {
    $defaults = array(
      'posts_per_page'   => -1,
      'offset'           => 0,
      'category'         => '',
      'orderby'          => 'post_title',
      'order'            => 'DESC',
      'include'          => '',
      'exclude'          => '',
      'meta_key'         => '',
      'meta_value'       => '',
      'post_type'        => 'post',
      'post_mime_type'   => '',
      'post_parent'      => '',
      'post_status'      => 'publish',
      'suppress_filters' => true,
      'depth'            => 5
    );



    $r = wp_parse_args($args, $defaults);
    extract($r, EXTR_SKIP);

    $pages = get_posts($r);

    if (empty($pages)) return array();

    return array_flip(eps_walk_page_dropdown_tree($pages, $depth, $r));
  }

  /**
 * Retrieve HTML dropdown (select) content for page list.
 *
 * @uses Walker_PageDropdown to create HTML dropdown content.
 * @since 2.1.0
 * @see Walker_PageDropdown::walk() for parameters and return description.
 */
  function eps_walk_page_dropdown_tree()
  {
    $args = func_get_args();
    $walker = (empty($args[2]['walker'])) ? new EPS_Walker_PageDropdown : $args[2]['walker'];
    return call_user_func_array(array($walker, 'walk'), $args);
  }

  /**
 * Create an array of pages.
 *
 * @package WordPress
 * @since 2.1.0
 * @uses Walker
 */
  class EPS_Walker_PageDropdown extends Walker
  {
    var $tree_type = 'page';
    var $db_fields = array('parent' => 'post_parent', 'id' => 'ID');

    function start_el(&$output, $object, $depth = 0, $args = array(), $current_object_id = 0)
    {
      if (!is_array($output)) {
        $output = array();
      }

      $pad = str_repeat('&nbsp;', $depth * 3);
      $output[$object->ID] = $pad . esc_html(apply_filters('list_pages', $object->post_title, $object));
    }
  }
}
