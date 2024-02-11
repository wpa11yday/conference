<?php
/**
 * Register the meta fields.
 * 
 * @package wpcsp
 */

add_action( 'init', 'wpcs_register_meta' );
/**
 * Register the meta fields.
 *
 * @return void
 */
function wpcs_register_meta() {
	register_meta(
		'user',
		'disable_front_end_styles',
		array(
			'type'         => 'boolean',
			'single'       => true,
			'show_in_rest' => true,
		)
	);
}
