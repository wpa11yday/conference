<?php
/**
 * Enqueue the scripts.
 *
 * @package wpcsp
 */

/**
 * Enqueue Admin Styles
 */
function wpcsp_pro_enqueue_styles() {
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'wp-conference-schedule-pro', plugin_dir_url( __DIR__ ) . 'assets/css/wp-conference-schedule-pro.css', array(), WPCS_VERSION, 'all' );
}
