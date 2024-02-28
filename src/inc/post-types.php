<?php
/**
 * Post type functions.
 *
 * @package wpcsp
 */

register_deactivation_hook( PLUGIN_FILE_URL, 'flush_rewrite_rules' );
register_activation_hook( PLUGIN_FILE_URL, 'wpcs_flush_rewrites' );
/**
 * Flush rewrite rules on activation.
 */
function wpcs_flush_rewrites() {
	// call your CPT registration function here (it should also be hooked into 'init').
	wpcs_register_post_types();
	flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
}

add_action( 'init', 'wpcs_register_post_types' );
/**
 * Registers the custom post types, runs during init.
 *
 * @return void
 */
function wpcs_register_post_types() {
	// Speaker post type labels.
	$speakerlabels = array(
		'name'               => __( 'People', 'wpa-conference' ),
		'singular_name'      => __( 'Person', 'wpa-conference' ),
		'add_new'            => __( 'Add New', 'wpa-conference' ),
		'add_new_item'       => __( 'Create New Person', 'wpa-conference' ),
		'edit'               => __( 'Edit', 'wpa-conference' ),
		'edit_item'          => __( 'Edit Person', 'wpa-conference' ),
		'new_item'           => __( 'New Person', 'wpa-conference' ),
		'view'               => __( 'View Person', 'wpa-conference' ),
		'view_item'          => __( 'View Person', 'wpa-conference' ),
		'search_items'       => __( 'Search People', 'wpa-conference' ),
		'not_found'          => __( 'No people found', 'wpa-conference' ),
		'not_found_in_trash' => __( 'No people found in Trash', 'wpa-conference' ),
		'parent_item_colon'  => __( 'Parent Person:', 'wpa-conference' ),
	);

	// Register speaker post type.
	register_post_type(
		'wpcsp_speaker',
		array(
			'labels'             => $speakerlabels,
			'rewrite'            => array( 'slug' => 'people' ),
			'supports'           => array( 'title', 'editor', 'revisions', 'thumbnail', 'page-attributes', 'excerpt' ),
			'menu_position'      => 20,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'can_export'         => true,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'query_var'          => true,
			'menu_icon'          => 'dashicons-groups',
			'show_in_rest'       => true,
			'rest_base'          => 'people',
			'has_archive'        => false,
		)
	);

	// Sponsor post type labels.
	$spnsorlabels = array(
		'name'               => __( 'Sponsors', 'wpa-conference' ),
		'singular_name'      => __( 'Sponsor', 'wpa-conference' ),
		'add_new'            => __( 'Add New', 'wpa-conference' ),
		'add_new_item'       => __( 'Create New Sponsor', 'wpa-conference' ),
		'edit'               => __( 'Edit', 'wpa-conference' ),
		'edit_item'          => __( 'Edit Sponsor', 'wpa-conference' ),
		'new_item'           => __( 'New Sponsor', 'wpa-conference' ),
		'view'               => __( 'View Sponsor', 'wpa-conference' ),
		'view_item'          => __( 'View Sponsor', 'wpa-conference' ),
		'search_items'       => __( 'Search Sponsors', 'wpa-conference' ),
		'not_found'          => __( 'No sponsors found', 'wpa-conference' ),
		'not_found_in_trash' => __( 'No sponsors found in Trash', 'wpa-conference' ),
		'parent_item_colon'  => __( 'Parent Sponsor:', 'wpa-conference' ),
	);

	// Register sponsor post type.
	register_post_type(
		'wpcsp_sponsor',
		array(
			'labels'          => $spnsorlabels,
			'rewrite'         => array(
				'slug'       => 'sponsors',
				'with_front' => false,
			),
			'supports'        => array( 'title', 'editor', 'revisions', 'thumbnail' ),
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

	// Donor post type labels.
	$donorlabels = array(
		'name'               => __( 'Donors', 'wpa-conference' ),
		'singular_name'      => __( 'Donor', 'wpa-conference' ),
		'add_new'            => __( 'Add New', 'wpa-conference' ),
		'add_new_item'       => __( 'Create New Donor', 'wpa-conference' ),
		'edit'               => __( 'Edit', 'wpa-conference' ),
		'edit_item'          => __( 'Edit Donor', 'wpa-conference' ),
		'new_item'           => __( 'New Donor', 'wpa-conference' ),
		'view'               => __( 'View Donor', 'wpa-conference' ),
		'view_item'          => __( 'View Donor', 'wpa-conference' ),
		'search_items'       => __( 'Search Donors', 'wpa-conference' ),
		'not_found'          => __( 'No Donors found', 'wpa-conference' ),
		'not_found_in_trash' => __( 'No Donors found in Trash', 'wpa-conference' ),
		'parent_item_colon'  => __( 'Parent Donor:', 'wpa-conference' ),
	);

	// Register donor post type.
	register_post_type(
		'wpcsp_donor',
		array(
			'labels'             => $donorlabels,
			'rewrite'            => array(
				'slug'       => 'donors',
				'with_front' => false,
			),
			'supports'           => array( 'title' ),
			'menu_position'      => 21,
			'public'             => true,
			'show_ui'            => true,
			'can_export'         => true,
			'capability_type'    => 'post',
			'hierarchical'       => false,
			'query_var'          => true,
			'menu_icon'          => 'dashicons-money',
			'show_in_rest'       => true,
			'rest_base'          => 'donors',
			'publicly_queryable' => false,
			'has_archive'        => false,
		)
	);

	// Session post type labels.
	$sessionlabels = array(
		'name'               => __( 'Sessions', 'wpa-conference' ),
		'singular_name'      => __( 'Session', 'wpa-conference' ),
		'add_new'            => __( 'Add New', 'wpa-conference' ),
		'add_new_item'       => __( 'Create New Session', 'wpa-conference' ),
		'edit'               => __( 'Edit', 'wpa-conference' ),
		'edit_item'          => __( 'Edit Session', 'wpa-conference' ),
		'new_item'           => __( 'New Session', 'wpa-conference' ),
		'view'               => __( 'View Session', 'wpa-conference' ),
		'view_item'          => __( 'View Session', 'wpa-conference' ),
		'search_items'       => __( 'Search Sessions', 'wpa-conference' ),
		'not_found'          => __( 'No sessions found', 'wpa-conference' ),
		'not_found_in_trash' => __( 'No sessions found in Trash', 'wpa-conference' ),
		'parent_item_colon'  => __( 'Parent Session:', 'wpa-conference' ),
	);

	// Register session post type.
	register_post_type(
		'wpcs_session',
		array(
			'labels'          => $sessionlabels,
			'rewrite'         => array(
				'slug'       => 'sessions',
				'with_front' => false,
			),
			'supports'        => array( 'title', 'editor', 'author', 'revisions', 'thumbnail', 'custom-fields' ),
			'menu_position'   => 21,
			'public'          => true,
			'show_ui'         => true,
			'can_export'      => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'query_var'       => true,
			'menu_icon'       => 'dashicons-schedule',
			'show_in_rest'    => false,
			'rest_base'       => 'sessions',
		)
	);

	$contentlabels = array(
		'name'               => __( 'Content Drafts', 'wpa-conference' ),
		'singular_name'      => __( 'Content Draft', 'wpa-conference' ),
		'add_new'            => __( 'Add New', 'wpa-conference' ),
		'add_new_item'       => __( 'Create New Content Draft', 'wpa-conference' ),
		'edit'               => __( 'Edit Draft', 'wpa-conference' ),
		'edit_item'          => __( 'Edit Content Draft', 'wpa-conference' ),
		'new_item'           => __( 'New Draft', 'wpa-conference' ),
		'view'               => __( 'View Draft', 'wpa-conference' ),
		'view_item'          => __( 'View Draft', 'wpa-conference' ),
		'search_items'       => __( 'Search Content Drafts', 'wpa-conference' ),
		'not_found'          => __( 'No drafts found', 'wpa-conference' ),
		'not_found_in_trash' => __( 'No drafts found in Trash', 'wpa-conference' ),
		'parent_item_colon'  => __( 'Parent Draft:', 'wpa-conference' ),
	);

	// Register session post type.
	register_post_type(
		'wpcs_drafts',
		array(
			'labels'          => $contentlabels,
			'rewrite'         => array(
				'slug'       => 'drafts',
				'with_front' => false,
			),
			'supports'        => array( 'title', 'editor', 'author', 'revisions', 'thumbnail', 'custom-fields' ),
			'menu_position'   => 22,
			'public'          => false,
			'show_ui'         => true,
			'can_export'      => true,
			'capability_type' => 'post',
			'hierarchical'    => false,
			'query_var'       => true,
			'menu_icon'       => 'dashicons-edit',
			'show_in_rest'    => true,
			'rest_base'       => 'sessions',
		)
	);
}

add_action( 'gettext', 'wpcs_change_title_text' );
/**
 * Change CPT title text.
 *
 * @param  string $translation The title.
 * @return string
 */
function wpcs_change_title_text( $translation ) {
	global $post;
	if ( isset( $post ) ) {
		switch ( $post->post_type ) {
			case 'wpcs_session':
				if ( 'Add title' === $translation ) {
					return 'Session Title';
				}
				break;
		}
	}
	return $translation;
}

add_action( 'dashboard_glance_items', 'wpcs_cpt_at_glance' );
/**
 * Add CPTs to Dashboad At A Glance Metabox.
 *
 * @return void
 */
function wpcs_cpt_at_glance() {
	$args     = array(
		'public'   => true,
		'_builtin' => false,
	);
	$output   = 'object';
	$operator = 'and';

	$post_types = get_post_types( $args, $output, $operator );
	foreach ( $post_types as $post_type ) {
		$num_posts = wp_count_posts( $post_type->name );
		$num       = number_format_i18n( $num_posts->publish );
		$text      = intval( $num_posts->publish ) > 1 ? $post_type->labels->name : $post_type->labels->singular_name;
		if ( current_user_can( 'edit_posts' ) ) {
			$output = '<a href="edit.php?post_type=' . esc_attr( $post_type->name ) . '">' . esc_html( $num ) . ' ' . esc_html( $text ) . '</a>';
			echo '<li class="post-count ' . esc_attr( $post_type->name ) . '-count">' . $output . '</li>';
		} else {
			$output = '<span>' . esc_html( $num ) . ' ' . esc_html( $text ) . '</span>';
			echo '<li class="post-count ' . esc_attr( $post_type->name ) . '-count">' . $output . '</li>';
		}
	}
}

add_filter( 'single_template', 'wpcs_set_single_session_template' );
/**
 * Add page templates
 *
 * @param  string $single_template The template path.
 * @return string
 */
function wpcs_set_single_session_template( $single_template ) {
	global $post;

	if ( 'wpcs_session' === $post->post_type ) {
		$single_template = WPCS_DIR . '/templates/session-template.php';
	}

	return $single_template;
}


add_action( 'gettext', 'wpcsp_change_title_text' );
/**
 * Change CPT title text
 *
 * @param  string $translation Title text.
 * @return string
 */
function wpcsp_change_title_text( $translation ) {
	global $post;
	if ( isset( $post ) ) {
		switch ( $post->post_type ) {
			case 'wpcsp_speaker':
				if ( 'Add title' === $translation ) {
					return 'Speaker Full Name';
				}
				break;
			case 'wpcsp_sponsor':
				if ( 'Add title' === $translation ) {
					return 'Sponsoring Company Name';
				}
				break;
		}
	}
	return $translation;
}

add_filter( 'single_template', 'wpcsp_set_single_template' );
/**
 * Add page templates
 *
 * @param  string $single_template The template path.
 * @return string
 */
function wpcsp_set_single_template( $single_template ) {
	global $post;

	if ( 'wpcsp_speaker' === $post->post_type ) {
		$single_template = WPCS_DIR . '/templates/speaker-template.php';
	}
	if ( 'wpcsp_sponsor' === $post->post_type ) {
		$single_template = WPCS_DIR . '/templates/sponsor-template.php';
	}
	return $single_template;
}

/**
 * Disable Gutenberg for simple content types.
 *
 * @param bool   $current_status Existing editor status.
 * @param string $post_type Post type being tested.
 *
 * @return bool
 */
function wpcs_disable_gutenberg( $current_status, $post_type ) {
	$post_types = array( 'wpcsp_speaker', 'wpcsp_sponsor', 'wpcsp_donor' );
	if ( in_array( $post_type, $post_types, true ) ) {
		return false;
	}

	return $current_status;
}
add_filter( 'use_block_editor_for_post_type', 'wpcs_disable_gutenberg', 10, 2 );
