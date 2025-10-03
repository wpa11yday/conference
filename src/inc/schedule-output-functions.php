<?php
/**
 * [schedule] shortcode and block functions.
 *
 * @package wpcsp
 */

defined( 'WPINC' ) || die();

/**
 * Get social media links for a speaker or sponsor.
 *
 * @param int $post_id Post ID.
 *
 * @return array
 */
function wpcsp_get_social_links( $post_id ) {
	$social_icons = array();
	$post_type    = ( 'wpcsp_sponsor' === get_post_type( $post_id ) ) ? 'sponsor' : 'speaker';
	foreach ( array( 'Facebook', 'Bluesky', 'Twitter', 'Mastodon', 'Instagram', 'LinkedIn', 'Threads', 'YouTube', 'WordPress', 'GitHub', 'Website' ) as $social_icon ) {

		$social_label = $social_icon;
		$social_icon  = strtolower( $social_icon );
		$url          = get_post_meta( get_the_ID(), 'wpcsp_' . $social_icon . '_url', true );
		if ( $url ) {
			$social_icon    = wpcsp_social_icon_class( $social_icon );
			$social_icons[] = '<a class="wpcsp-' . $post_type . '-social-icon-link" href="' . esc_url( $url ) . '"><span class="dashicons dashicons-' . $social_icon . '" aria-hidden="true"></span><span class="screen-reader-text">' . $social_label . '</a>';
		}
	}

	return $social_icons;
}

/**
 * Get the classes for rendering a social media icon.
 *
 * @param string $social_icon Social Icon designation.
 *
 * @return string
 */
function wpcsp_social_icon_class( $social_icon ) {
	switch ( $social_icon ) {
		case 'website':
			$social_icon = 'admin-site-alt3';
			break;
		case 'facebook':
			$social_icon = 'facebook-alt';
			break;
		case 'github':
			$social_icon = ' fa-brands fa-github';
			break;
		case 'mastodon':
			$social_icon = ' fa-brands fa-mastodon';
			break;
		case 'threads':
			$social_icon = ' fa-brands fa-threads';
			break;
		case 'bluesky':
			$social_icon = ' fa-brands fa-bluesky';
			break;
		case 'twitter':
			$social_icon = ' fa-brands fa-x-twitter';
			break;
	}

	return $social_icon;
}

/**
 * Return HTML from a WordPress profile via shortcode to show attendees.
 *
 * @param array $atts Shortcode attributes with one parameter, user ID.
 *
 * @return string
 */
function wpcs_shortcode_people( $atts ) {
	$atts = shortcode_atts(
		array(
			'id' => '',
		),
		$atts
	);

	$args = array(
		'orderby'    => 'meta_value',
		'meta_key'   => 'last_name',
		'meta_query' => array(
			array(
				'key'     => 'show_in_attendee_list',
				'compare' => '=',
				'value'   => 'Yes',
			),
		),
		'fields'     => array( 'ID', 'display_name', 'user_email' ),
	);
	// get all authorized users.
	$output = ( str_contains( home_url(), 'staging.wpaccessibility.day' ) ) ? false : get_transient( 'wpcs_attendees' );
	if ( $output ) {
		return $output;
	} else {
		$output = '';
	}
	$users = get_users( $args );
	foreach ( $users as $user ) {
		$default   = plugins_url( '/assets/images/default-gravatar.png', __DIR__ );
		$name      = $user->display_name;
		$gravatar  = get_avatar( $user->user_email, 96, $default );
		$city      = get_user_meta( $user->ID, 'city', true );
		$state     = get_user_meta( $user->ID, 'state', true );
		$country   = get_user_meta( $user->ID, 'country', true );
		$company   = get_user_meta( $user->ID, 'company', true );
		$job_title = get_user_meta( $user->ID, 'job_title', true );
		$twitter   = get_user_meta( $user->ID, 'twitter', true );
		$linked    = get_user_meta( $user->ID, 'linkedin', true );

		if ( $city === $state ) {
			$loc = $city;
		} else {
			$loc = ( '' === $state ) ? $city : $city . ', ' . $state;
		}
		$location = ( '' === $country ) ? $loc : $loc . ', ' . $country;
		$location = ( '' === $loc ) ? str_replace( ', ', '', $location ) : $location;
		if ( $company || $job_title ) {
			$company = ( $company ) ? $company : '';
			$company = ( $job_title && $company ) ? $job_title . ', ' . $company : $company;
		}
		$company  = ( $company ) ? '<div class="attendee-employment">' . esc_html( $company ) . '</div>' : '';
		$location = ( $location ) ? '<div class="attendee-location">' . esc_html( $location ) . '</div>' : '';
		$icons    = array();
		if ( $twitter ) {
			$icons[] = '<a href="' . esc_url( $twitter ) . '"><span class="dashicons fa-brands fa-x-twitter" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html( $name ) . ' on Twitter</span></a>';
		}
		if ( $linked ) {
			$icons[] = '<a href="' . esc_url( $linked ) . '"><span class="dashicons dashicons-linkedin" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html( $name ) . ' on LinkedIn</span></a>';
		}
		$social  = ( ! empty( $icons ) ) ? '<div class="attendee-social">' . implode( ' ', $icons ) . '</div>' : '';
		$output .= '<li>' . $gravatar . '<div class="attendee-info"><h2 class="attendee-name">' . $name . '</h2>' . $company . $location . $social . '</div></li>';
	}
	$output = '<ul class="wpad-attendees alignwide">' . $output . '</ul>';
	set_transient( 'wpcs_attendees', $output, 300 );

	return $output;
}

