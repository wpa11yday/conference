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
	$version = WPCS_VERSION;
	$version = ( str_contains( home_url(), 'staging.wpaccessibility.day' ) ) ? $version . '-' . wp_rand( 1000, 10000 ) : $version;
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'wp-conference-schedule-pro', plugin_dir_url( __DIR__ ) . 'assets/css/wp-conference-schedule-pro.css', array(), $version, 'all' );
}
