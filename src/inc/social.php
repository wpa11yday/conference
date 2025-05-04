<?php
/**
 * Social sharing functions.
 *
 * @package wpcsp
 */

/*
 * Get the post data that will be sent to social sharing pages.
 * 
 * @param integer $post_ID ID of the current post.
 *
 * @return array of post data for use in sharing.
 */
function wpcsp_post_information( $post_ID ) {
	$data          = array();
	$data['text'] = get_the_title( $post_ID ); 
	$data['url']   = get_permalink( $post_ID );

	return $data;
}

/* 
 * Generate the URLs used to post data to services.
 * 
 * @param integer $post_ID of current post
 * 
 * @return array of URLs for posting to each service.
 */
function wpcsp_create_urls( $post_ID ) {
	$data      = wpcsp_post_information( $post_ID );
	$twitter   = "https://x.com/intent/tweet?text=" . urlencode( $data['title']  ). '&url=' . urlencode( $data['url'] );
	$facebook  = "https://www.facebook.com/sharer/sharer.php?u=" . urlencode( $data['url'] );
	$linkedin  = "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode( $data['url'] );
	$mastodon  = "#";
	$bluesky   = "https://bsky.app/intent/compose?text=" . urlencode( $data['text'] ) . ' ' . urlencode( $data['url'] );
	
	return apply_filters(
		'wpcsp_social_service_links',
		 array( 
			'twitter'  => $twitter,
			'facebook' => $facebook,
			'bluesky'  => $bluesky,
			'linkedin' => $linkedin,
			'mastodon' => $mastodon,
		),
		$data
	);
}

/*
 * Generate the HTML links using URLs.
 *
 * @param integer $post_ID of current post
 *
 * @return string block of HTML links.
 */
function wpcsp_create_links( $post_ID ) {
	$urls     = wpcsp_create_urls( $post_ID );
	$html     = '';
	$defaults = array(
		'twitter'  => 'on',
		'facebook' => 'on',
		'bluesky'  => 'on',
		'linkedin' => 'on',
		'mastodon' => 'on',
	);
	$settings = get_option( 'wpcsp_settings', $defaults );
	$enabled  = ( isset( $settings['enabled'] ) ) ? $settings['enabled'] : array();

	foreach ( $urls as $service => $url ) {
		$is_enabled = in_array( $service, array_keys( $enabled ) );
		if ( $url && $is_enabled ) {
			$social_icon = wpcsp_social_icon_class( $service );
			$html       .= "
					<div class='wpcsp-link $service'>
						<a href='" . esc_url( $url ) . "' rel='nofollow external' aria-describedby='description-$service'>
							<span class='wpcsp-icon dashicons dashicons-" . $social_icon . "' aria-hidden='true'></span>
							<span class='wpcsp-text $service'>" . ucfirst( $service ) . "</span>
						</a>
						<span class='description' role='tooltip' id='description-$service'>
							" . __( 'Share this post' ) . "
						</span>
					</div>";
		}
	}
	
	return "<div class='wpcsp-links'>" . $html . "</div>";
}

/*
 * Fetch HTML for links and wrap in a container. Add heading and ARIA landmark role.
 *
 * @param integer $post_ID of current post.
 *
 * @return full HTML block.
 */
function wpcsp_social_block( $post_ID ) {
	$links = wpcsp_create_links( $post_ID );
	$html = "
			<nav aria-labelledby='wpa-conference'>
				<h3 id='wpa-conference'>" . __( 'Share This Post', 'wpa-conference' ) . "</h3>			
				<div class='wpcsp-social-share'>				
					$links
				</div>
			</nav>";
	
	return $html;
}
/*
 * Use WordPress filter 'the_content' to add sharing links into post content.
 *
 * @param $content The current content of the post.
 * 
 * @return $content The previous content of the post plus social sharing links.
 */
add_filter( 'the_content', 'wpcsp_post_content' );
function wpcsp_post_content( $content ) {
	global $post;
	$post_ID = $post->ID;
	if ( is_main_query() && in_the_loop() ) {
		$wpcsp_social = wpcsp_social_block( $post_ID );
		$content      = $content . $wpcsp_social;
	}
	
	return $content;
}