/**
 * Get sessions scheduled for conference.
 *
 * @return array
 */
function wpcs_get_sessions() {
	$post_status = 'publish';
	if ( isset( $_GET['preview'] ) && current_user_can( 'publish_posts' ) ) {
		$post_status = array( 'draft', 'approved' );
	}
	$query = array(
		'post_type'      => 'wpcs_session',
		'post_status'    => $post_status,
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_wpcs_session_time',
				'compare' => 'EXISTS',
			),
			array(
				'key'     => '_wpcs_session_time',
				'value'   => '',
				'compare' => '!=',
			),
		),
	);
	$posts = get_posts( $query );

	return $posts;
}

/**
 * Generate schedule for WP Accessibility Day.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function wpcs_schedule( $atts, $content ) {
	$output       = array();
	$is_draft     = ( 'draft' === get_post_status( get_the_ID() ) ) ? true : false;
	$return       = ( str_contains( home_url(), 'staging.wpaccessibility.day' ) || $is_draft ) ? false : get_transient( 'wpcs_schedule' );
	$current_talk = '';
	if ( $return && ! isset( $_GET['reset_cache'] ) ) {
		return $return;
	} else {
		$return = '';
	}
	$begin = strtotime( get_option( 'wpad_start_time' ) );
	$end   = strtotime( get_option( 'wpad_end_time' ) );
	$args  = shortcode_atts(
		array(
			'start' => '15',
		),
		$atts,
		'wpcs_schedule'
	);

	$posts    = wpcs_get_sessions();
	$schedule = array();
	foreach ( $posts as $post_ID ) {
		$time              = gmdate( 'H', get_post_meta( $post_ID, '_wpcs_session_time', true ) );
		$datatime          = gmdate( 'Y-m-d\TH:i:s\Z', get_post_meta( $post_ID, '_wpcs_session_time', true ) );
		$schedule[ $time ] = array(
			'id' => $post_ID,
			'ts' => $datatime,
		);
	}
	$start            = $args['start'] - 24;
	$n                = 1;
	$current_talk_set = false;
	for ( $i = $start; $i < $args['start']; $i++ ) {
		$number     = ( isset( $_GET['buttonsoff'] ) ) ? str_pad( $n, 2, '0', STR_PAD_LEFT ) : '';
		$session_id = ( isset( $_GET['buttonsoff'] ) ) ? " <span class='session_id'>$number</span>" : '';
		$is_first   = false;
		if ( $i === $start ) {
			$is_first = true;
		}
		if ( absint( $i ) !== $i ) {
			$base = 24 - absint( $i );
		} else {
			$base = $i;
		}

		$time       = str_pad( $base, 2, '0', STR_PAD_LEFT );
		$is_current = false;
		if ( ! isset( $schedule[ $time ] ) ) {
			continue;
		}
		$text    = '';
		$is_next = false;
		if ( ( time() > $begin - HOUR_IN_SECONDS ) && ( time() < $end ) ) {
			if ( ( $begin < time() && time() < $end ) && date( 'H' ) === $time && (int) date( 'i' ) < 50 || date( 'G' ) === (int) $time - 1 && (int) date( 'i' ) > 50 ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$is_current = true;
			}
			if ( (int) date( 'i' ) < 50 ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
				$text = __( 'Now speaking:', 'wpa-conference' ) . ' ';
			} else {
				$is_next = true;
				$text    = __( 'Up next:', 'wpa-conference' ) . ' ';
			}
		} elseif ( ! ( time() > $end ) ) {
			$is_next = true;
			$text    = false;
		}

		$talk_ID = $schedule[ $time ]['id'];
		if ( $talk_ID ) {
			$is_current = ( $is_current || ( $is_first && $is_next ) ) ? true : false;
			$session    = wpad_draw_session( $schedule[ $time ], $is_current, $text, $session_id );
			$output[]   = $session[0];
			if ( true !== $current_talk_set ) {
				$current_talk     = $session[1];
				$current_talk_set = true;
			}
		}
		++$n;
	}
	$opening_id = get_option( 'wpcs_opening_remarks' );
	$opening    = '';
	if ( $opening_id ) {
		$opening_time    = strtotime( get_option( 'wpad_start_time' ) );
		$opening_remarks = array(
			'id' => $opening_id,
			'ts' => gmdate( 'Y-m-d\TH:i:s\Z', (int) $opening_time ),
		);

		$opening = wpad_draw_session( $opening_remarks, true, 'Up next: ', '' );
		array_unshift( $output, $opening[0] );
	}

	$links  = wpcs_banner();
	$return = $links . $current_talk . implode( PHP_EOL, $output );
	set_transient( 'wpcs_schedule', $return, 150 );

	return $return;
}


/**
 * Get langs for a session.
 *
 * @param int $talk_id Post ID for session.
 *
 * @return string
 */
