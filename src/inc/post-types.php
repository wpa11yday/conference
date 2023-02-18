<?php

register_deactivation_hook( PLUGIN_FILE_URL, 'flush_rewrite_rules' );
register_activation_hook( PLUGIN_FILE_URL, 'wpcs_flush_rewrites' ); 
/**
 * Flush rewrite rules on activation
 */
function wpcs_flush_rewrites() {
	// call your CPT registration function here (it should also be hooked into 'init')
	wpcs_register_post_types();
	flush_rewrite_rules();
}

// Registers the custom post types, runs during init.
add_action( 'init', 'wpcs_register_post_types' );
function wpcs_register_post_types() {

	// Speaker post type labels.
	$speakerlabels = array(
		'name'               => __( 'People',                   'wpcsp' ),
		'singular_name'      => __( 'Person',                    'wpcsp' ),
		'add_new'            => __( 'Add New',                    'wpcsp' ),
		'add_new_item'       => __( 'Create New Person',         'wpcsp' ),
		'edit'               => __( 'Edit',                       'wpcsp' ),
		'edit_item'          => __( 'Edit Person',               'wpcsp' ),
		'new_item'           => __( 'New Person',                'wpcsp' ),
		'view'               => __( 'View Person',               'wpcsp' ),
		'view_item'          => __( 'View Person',               'wpcsp' ),
		'search_items'       => __( 'Search People',            'wpcsp' ),
		'not_found'          => __( 'No people found',          'wpcsp' ),
		'not_found_in_trash' => __( 'No people found in Trash', 'wpcsp' ),
		'parent_item_colon'  => __( 'Parent Person:',            'wpcsp' ),
	);

	// Register speaker post type.
	register_post_type(
		'wpcsp_speaker',
		array(
			'labels'          => $speakerlabels,
			'rewrite'         => array( 'slug' => 'speakers' ),
			'supports'        => array( 'title', 'editor', 'revisions', 'thumbnail', 'page-attributes', 'excerpt' ),
			'menu_position'   => 20,
			'public'          => true,
			'publicly_queryable' => true,
			'show_ui'         => true,
			'can_export'      => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'query_var'       => true,
			'menu_icon'       => 'dashicons-groups',
			'show_in_rest'    => true,
			'rest_base'       => 'speakers',
			'has_archive' 	  => false,
		)
	);

	// Sponsor post type labels.
	$spnsorlabels = array(
		'name'               => __( 'Sponsors',                   'wpcsp' ),
		'singular_name'      => __( 'Sponsor',                    'wpcsp' ),
		'add_new'            => __( 'Add New',                    'wpcsp' ),
		'add_new_item'       => __( 'Create New Sponsor',         'wpcsp' ),
		'edit'               => __( 'Edit',                       'wpcsp' ),
		'edit_item'          => __( 'Edit Sponsor',               'wpcsp' ),
		'new_item'           => __( 'New Sponsor',                'wpcsp' ),
		'view'               => __( 'View Sponsor',               'wpcsp' ),
		'view_item'          => __( 'View Sponsor',               'wpcsp' ),
		'search_items'       => __( 'Search Sponsors',            'wpcsp' ),
		'not_found'          => __( 'No sponsors found',          'wpcsp' ),
		'not_found_in_trash' => __( 'No sponsors found in Trash', 'wpcsp' ),
		'parent_item_colon'  => __( 'Parent Sponsor:',            'wpcsp' ),
	);

	// Register sponsor post type.
	register_post_type(
		'wpcsp_sponsor',
		array(
			'labels'          => $spnsorlabels,
			'rewrite'         => array( 'slug' => 'sponsors', 'with_front' => false, ),
			'supports'        => array( 'title', 'editor', 'revisions', 'thumbnail'),
			'menu_position'   => 21,
			'public'          => true,
			'show_ui'         => true,
			'can_export'      => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'query_var'       => true,
			'menu_icon'       => 'dashicons-heart',
			'show_in_rest'    => true,
			'rest_base'       => 'sponsors',
		)
	);


	// Session post type labels.
	$sessionlabels = array(
		'name'               => __( 'Sessions',                   'wp-conference-schedule' ),
		'singular_name'      => __( 'Session',                    'wp-conference-schedule' ),
		'add_new'            => __( 'Add New',                    'wp-conference-schedule' ),
		'add_new_item'       => __( 'Create New Session',         'wp-conference-schedule' ),
		'edit'               => __( 'Edit',                       'wp-conference-schedule' ),
		'edit_item'          => __( 'Edit Session',               'wp-conference-schedule' ),
		'new_item'           => __( 'New Session',                'wp-conference-schedule' ),
		'view'               => __( 'View Session',               'wp-conference-schedule' ),
		'view_item'          => __( 'View Session',               'wp-conference-schedule' ),
		'search_items'       => __( 'Search Sessions',            'wp-conference-schedule' ),
		'not_found'          => __( 'No sessions found',          'wp-conference-schedule' ),
		'not_found_in_trash' => __( 'No sessions found in Trash', 'wp-conference-schedule' ),
		'parent_item_colon'  => __( 'Parent Session:',            'wp-conference-schedule' ),
	);

	// Register session post type.
	register_post_type(
		'wpcs_session',
		array(
			'labels'          => $sessionlabels,
			'rewrite'         => array( 'slug' => 'sessions', 'with_front' => false, ),
			'supports'        => array( 'title', 'editor', 'author', 'revisions', 'thumbnail', 'custom-fields' ),
			'menu_position'   => 21,
			'public'          => true,
			'show_ui'         => true,
			'can_export'      => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'query_var'       => true,
			'menu_icon'       => 'dashicons-schedule',
			'show_in_rest'    => true,
			'rest_base'       => 'sessions',
		)
	);
	
}

