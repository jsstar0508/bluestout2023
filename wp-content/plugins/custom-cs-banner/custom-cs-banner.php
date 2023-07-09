<?php
/**
 * Plugin Name:       Custom CS Banner
 * Description:       Block for Case Studies banner of bluestout.com
 * Requires at least: 6.1
 * Requires PHP:      7.0
 * Version:           0.1.0
 * Author:            Jiang Rujing
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-cs-banner
 *
 * @package           customcsbanner
 */

/**
 * Registers the block using the metadata loaded from the `block.json` file.
 * Behind the scenes, it registers also all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://developer.wordpress.org/reference/functions/register_block_type/
 */
function customcsbanner_custom_cs_banner_block_init() {
	register_block_type( __DIR__ . '/build' );
}
add_action( 'init', 'customcsbanner_custom_cs_banner_block_init' );