function wpad_get_langs_html( $talk_id ) {
	$all_tags  = wp_get_post_terms( $talk_id, 'wpcs_session_lang' );
	$tags_html = '';

	if ( ! is_wp_error( $all_tags ) && ! empty( $all_tags ) ) {

		if ( ! empty( $all_tags ) ) {
			$tags_html .= '<ul class="talks-wrapper">';
			foreach ( $all_tags as $tag ) {
				$tags_html .= '<li class="talk-lang-wrapper ' . $tag->slug . '">';
				$tags_html .= '<a href="' . esc_url( get_term_link( $tag->term_id ) ) . '">' . $tag->name . '</a>';
				$tags_html .= '</li>';
			}
			$tags_html .= '</ul>';
		}
	}

	return $tags_html;
}

/**
 * Filter term output for the languages taxonomy to wrap terms in appropriate lang tags.
 *
 * @param object $term Term object.
 * @param string $taxonomy Taxonomy name.
 *
 * @return object
 */
function wpad_filter_term_name( $term, $taxonomy ) {
	if ( 'wpcs_session_lang' === $taxonomy && ! is_admin() ) {
		$term->name = ( ! str_contains( $term->name, 'span lang' ) ) ? '<span lang="' . $term->slug . '">' . $term->name . '</span>' : $term->name;
	}

	return $term;
}
add_filter( 'get_term', 'wpad_filter_term_name', 10, 2 );

/**
 * Get tags for a session.
 *
 * @param int $talk_id Post ID for session.
 *
 * @return string
 */
function wpad_get_tags_html( $talk_id ) {
	$all_tags           = wp_get_post_terms( $talk_id, 'wpcs_session_tag' );
	$filtered_tags_html = '';

	if ( ! is_wp_error( $all_tags ) && ! empty( $all_tags ) ) {

		// Filter out tags that don't appear in more than one post.
		$filtered_tags = array_filter(
			$all_tags,
			function ( $tag ) {
				return $tag->count > 2;
			}
		);

		$counted = array();
		foreach ( $filtered_tags as $tag ) {
			$counted[ $tag->count ] = $tag;
		}
		krsort( $counted );

		if ( ! empty( $counted ) ) {
			$filtered_tags_html .= '<ul class="talks-wrapper">';
			$i                   = 0;
			foreach ( $counted as $tag ) {
				if ( $i > 4 ) {
					break;
				}
				$filtered_tags_html .= '<li class="talk-tag-wrapper">';
				$filtered_tags_html .= '<a href="' . esc_url( get_term_link( $tag->term_id ) ) . '">' . esc_html( $tag->name ) . '</a>';
				$filtered_tags_html .= '</li>';
				++$i;
			}
			$filtered_tags_html .= '</ul>';
		}
	}

	return $filtered_tags_html;
}

