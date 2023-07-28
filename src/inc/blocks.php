<?php
/**
 * Wp Block Editor functions.
 *
 * @package wpcsp
 */

/**
 * Enqueue block editor assets.
 *
 * @return void
 */
function wpcs_enqueue_block_assets() {
	$blockPath = 'assets/js/blocks/index-min.js';

	wp_enqueue_script(
		'wpcs/blocks',
		trailingslashit( plugin_dir_url( PLUGIN_FILE_URL ) ) . $blockPath,
		array( 'wp-plugins', 'wp-blocks', 'wp-element', 'wp-i18n' ),
		filemtime( trailingslashit( WPCS_DIR ) . $blockPath ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'wpcs_enqueue_block_assets' );
