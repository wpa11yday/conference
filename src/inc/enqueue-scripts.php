<?php
/**
 * Enqueue Admin Styles 
 */
function wpcsp_pro_admin_enqueue_styles(){
	wp_enqueue_style( 'wp-conference-schedule-pro', plugin_dir_url( __DIR__ ).'assets/css/wp-conference-schedule-pro-admin.css', array(), WPCS_VERSION, 'all' );
}

/**
 * Enqueue Admin Scripts 
 */
function wpcsp_pro_admin_enqueue_scripts(){
	global $post;
	$post_id = is_object($post) ? $post->ID : null;
	wp_enqueue_script( 'wp-conference-schedule-pro', plugin_dir_url( __DIR__ ). 'assets/js/wp-conference-schedule-pro-admin.js', array( 'jquery' ), WPCS_VERSION, false );

	wp_localize_script('wp-conference-schedule-pro', 'ac_script_vars', array(
			'postID' => $post_id
		)
	);
}

/**
 * Enqueue Admin Styles 
 */
function wpcsp_pro_enqueue_styles(){
	wp_enqueue_style( 'dashicons' );
	wp_enqueue_style( 'wp-conference-schedule-pro', plugin_dir_url( __DIR__ ).'assets/css/wp-conference-schedule-pro.css', array(), WPCS_VERSION, 'all' );
}