/**
 * Get track name for a session.
 *
 * @param int $talk_id Post ID for session.
 *
 * @return string
 */
function wpad_get_track_name( $talk_id ) {
	$track_name = '';
	$track      = wp_get_post_terms( $talk_id, 'wpcs_track' );
	if ( ! is_wp_error( $track ) && ! empty( $track ) ) {
		if ( isset( $track[0] ) ) {
			$track_name = $track[0]->name;
		}
	}

	return $track_name;
}

/**
 * Draw the Language list for a session.
 *
 * @param int $talk_id Talk Post ID.
 *
 * @return string
 */
function wpad_draw_langs( $talk_id ) {
	$tag_heading_txt = __( 'Languages', 'wpa-conference' );
	$tags_html       = wpad_get_langs_html( $talk_id );
	if ( ! empty( $tags_html ) ) {
		$tags_html = "
			<div class='talk-langs-wrapper'>
				<h3>$tag_heading_txt</h3>
				<div class='wp-block-columns inside talk-langs'>
					$tags_html
				</div>
			</div>
		";
	}

	return $tags_html;
}

/**
 * Draw the Topics list for a session.
 *
 * @param int $talk_id Talk Post ID.
 *
 * @return string
 */
function wpad_draw_topics( $talk_id ) {
	$tag_heading_txt = __( 'Topics', 'wpa-conference' );
	$tags_html       = wpad_get_tags_html( $talk_id );
	if ( ! empty( $tags_html ) ) {
		$tags_html = "
			<div class='talk-tags-wrapper'>
				<h3>$tag_heading_txt</h3>
				<div class='wp-block-columns inside talk-tags'>
					$tags_html
				</div>
			</div>
		";
	}

	return $tags_html;
}

/**
 * Draw a single session in the schedule.
 *
 * @param int    $talk Array with ID and timestamp for session to format.
 * @param bool   $is_current Is the current session or next if event not started.
 * @param string $text Label for current status.
 * @param string $session_id Visible session ID.
 *
 * @return array
 */
