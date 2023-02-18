<?php

// Registers custom taxonomies to post types.
function wpcs_register_taxonomies() {

	// Labels for tracks.
	$track_labels = array(
		'name'          => __( 'Tracks',         'wp-conference-schedule' ),
		'singular_name' => __( 'Track',          'wp-conference-schedule' ),
		'search_items'  => __( 'Search Tracks',  'wp-conference-schedule' ),
		'popular_items' => __( 'Popular Tracks', 'wp-conference-schedule' ),
		'all_items'     => __( 'All Tracks',     'wp-conference-schedule' ),
		'edit_item'     => __( 'Edit Track',     'wp-conference-schedule' ),
		'update_item'   => __( 'Update Track',   'wp-conference-schedule' ),
		'add_new_item'  => __( 'Add Track',      'wp-conference-schedule' ),
		'new_item_name' => __( 'New Track',      'wp-conference-schedule' ),
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
		'name'          => __( 'Locations',         'wp-conference-schedule' ),
		'singular_name' => __( 'Location',          'wp-conference-schedule' ),
		'search_items'  => __( 'Search Locations',  'wp-conference-schedule' ),
		'popular_items' => __( 'Popular Locations', 'wp-conference-schedule' ),
		'all_items'     => __( 'All Locations',     'wp-conference-schedule' ),
		'edit_item'     => __( 'Edit Location',     'wp-conference-schedule' ),
		'update_item'   => __( 'Update Location',   'wp-conference-schedule' ),
		'add_new_item'  => __( 'Add Location',      'wp-conference-schedule' ),
		'new_item_name' => __( 'New Location',      'wp-conference-schedule' ),
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


function wpcsp_register_conference_taxonomies() {
    
    // Labels for speaker groups.
	$speakergrouplabels = array(
		'name'          => __( 'Groups',         'wpcsp' ),
		'singular_name' => __( 'Group',          'wpcsp' ),
		'search_items'  => __( 'Search Groups',  'wpcsp' ),
		'popular_items' => __( 'Popular Groups', 'wpcsp' ),
		'all_items'     => __( 'All Groups',     'wpcsp' ),
		'edit_item'     => __( 'Edit Group',     'wpcsp' ),
		'update_item'   => __( 'Update Group',   'wpcsp' ),
		'add_new_item'  => __( 'Add Group',      'wpcsp' ),
		'new_item_name' => __( 'New Group',      'wpcsp' ),
	);

	// Register speaker groups taxonomy
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
		'name'          => __( 'Sponsor Levels',         'rwc-conference-schedule' ),
		'singular_name' => __( 'Sponsor Level',          'rwc-conference-schedule' ),
		'search_items'  => __( 'Search Sponsor Levels',  'rwc-conference-schedule' ),
		'popular_items' => __( 'Popular Sponsor Levels', 'rwc-conference-schedule' ),
		'all_items'     => __( 'All Sponsor Levels',     'rwc-conference-schedule' ),
		'edit_item'     => __( 'Edit Sponsor Level',     'rwc-conference-schedule' ),
		'update_item'   => __( 'Update Sponsor Level',   'rwc-conference-schedule' ),
		'add_new_item'  => __( 'Add Sponsor Level',      'rwc-conference-schedule' ),
		'new_item_name' => __( 'New Sponsor Level',      'rwc-conference-schedule' ),
	);

	// Register sponsor level taxonomy
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
		'name'          => __( 'Tags',         'rwc-conference-schedule' ),
		'singular_name' => __( 'Tag',          'rwc-conference-schedule' ),
		'search_items'  => __( 'Search Tags',  'rwc-conference-schedule' ),
		'popular_items' => __( 'Popular Tags', 'rwc-conference-schedule' ),
		'all_items'     => __( 'All Tags',     'rwc-conference-schedule' ),
		'edit_item'     => __( 'Edit Tag',     'rwc-conference-schedule' ),
		'update_item'   => __( 'Update Tag',   'rwc-conference-schedule' ),
		'add_new_item'  => __( 'Add Tag',      'rwc-conference-schedule' ),
		'new_item_name' => __( 'New Tag',      'rwc-conference-schedule' ),
	);

	// Register session tagstaxonomy
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