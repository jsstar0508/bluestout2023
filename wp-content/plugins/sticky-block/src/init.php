<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function sticky_block_assets() { 
	wp_register_script(
		'sticky_block-editor-js', 
		plugins_url( 'dist/blocks.build.js', dirname( __FILE__ ) ), 
		array( 'wp-blocks', 'wp-i18n', 'wp-element', 'wp-editor' ), 
		null, 
		true 
	);

	wp_register_style(
		'sticky_block-css', 
		plugins_url( 'dist/blocks.style.build.css', dirname( __FILE__ ) ), 
		array( 'wp-editor' ), 
		null 
	);

	wp_register_style(
		'sticky_block-editor-css', 
		plugins_url( 'dist/blocks.editor.build.css', dirname( __FILE__ ) ), 
		array( 'wp-edit-blocks' ), 
		null 
	);

	wp_localize_script(
		'sticky_block-editor-js',
		'cgbGlobal', 
		[
			'pluginDirPath' => plugin_dir_path( __DIR__ ),
			'pluginDirUrl'  => plugin_dir_url( __DIR__ ),
		]
	);	

	register_block_type(
		'senff/sticky-block', array(			
			'editor_script' => 'sticky_block-editor-js',
			'style'         => 'sticky_block-css',			
			'editor_style'  => 'sticky_block-editor-css',
		)
	);
}

function sticky_block_frontend() {
	wp_register_script(
		'sticky_block-js', 
		plugins_url('dist/sticky-block.min.js', dirname( __FILE__ )), 
		array( 'jquery' ), 
		"1.0",
		true
		);
		wp_enqueue_script('sticky_block-js');
	}

add_action( 'init', 'sticky_block_assets' );
add_action('wp_enqueue_scripts', 'sticky_block_frontend');