<?php
/**
 * Blocks Initializer
 *
 * Enqueue CSS/JS of all the blocks.
 *
 * @since   1.0.0
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Enqueue Gutenberg block assets for both frontend + backend.
 *
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function marginalia_cgb_block_assets() { // phpcs:ignore
	// Styles.
	wp_enqueue_style(
		'marginalia-cgb-style-css', // Handle.
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), // Block style CSS.
		array( 'wp-editor' ), filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.style.build.css' ) // Version: File modification time.
	);
}

// Hook: Frontend assets.
add_action( 'enqueue_block_assets', 'marginalia_cgb_block_assets' );

/**
 * Enqueue Gutenberg block assets for backend editor.
 *
 * @uses {wp-blocks} for block type registration & related functions.
 * @uses {wp-element} for WP Element abstraction — structure of blocks.
 * @uses {wp-i18n} to internationalize the block's text.
 * @uses {wp-editor} for WP editor styles.
 * @since 1.0.0
 */
function marginalia_cgb_editor_assets() { // phpcs:ignore
	// Scripts.

	$defaults = get_option('marginalia_default');
	if(empty($defaults['width']))
		{
			$defaults['width'] = '8';
			$defaults['position'] = 'left';
		}
	wp_register_script('marginalia-cgb-block-js', // Handle.
	plugins_url( '/dist/blocks.build.js', dirname( __FILE__ ) ), // Block.build.js: We register the block here. Built with Webpack.
	array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), // Dependencies, defined above.
	filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.build.js' ), // Version: File modification time.
	true // Enqueue the script in the footer.
);
	wp_localize_script('marginalia-cgb-block-js','marginalia',$defaults);
	wp_enqueue_script('marginalia-cgb-block-js');

	// Styles.
	wp_enqueue_style(
		'marginalia-cgb-block-editor-css', // Handle.
		plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), // Block editor CSS.
		array( 'wp-edit-blocks' ), // Dependency to include the CSS after it.
		filemtime( plugin_dir_path( __DIR__ ) . 'dist/blocks.editor.build.css' ) // Version: File modification time.
	);
}

// Hook: Editor assets.
add_action( 'enqueue_block_editor_assets', 'marginalia_cgb_editor_assets' );

add_action('admin_menu','marginalia_options_menu');
function marginalia_options_menu () {
	add_options_page('Marginalia','Marginalia','manage_options','marginalia_options','marginalia_options');
}

function marginalia_options () {
	if(isset($_POST['default']))
		{
		$default = $_POST['default'];
		update_option('marginalia_default',$default);
		}
	else
		$default = get_option('marginalia_default');
	if(empty($default)){
		$default["width"] = '8';
		$default["position"] = 'left';
	}
	$width = '';
	for($i=5; $i < 21; $i++)
		$width .= sprintf('<option value="%d" %s>%s</option>',$i,(($i == $default['width']) ? ' selected="selected" ': ''), $i);
	$position = sprintf('<option value="%s">%s</option>',$default["position"],$default["position"]);

	printf('<h2>Marginalia Options</h2><form method="post" action="%s">
	<p>Margin Width: <select type="text" name="default[width]">%s</select></p>
	<p>Position: <select name="default[position]" >
	%s
	<option value="left">left</option>
	<option value="right">right</option>
	</select></p>	
	<p><button>Save</button></p></form>',admin_url('options-general.php?page=marginalia_options'),$width,$position);
}
?>