function wpad_draw_session( $talk, $is_current, $text, $session_id ) {
	$talk_ID         = $talk['id'];
	$datatime        = $talk['ts'];
	$mins            = gmdate( 'i', strtotime( $talk['ts'] ) );
	$time            = gmdate( 'H', strtotime( $talk['ts'] ) );
	$track_name      = wpad_get_track_name( $talk_ID );
	$track_name_html = '';

	if ( ! empty( $track_name ) ) {
		$track_name_html = '<div class="talk-track">' . $track_name . '</div>';
	}

	$time_html = '<div class="talk-header">
		<h2 class="talk-time" data-time="' . $datatime . '" id="talk-time-' . $time . '">
			<div class="time-wrapper">
				<span>' . $time . ':' . $mins . ' UTC<span class="screen-reader-text">,&nbsp;</span></span>
			</div>
		</h2>
		<div class="talk-wrapper">%s[control]</div>
	</div>';
	$talk_type = sanitize_html_class( get_post_meta( $talk_ID, '_wpcs_session_type', true ) );
	$speakers  = wpcs_session_speakers( $talk_ID, $talk_type );
	$sponsors  = wpcs_session_sponsors( $talk_ID );
	$talk      = get_post( $talk_ID );

	$talk_attr_id = sanitize_title( $talk->post_title );
	$talk_title   = '<div class="talk-title-meta"><a href="' . esc_url( get_the_permalink( $talk_ID ) ) . '" id="talk-' . $talk_attr_id . '">' . $talk->post_title . '</a>' . $track_name_html . $session_id . '</div>';
	$talk_label   = ( 'panel' === $talk_type ) ? '<strong>Panel:</strong> ' : '';
	$talk_title  .= '<div class="talk-speakers">' . $talk_label . implode( ', ', $speakers['list'] ) . '</div>';
	$talk_title   = '<div class="talk-title-wrapper">' . $talk_title . '</div>';
	$talk_heading = sprintf( $time_html, ' ' . $talk_title );
	if ( 'lightning-group' !== $talk_type ) {
		$wrap   = '<div class="wp-block-column">';
		$unwrap = '</div>';
	} else {
		$wrap   = '';
		$unwrap = '';
	}
	$talk_output  = $wrap . $sponsors;
	$talk_output .= ( 'lightning-group' !== $talk_type ) ? '<div class="talk-description">' . wp_trim_words( $talk->post_content ) . '</div>' : '';
	$talk_output .= $unwrap;
	$talk_output .= $wrap . $speakers['html'] . $unwrap;

	$session_id   = sanitize_title( $talk->post_title );
	$hidden       = ( isset( $_GET['buttonsoff'] ) ) ? '' : 'hidden';
	$control      = ( isset( $_GET['buttonsoff'] ) ) ? '' : '<button type="button" class="toggle-details" aria-expanded="false"><span class="dashicons-plus dashicons" aria-hidden="true"></span> View Details<span class="screen-reader-text">: ' . $talk->post_title . '</span></button>';
	$current_talk = '';
	if ( $is_current ) {
		$hidden  = '';
		$control = str_replace( '"false"', '"true"', $control );
		$control = str_replace( '-plus', '-minus', $control );
		if ( false !== $text ) {
			$current_talk = "<p class='current-talk'><strong>$text</strong> <a href='#$session_id'>$time:$mins UTC - $talk->post_title</a></p>";
		}
	}
	if ( current_user_can( 'manage_options' ) ) {
		$calendar = wpad_add_calendar_links( $talk_ID );
	}

	$output = "
	<div class='wp-block-group schedule $talk_type' id='$session_id'>
		<div class='wp-block-group__inner-container'>
			" . str_replace( '[control]', '<div>' . $control . '</div>', $talk_heading ) . "
			<div class='wp-block-columns inside $hidden'>
				$talk_output
			</div>
			$calendar
		</div>
	</div>";

	return array( $output, $current_talk );
}

/**
 * Inline shortcode to generate event start time.
 *
 * @param array $atts Array of shortcode attributes include date/time format (string) and dashicon identifier (string), and a fallback value if no time set.
 *
 * @return string
 */
function wpcs_event_start( $atts = array() ) {
	$args     = shortcode_atts(
		array(
			'format'   => 'H:i',
			'dashicon' => '',
			'fallback' => 'Fall 2024',
			'time'     => '',
		),
		$atts,
		'wpad'
	);
	$dashicon = '';
	if ( '' !== $args['dashicon'] ) {
		$dashicon = '<span class="dashicons dashicons-' . esc_attr( $args['dashicon'] ) . '" aria-hidden="true"></span> ';
	}
	$datetime = ( '' !== $args['time'] ) ? $args['time'] : get_option( 'wpad_start_time', '' );
	if ( $datetime ) {
		$start = gmdate( 'Y-m-d\TH:i:00', strtotime( $datetime ) ) . 'Z';
		$time  = gmdate( $args['format'], strtotime( $datetime ) );
		if ( 'H:i' === $args['format'] ) {
			$time .= ' UTC';
		}
	} else {
		return '<span class="event-time">' . esc_html( $args['fallback'] ) . '</span>';
	}

	return '<time class="event-time" datetime="' . esc_attr( $start ) . '" data-time="' . esc_attr( $start ) . '">' . $dashicon . esc_html( $time ) . '</time>';
}

/**
 * Show the event start time banner.
 *
 * @return string
 */