// Change CPT title text
add_action( 'gettext', 'wpcs_change_title_text' );
function wpcs_change_title_text( $translation ) {
	global $post;
	if( isset( $post ) ) {
		switch( $post->post_type ){
			case 'wpcs_session' :
				if( $translation == 'Add title' ) return 'Session Title';
			break;
		}
	}
	return $translation;
}

// Add CPTs to Dashboad At A Glance Metabox
add_action( 'dashboard_glance_items', 'wpcs_cpt_at_glance' );
function wpcs_cpt_at_glance() {
	$args = array(
			'public' => true,
			'_builtin' => false
	);
	$output = 'object';
	$operator = 'and';

	$post_types = get_post_types( $args, $output, $operator );
	foreach ( $post_types as $post_type ) {
		$num_posts = wp_count_posts( $post_type->name );
		$num = number_format_i18n( $num_posts->publish );
		$text = _n( $post_type->labels->singular_name, $post_type->labels->name, intval( $num_posts->publish ) );
		if ( current_user_can( 'edit_posts' ) ) {
			$output = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a>';
			echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
		} else {
			$output = '<span>' . $num . ' ' . $text . '</span>';
			echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
		}
	}
}

// Add page templates
add_filter( 'single_template', 'wpcs_set_single_session_template' );
function wpcs_set_single_session_template($single_template) {
	global $post;

	if ($post->post_type == 'wpcs_session' ) {
			$single_template = WPCS_DIR . '/templates/session-template.php';
	}
	return $single_template;
}


//* Change CPT title text
add_action( 'gettext', 'wpcsp_change_title_text' );
function wpcsp_change_title_text( $translation ) {
	global $post;
	if( isset( $post ) ) {
		switch( $post->post_type ){
			case 'wpcsp_speaker' :
				if( $translation == 'Add title' ) return 'Speaker Full Name';
			break;
            case 'wpcsp_sponsor' :
                if( $translation == 'Add title' ) return 'Sponsoring Company Name';
            break;
		}
	}
	return $translation;
}

// Add page templates
add_filter( 'single_template', 'wpcsp_set_single_template' );
function wpcsp_set_single_template($single_template) {
	global $post;

	if ($post->post_type == 'wpcsp_speaker' ) {
		$single_template = WPCS_DIR . '/templates/speaker-template.php';
	}
	if ($post->post_type == 'wpcsp_sponsor' ) {
		$single_template = WPCS_DIR . '/templates/sponsor-template.php';
	}
	return $single_template;
}