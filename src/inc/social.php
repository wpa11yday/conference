<?php
/**
 * Social sharing functions.
 *
 * @package wpcsp
 */

/**
 * Get the post data that will be sent to social sharing pages.
 *
 * @param integer $post_ID ID of the current post.
 *
 * @return array of post data for use in sharing.
 */
function wpcsp_post_information( $post_ID ) {
	$data          = array();
	$post          = get_post( $post_ID );
	$data['title'] = $post->post_title;
	$data['url']   = get_permalink( $post_ID );

	return $data;
}

/**
 * Generate the URLs used to post data to services.
 *
 * @param integer $post_ID of current post.
 *
 * @return array of URLs for posting to each service.
 */
function wpcsp_create_urls( $post_ID ) {
	$data     = wpcsp_post_information( $post_ID );
	$twitter  = 'https://x.com/intent/tweet?text=' . urlencode( $data['title'] ) . '&url=' . urlencode( $data['url'] );
	$facebook = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode( $data['url'] );
	$linkedin = 'https://www.linkedin.com/sharing/share-offsite/?url=' . urlencode( $data['url'] );
	$mastodon = '#';
	$bluesky  = 'https://bsky.app/intent/compose?text=' . urlencode( $data['title'] ) . ' ' . urlencode( $data['url'] );

	return array(
		'facebook' => $facebook,
		'linkedin' => $linkedin,
		'bluesky'  => $bluesky,
		'mastodon' => $mastodon,
		'twitter'  => $twitter,
	);
}

/**
 * Generate the HTML links using URLs.
 *
 * @param integer $post_ID of current post.
 *
 * @return string block of HTML links.
 */
function wpcsp_create_links( $post_ID ) {
	$urls     = wpcsp_create_urls( $post_ID );
	$html     = '';
	$defaults = array( 'facebook', 'linkedin', 'bluesky', 'mastodon', 'twitter' );
	foreach ( $urls as $service => $url ) {
		$is_enabled = in_array( $service, $defaults, true );

		if ( $url && $is_enabled ) {
			$social_icon = wpcsp_social_icon_class( $service );
			$link_class  = $service;
			if ( 'mastodon' === $service ) {
				$link_class .= ' mastodon-share';
			}
			$html .= "
					<li class='wpcsp-link " . esc_attr( $service ) . "'>
						<a class='" . $link_class . "' href='" . esc_url( $url ) . "' target='_blank' rel='nofollow external'>
							<span class='wpcsp-icon dashicons dashicons-" . esc_attr( $social_icon ) . "' aria-hidden='true'></span>
							<span class='wpcsp-text $service'>" . esc_html( ucfirst( $service ) ) . '</span>
						</a>
					</li>';
		}
	}

	return '<ul class="wpcsp-links" id="wpad-share-post-' . absint( $post_ID ) . '">' . $html . '</ul>';
}

/**
 * Fetch HTML for links and wrap in a container. Add heading and ARIA landmark role.
 *
 * @param integer $post_ID of current post.
 * @param string  $heading Heading text.
 * @param string  $level Heading level.
 * @param string  $button Disclosure trigger text. Empty to disable disclosure.
 *
 * @return full HTML block.
 */
function wpcsp_social_block( $post_ID, $heading = '', $level = 'h2', $button = '' ) {
	$links  = wpcsp_create_links( $post_ID );
	$level  = ( in_array( $level, array( 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ) ? $level : 'h2';
	$text   = ( $heading ) ? $heading : __( 'Share This Post', 'wpa-conference' );
	$button = ( $button ) ? '<button class="button has-popup" type="button" aria-expanded="false" aria-haspopup="true" aria-controls="wpad-share-post-' . absint( $post_ID ) . '">' . esc_html( $button ) . '</button>' : '';
	if ( $button ) {
		$html = '<div class="wpad-share-post">' . $button . $links . '</div>';
	} else {
		$html = "
			<nav aria-labelledby='wpa-conference'>
				<$level id='wpa-conference'>" . esc_html( $text ) . "</$level>
				<div class='wpcsp-social-share'>
					$links
				</div>
			</nav>";
	}
	return $html;
}

/**
 * Shortcode handler for social blocks. All attributes are optional.
 *
 * @param array  $atts Shortcode attributes. [ 'post_id' => int, 'heading' => heading text, 'level' => h2, h3, etc. ].
 * @param string $content Enclosed content. Unused attribute.
 *
 * @return string
 */
function wpcs_social_links( $atts = array(), $content = '' ) {
	$args = shortcode_atts(
		array(
			'post_id' => get_the_ID(),
			'heading' => '',
			'level'   => 'h2',
			'button'  => 'Share',
		),
		$atts,
		'social'
	);

	return wpcsp_social_block( $args['post_id'], $args['heading'], $args['level'], $args['button'] );
}

/**
 * Use WordPress filter 'the_content' to add sharing links into post content.
 *
 * @param string $content The current content of the post.
 *
 * @return $content The previous content of the post plus social sharing links.
 */
function wpcsp_post_content( $content ) {
	if ( is_main_query() && in_the_loop() ) {
		global $post;
		$post_ID = $post->ID;
		if ( 'post' === $post->post_type ) {
			$wpcsp_social = wpcsp_social_block( $post_ID );
			$content      = $content . $wpcsp_social;
		}
	}
	return $content;
}
add_filter( 'the_content', 'wpcsp_post_content', 100 );