function wpcs_banner() {
	$time   = time();
	$output = '';
	// If more than 10 minutes before start time.
	if ( $time < ( strtotime( get_option( 'wpad_start_time', '' ) ) - 600 ) ) {
		// Actual start time.
		if ( $time < strtotime( get_option( 'wpad_start_time', '' ) ) ) {
			$start  = gmdate( 'F j, Y', strtotime( get_option( 'wpad_start_time', '' ) ) );
			$until  = human_time_diff( $time, strtotime( get_option( 'wpad_start_time', '' ) ) );
			$append = " - in just about <strong>$until</strong>!";
		}
		$register_or_signup = ( 'true' === get_option( 'wpcs_registration_open' ) ) ? "<a class='button' href='" . esc_url( get_option( 'wpcs_field_registration' ) ) . "'>Register today!</a>" : ' <a href="' . home_url( 'join-our-email-list' ) . '" class="button">Get notified when registration opens!</a>';
		$output             = "<div class='wpad-callout'><p><span>WP Accessibility Day starts $start $append</span> $register_or_signup </p></div>";
	}

	return $output;
}

/**
 * Get speakers for schedule.
 *
 * @param int    $session_id Talk post ID.
 * @param string $talk_type Type of session to display.
 *
 * @return array Array containing output HTML and a list of names. [html=>'',list=>'']
 */
function wpcs_session_speakers( $session_id, $talk_type = 'session' ) {
	$html         = '';
	$list         = array();
	$speakers_cpt = get_post_meta( $session_id, 'wpcsp_session_speakers', true );
	$speakers_cpt = ( is_array( $speakers_cpt ) ) ? array_reverse( $speakers_cpt ) : array( get_post_meta( $session_id, '_wpcs_session_speakers', true ) );

	if ( $speakers_cpt ) {
		$speakers_heading = '';
		if ( ! is_page( 'schedule' ) ) {
			$speakers_heading = ( count( $speakers_cpt ) > 1 ) ? '<h3>Speakers</h3>' : '<h3>Speaker</h3>';
		}
		$title_organization = array();
		ob_start();
		if ( 'lightning-group' === $talk_type ) {
			$ltalks = get_post_meta( $session_id, 'wpad_lightning_talks', true );
			if ( $ltalks ) {
				$ltalks = explode( ',', $ltalks );
				foreach ( $ltalks as $lt ) {
					update_post_meta( $lt, '_wpad_session', $session_id );
					$speaker      = '';
					$speakers_cpt = get_post_meta( $lt, 'wpcsp_session_speakers', true );
					$speakers_cpt = ( is_array( $speakers_cpt ) ) ? array_reverse( $speakers_cpt ) : array( get_post_meta( $lt, '_wpcs_session_speakers', true ) );
					foreach ( $speakers_cpt as $st ) {
						$first_name         = get_post_meta( $st, 'wpcsp_first_name', true );
						$last_name          = get_post_meta( $st, 'wpcsp_last_name', true );
						$concat             = $first_name . ' ' . $last_name;
						$full_name          = '<a href="' . get_permalink( $st ) . '">' . $concat . '</a>';
						$title_organization = array();
						$title              = get_post_meta( $st, 'wpcsp_title', true );
						$organization       = get_post_meta( $st, 'wpcsp_organization', true );
						if ( $title ) {
							$title_organization[] = $title;
						}
						if ( $organization ) {
							$title_organization[] = $organization;
						}
						$headshot = get_the_post_thumbnail( $st, 'thumbnail' );
						$speaker .= '<div class="wpcsp-session-speaker">
							' . $headshot . '
							<div class="wpcsp-session-speaker-data">
								<div class="wpcsp-session-speaker-name">
									' . $full_name . '
								</div>
								<div class="wpcsp-session-speaker-title-organization">
									' . implode( ', ', $title_organization ) . '
								</div>
							</div>
						</div>';
					}
					$html .= '<div class="lightning-talk">
						<h3><a href="' . get_the_permalink( $lt ) . '">' . get_post_field( 'post_title', $lt ) . '</a></h3>
						<div class="talk-description">
							' . wp_trim_words( get_post_field( 'post_content', $lt ) ) . '
						</div>
					</div><div class="lightning-talk-speakers">' . $speaker . '</div>';
				}
			}
		} else {
			foreach ( $speakers_cpt as $post_id ) {
				if ( ! is_numeric( $post_id ) ) {
					$concat    = $post_id;
					$full_name = $concat;
					$headshot  = '';
				} else {
					$first_name         = get_post_meta( $post_id, 'wpcsp_first_name', true );
					$last_name          = get_post_meta( $post_id, 'wpcsp_last_name', true );
					$concat             = $first_name . ' ' . $last_name;
					$full_name          = '<a href="' . get_permalink( $post_id ) . '">' . $concat . '</a>';
					$title_organization = array();
					$title              = get_post_meta( $post_id, 'wpcsp_title', true );
					$organization       = get_post_meta( $post_id, 'wpcsp_organization', true );
					if ( $title ) {
						$title_organization[] = $title;
					}
					if ( $organization ) {
						$title_organization[] = $organization;
					}
					$headshot = get_the_post_thumbnail( $post_id, 'thumbnail' );
				}
				$list[]    = $concat;
				$talk_html = '';

				echo $talk_html;
				echo '<div class="wpcsp-session-speaker">
					' . $headshot . '
					<div class="wpcsp-session-speaker-data">
						<div class="wpcsp-session-speaker-name">
							' . $full_name . '
						</div>
						<div class="wpcsp-session-speaker-title-organization">
							' . implode( ', ', $title_organization ) . '
						</div>
					</div>
				</div>';
			}
		}
		$html .= ob_get_clean();
	}
	$html = ( 'lightning-group' !== $talk_type ) ? '<div class="wpcsp-speakers">' . $speakers_heading . $html . '</div>' : $html;

	return array(
		'list' => $list,
		'html' => $html,
	);
}

