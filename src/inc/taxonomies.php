<?php
/**
 * Register the taxonomies.
 *
 * @package wpcsp
 */

/**
 * Registers custom taxonomies to post types.
 *
 * @return void
 */
function wpcs_register_taxonomies() {

	// Labels for tracks.
	$track_labels = array(
		'name'          => __( 'Tracks', 'wpa-conference' ),
		'singular_name' => __( 'Track', 'wpa-conference' ),
		'search_items'  => __( 'Search Tracks', 'wpa-conference' ),
		'popular_items' => __( 'Popular Tracks', 'wpa-conference' ),
		'all_items'     => __( 'All Tracks', 'wpa-conference' ),
		'edit_item'     => __( 'Edit Track', 'wpa-conference' ),
		'update_item'   => __( 'Update Track', 'wpa-conference' ),
		'add_new_item'  => __( 'Add Track', 'wpa-conference' ),
		'new_item_name' => __( 'New Track', 'wpa-conference' ),
	);

	// Register the Tracks taxonomy.
	register_taxonomy(
		'wpcs_track',
		'wpcs_session',
		array(
			'labels'       => $track_labels,
			'rewrite'      => array( 'slug' => 'track' ),
			'query_var'    => 'track',
			'hierarchical' => true,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'rest_base'    => 'session_track',
		)
	);

	// Labels for locations.
	$location_labels = array(
		'name'          => __( 'Locations', 'wpa-conference' ),
		'singular_name' => __( 'Location', 'wpa-conference' ),
		'search_items'  => __( 'Search Locations', 'wpa-conference' ),
		'popular_items' => __( 'Popular Locations', 'wpa-conference' ),
		'all_items'     => __( 'All Locations', 'wpa-conference' ),
		'edit_item'     => __( 'Edit Location', 'wpa-conference' ),
		'update_item'   => __( 'Update Location', 'wpa-conference' ),
		'add_new_item'  => __( 'Add Location', 'wpa-conference' ),
		'new_item_name' => __( 'New Location', 'wpa-conference' ),
	);

	// Register the Locations taxonomy.
	register_taxonomy(
		'wpcs_location',
		'wpcs_session',
		array(
			'labels'       => $location_labels,
			'rewrite'      => array( 'slug' => 'location' ),
			'query_var'    => 'location',
			'hierarchical' => true,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'rest_base'    => 'session_location',
		)
	);

}

add_action( 'init', 'wpcs_register_taxonomies' );

/**
 * Register conference taxonomies.
 *
 * @return void
 */
function wpcsp_register_conference_taxonomies() {

	// Labels for speaker groups.
	$speakergrouplabels = array(
		'name'          => __( 'Groups', 'wpa-conference' ),
		'singular_name' => __( 'Group', 'wpa-conference' ),
		'search_items'  => __( 'Search Groups', 'wpa-conference' ),
		'popular_items' => __( 'Popular Groups', 'wpa-conference' ),
		'all_items'     => __( 'All Groups', 'wpa-conference' ),
		'edit_item'     => __( 'Edit Group', 'wpa-conference' ),
		'update_item'   => __( 'Update Group', 'wpa-conference' ),
		'add_new_item'  => __( 'Add Group', 'wpa-conference' ),
		'new_item_name' => __( 'New Group', 'wpa-conference' ),
	);

	// Register speaker groups taxonomy.
	register_taxonomy(
		'wpcsp_speaker_level',
		'wpcsp_speaker',
		array(
			'labels'       => $speakergrouplabels,
			'rewrite'      => array( 'slug' => 'speaker_group' ),
			'query_var'    => 'speaker_level',
			'hierarchical' => true,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'rest_base'    => 'speaker_level',
		)
	);

	// Labels for sponsor levels.
	$sponsorlevellabels = array(
		'name'          => __( 'Sponsor Levels', 'wpa-conference' ),
		'singular_name' => __( 'Sponsor Level', 'wpa-conference' ),
		'search_items'  => __( 'Search Sponsor Levels', 'wpa-conference' ),
		'popular_items' => __( 'Popular Sponsor Levels', 'wpa-conference' ),
		'all_items'     => __( 'All Sponsor Levels', 'wpa-conference' ),
		'edit_item'     => __( 'Edit Sponsor Level', 'wpa-conference' ),
		'update_item'   => __( 'Update Sponsor Level', 'wpa-conference' ),
		'add_new_item'  => __( 'Add Sponsor Level', 'wpa-conference' ),
		'new_item_name' => __( 'New Sponsor Level', 'wpa-conference' ),
	);

	// Register sponsor level taxonomy.
	register_taxonomy(
		'wpcsp_sponsor_level',
		'wpcsp_sponsor',
		array(
			'labels'       => $sponsorlevellabels,
			'rewrite'      => array( 'slug' => 'sponsor_level' ),
			'query_var'    => 'sponsor_level',
			'hierarchical' => true,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'rest_base'    => 'sponsor_level',
		)
	);

	// Labels for session tags.
	$sponsorlevellabels = array(
		'name'          => __( 'Tags', 'wpa-conference' ),
		'singular_name' => __( 'Tag', 'wpa-conference' ),
		'search_items'  => __( 'Search Tags', 'wpa-conference' ),
		'popular_items' => __( 'Popular Tags', 'wpa-conference' ),
		'all_items'     => __( 'All Tags', 'wpa-conference' ),
		'edit_item'     => __( 'Edit Tag', 'wpa-conference' ),
		'update_item'   => __( 'Update Tag', 'wpa-conference' ),
		'add_new_item'  => __( 'Add Tag', 'wpa-conference' ),
		'new_item_name' => __( 'New Tag', 'wpa-conference' ),
	);

	// Register session tags taxonomy.
	register_taxonomy(
		'wpcs_session_tag',
		'wpcs_session',
		array(
			'labels'       => $sponsorlevellabels,
			'rewrite'      => array( 'slug' => 'sponsor_level' ),
			'query_var'    => 'session_tag',
			'hierarchical' => false,
			'public'       => true,
			'show_ui'      => true,
			'show_in_rest' => true,
			'rest_base'    => 'session_tag',
		)
	);

}
add_action( 'init', 'wpcsp_register_conference_taxonomies' );
