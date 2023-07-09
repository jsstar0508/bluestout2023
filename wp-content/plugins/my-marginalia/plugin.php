<?php
/**
 * Plugin Name: My Marginalia
 * Plugin URI: https://github.com/davidfcarr/marginalia
 * Description: Add comments or links in the margins, related to one or more blocks of content. Marginal comments can be aligned left or right from your main content, and the comment you're commenting on can be a mix of paragraphs, headings, images, and other media. Uses Gutenberg blocks and "inner blocks." Created with the help of Create Guten Block, https://github.com/ahmadawais/create-guten-block/
 * Author: davidfcarr
 * Author URI: https://www.carrcommunications.com/
 * Version: 1.0.6
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Initializer.
 */
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
