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
	$data['title'] = get_the_title( $post_ID );
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
		'twitter'  => $twitter,
		'facebook' => $facebook,
		'bluesky'  => $bluesky,
		'linkedin' => $linkedin,
		'mastodon' => $mastodon,
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
	$defaults = array( 'twitter', 'facebook', 'bluesky', 'linkedin', 'mastodon' );
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

	return '<ul class="wpcsp-links">' . $html . '</ul>';
}

/**
 * Fetch HTML for links and wrap in a container. Add heading and ARIA landmark role.
 *
 * @param integer $post_ID of current post.
 *
 * @return full HTML block.
 */
function wpcsp_social_block( $post_ID ) {
	$links = wpcsp_create_links( $post_ID );
	$html  = "
			<nav aria-labelledby='wpa-conference'>
				<h3 id='wpa-conference'>" . __( 'Share This Post', 'wpa-conference' ) . "</h3>			
				<div class='wpcsp-social-share'>
					$links
				</div>
			</nav>";

	return $html;
}

/**
 * Use WordPress filter 'the_content' to add sharing links into post content.
 *
 * @param string $content The current content of the post.
 *
 * @return $content The previous content of the post plus social sharing links.
 */
function wpcsp_post_content( $content ) {
	global $post;
	$post_ID = $post->ID;
	if ( is_main_query() && in_the_loop() ) {
		$wpcsp_social = wpcsp_social_block( $post_ID );
		$content      = $content . $wpcsp_social;
	}

	return $content;
}
add_filter( 'the_content', 'wpcsp_post_content' );