/**
 * Get sponsors for schedule.
 *
 * @param int $session_id Talk post ID.
 *
 * @return string Output HTML
 */
function wpcs_session_sponsors( $session_id ) {
	$session_sponsors = get_post_meta( $session_id, 'wpcsp_session_sponsors', true );
	if ( ! $session_sponsors ) {
		return '';
	}

	$sponsors = array();
	foreach ( $session_sponsors as $sponsor_li ) {
		$sponsors[] .= '<a href="' . esc_url( get_the_permalink( $sponsor_li ) ) . '">' . get_the_title( $sponsor_li ) . '</a>';
	}
	ob_start();

	if ( $sponsors ) {
		echo '<div class="wpcs-session-sponsor"><span class="wpcs-session-sponsor-label">Session Sponsored by: </span>' . implode( ', ', $sponsors ) . '</div>';
	}
	$html = ob_get_clean();

	return $html;
}

/**
 * Get an array of links to slide data.
 *
 * @param int $session_ID Post ID for session.
 *
 * @return array
 */
function wpcs_get_slides( $session_ID ) {
	$slides    = get_post_meta( $session_ID, 'wpcsp_session_slides', true );
	$labels    = get_post_meta( $session_ID, 'wpcsp_session_slide_labels', true );
	$filetypes = array( '.ppt', '.pptx', '.pdf', '.key', '.otp', '.pps', '.ppsx' );
	$list      = array();
	$extension = 'url';
	if ( is_array( $slides ) ) {
		foreach ( $slides as $index => $slide ) {
			foreach ( $filetypes as $ext ) {
				$extension = 'url';
				$ends_with = wpcs_ends_with( $slide, $ext );
				if ( $ends_with ) {
					$extension = $ext;
					break;
				}
			}
			$label = ( ! empty( $labels ) && isset( $labels[ $index ] ) ) ? $labels[ $index ] : false;
			$class = 'custom';
			if ( ! $label ) {
				if ( 'url' !== $extension ) {
					$class = sanitize_title( $extension );
					// translators: file extension.
					$label = sprintf( __( 'Slides (%s)', 'wpa-conference' ), $extension );
				} else {
					$class = 'url';
					$label = __( 'Slides (URL)', 'wpa-conference' );
				}
			}
			$list[] = ( esc_url( $slide ) ) ? '<a href="' . esc_url( $slide ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</a>' : '';
		}
	}

	return $list;
}

/**
 * Output slides.
 *
 * @param int $session_ID Session ID.
 */
function wpcs_slides( $session_ID ) {
	$slides = wpcs_get_slides( $session_ID );
	$output = '';
	if ( is_array( $slides ) && ! empty( $slides ) ) {
		foreach ( $slides as $slide ) {
			$output .= '<li>' . $slide . '</li>';
		}
		return '<div class="wpcs-slides-wrapper"><ul class="wpcs-slides">' . $output . '</ul></div>';
	}
}

/**
 * Get an array of links to session resources.
 *
 * @param int $session_ID Post ID for session.
 *
 * @return array
 */
function wpcs_get_resources( $session_ID ) {
	$resources = get_post_meta( $session_ID, 'wpcsp_session_resources', true );
	$labels    = get_post_meta( $session_ID, 'wpcsp_session_resource_labels', true );
	$filetypes = array( '.doc', '.docx', '.xls', '.xlsx', '.pdf' );
	$list      = array();
	$extension = 'url';
	if ( is_array( $resources ) ) {
		foreach ( $resources as $index => $resource ) {
			foreach ( $filetypes as $ext ) {
				$extension = 'url';
				$ends_with = wpcs_ends_with( $resource, $ext );
				if ( $ends_with ) {
					$extension = $ext;
					break;
				}
			}
			$label = ( ! empty( $labels ) && isset( $labels[ $index ] ) ) ? $labels[ $index ] : false;
			$class = 'custom';
			if ( ! $label ) {
				if ( 'url' !== $extension ) {
					$name = '';
					if ( current_user_can( 'manage_options' ) ) {
						$parts = wp_parse_url( $resource );
						$path  = $parts['path'];
						$split = explode( '/', $path );
						$name  = end( $split );
						$name  = ' - ' . str_replace( array( '-', '_', $extension ), ' ', $name );
					}
					$class = sanitize_title( $extension );
					// translators: 1) type of resource, 2) File extension.
					$label = sprintf( __( 'Session Resource (%1$s) %2$s', 'wpa-conference' ), strtoupper( $class ), $name );
				} else {
					$class = 'url';
					$label = __( 'Session Resource (URL)', 'wpa-conference' );
				}
			}
			$list[] = ( esc_url( $resource ) ) ? '<a href="' . esc_url( $resource ) . '">' . esc_html( $label ) . '</a>' : '';
		}
	}

	return $list;
}

/**
 * Output resources.
 *
 * @param int $session_ID Session ID.
 */
function wpcs_resources( $session_ID ) {
	$resources = wpcs_get_resources( $session_ID );
	if ( is_array( $resources ) && ! empty( $resources ) ) {
		$output = '';
		foreach ( $resources as $resource ) {
			$output .= '<li>' . $resource . '</li>';
		}
		return '<div class="wpcs-resources-wrapper"><ul class="wpcs-resources">' . $output . '</ul></div>';
	}
}

/**
 * Check for a file extension ending on a URL.
 *
 * @param string $source Source string to check.
 * @param string $ext Extension we're checking for.
 *
 * @return bool
 */
function wpcs_ends_with( $source, $ext ) {
	$length = strlen( $ext );
	if ( 0 === $length ) {
		return true;
	}

	return ( substr( $source, -$length ) === $ext );
}

/**
 * Add grouped session data after post content.
 *
 * @param string $content Post content.
 *
 * @return string
 */
function wpcs_session_details( $content ) {
	if ( is_main_query() && in_the_loop() ) {
		global $post;
		$post_ID = $post->ID;
		if ( 'wpcs_session' === get_post_type( $post ) ) {
			$session_type = get_post_meta( $post_ID, '_wpcs_session_type', true );
			$slides       = wpcs_slides( $post_ID );
			$resources    = wpcs_resources( $post_ID );
			$speakers     = wpcs_session_speakers( $post_ID, $session_type )['html'];
			$topics       = wpad_draw_topics( $post_ID );
			$translations = wpad_draw_langs( $post_ID );
			if ( empty( $slides ) && empty( $resources ) ) {
				$slides = '<p>' . __( 'None provided yet.', 'wpa-conference' ) . '</p>';
			}
			$now              = current_time( 'timestamp', true ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.RequestedUTC
			$embargo          = strtotime( get_option( 'wpad_start_time' ) . ' -7 days' );
			$resources_public = ( $now > $embargo ) ? true : false;
			$resource_block   = ( $resources_public ) ? '<div><h3>' . __( 'Resources', 'wpa-conference' ) . '</h3>' . $slides . $resources . '</div>' : '';

			$content = $content . '<div class="session-blocks">' . $resource_block . '<div>' . $topics . '</div><div>' . $translations . '</div></div>' . $speakers;
		}
	}
	return $content;
}
add_filter( 'the_content', 'wpcs_session_details' );
