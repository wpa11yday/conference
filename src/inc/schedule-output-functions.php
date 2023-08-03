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
 * @return string
 */
function wpcsp_get_social_links( $post_id ) {
	$social_icons = array();
	$post_type    = ( 'wpcsp_sponsor' === get_post_type( $post_id ) ) ? 'sponsor' : 'speaker';
	foreach ( array( 'Facebook', 'Twitter', 'Instagram', 'LinkedIn', 'YouTube', 'WordPress', 'GitHub', 'Website' ) as $social_icon ) {

		$social_label = $social_icon;
		$social_icon  = strtolower( $social_icon );
		$url          = get_post_meta( get_the_ID(), 'wpcsp_' . $social_icon . '_url', true );
		if ( $url ) {

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
			}

			$social_icons[] = '<a class="wpcsp-' . $post_type . '-social-icon-link" href="' . esc_url( $url ) . '"><span class="dashicons dashicons-' . $social_icon . '" aria-hidden="true"></span><span class="screen-reader-text">' . $social_label . '</a>';
		}
	}
	return $social_icons;
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
	$output = get_transient( 'wpcs_attendees' );
	if ( $output ) {
		return $output;
	} else {
		$output = '';
	}
	$users = get_users( $args );
	foreach ( $users as $user ) {
		$name      = $user->display_name;
		$gravatar  = get_avatar( $user->user_email );
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
			$icons[] = '<a href="' . esc_url( $twitter ) . '"><span class="dashicons dashicons-twitter" aria-hidden="true"></span><span class="screen-reader-text">' . esc_html( $name ) . ' on Twitter</span></a>';
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
		$post_status = 'draft';
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
	$return       = get_transient( 'wpcs_schedule' );
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
	$start = $args['start'] - 24;
	$n     = 1;
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
				$text = 'Now speaking: ';
			} else {
				$is_next = true;
				$text    = 'Up next: ';
			}
		} elseif ( ! ( time() > $end ) ) {
			$is_next = true;
			$text    = false;
		}

		$datatime  = $schedule[ $time ]['ts'];
		$time_html = '<div class="talk-header"><h2 class="talk-time" data-time="' . $datatime . '" id="talk-time-' . $time . '"><div class="time-wrapper"><span>' . $time . ':00 UTC<span class="screen-reader-text">,&nbsp;</span></span>' . ' </div></h2><div class="talk-wrapper">%s[control]</div></div>';
		$talk_ID   = $schedule[ $time ]['id'];
		if ( $talk_ID ) {
			$talk_type = sanitize_html_class( get_post_meta( $talk_ID, '_wpcs_session_type', true ) );
			$speakers  = wpcs_session_speakers( $talk_ID, $talk_type );
			$sponsors  = wpcs_session_sponsors( $talk_ID );
			$talk      = get_post( $talk_ID );

			$talk_attr_id = sanitize_title( $talk->post_title );
			$talk_title   = '<a href="' . esc_url( get_the_permalink( $talk_ID ) ) . '" id="talk-' . $talk_attr_id . '">' . $talk->post_title . '</a>' . $session_id;
			$talk_label   = ( 'panel' === $talk_type ) ? '<strong>Panel:</strong> ' : '';
			$talk_title  .= '<div class="talk-speakers">' . $talk_label . implode( ', ', $speakers['list'] ) . '</div>';
			$talk_title   = '<div class="talk-title-wrapper">' . $talk_title . '</div>';
			$talk_heading = sprintf( $time_html, ' ' . $talk_title );
			if ( 'lightning' !== $talk_type ) {
				$wrap   = '<div class="wp-block-column">';
				$unwrap = '</div>';
			} else {
				$wrap   = '';
				$unwrap = '';
			}
			$talk_output  = $wrap . $sponsors;
			$talk_output .= ( 'lightning' !== $talk_type ) ? '<div class="talk-description">' . wp_trim_words( $talk->post_content ) . '</div>' : '';
			$talk_output .= $unwrap;
			$talk_output .= $wrap . $speakers['html'] . $unwrap;

			$session_id = sanitize_title( $talk->post_title );
			$hidden     = ( isset( $_GET['buttonsoff'] ) ) ? '' : 'hidden';
			$control    = ( isset( $_GET['buttonsoff'] ) ) ? '' : '<button type="button" class="toggle-details" aria-expanded="false"><span class="dashicons-plus dashicons" aria-hidden="true"></span> View Details<span class="screen-reader-text">: ' . $talk->post_title . '</span></button>';

			if ( $is_current || ( $is_first && $is_next ) ) {
				$hidden  = '';
				$control = str_replace( '"false"', '"true"', $control );
				$control = str_replace( '-plus', '-minus', $control );
				if ( false !== $text ) {
					$current_talk = "<p class='current-talk'><strong>$text</strong> <a href='#$session_id'>$time:00 UTC - $talk->post_title</a></p>";
				}
			}

			$output[] = "
			<div class='wp-block-group schedule $talk_type' id='$session_id'>
				<div class='wp-block-group__inner-container'>
					" . str_replace( '[control]', '<div>' . $control . '</div>', $talk_heading ) . "
					<div class='wp-block-columns inside $hidden'>
						$talk_output
					</div>
				</div>
			</div>";
		} else {
			$talk_heading = sprintf( $time_html, '<span class="unannounced">Watch this spot!</span>' );
			$output[]     = "
			<div class='wp-block-group schedule unset' id='unset'>
				<div class='wp-block-group__inner-container'>
					$talk_heading
					<div class='wp-block-columns inside'>
					</div>
				</div>
			</div>";
		}
		$n++;
	}

	$links  = wpcs_banner();
	$return = $links . $current_talk . implode( PHP_EOL, $output );
	set_transient( 'wpcs_schedule', $return, 150 );

	return $return;
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
		),
		$atts,
		'wpad'
	);
	$dashicon = '';
	if ( '' !== $args['dashicon'] ) {
		$dashicon = '<span class="dashicons dashicons-' . esc_attr( $args['dashicon'] ) . '" aria-hidden="true"></span> ';
	}
	if ( get_option( 'wpad_start_time', '' ) ) {
		$start = gmdate( 'Y-m-d\TH:i:00', strtotime( get_option( 'wpad_start_time', '' ) ) ) . 'Z';
		$time  = gmdate( $args['format'], strtotime( get_option( 'wpad_start_time', '' ) ) );
		if ( 'H:i' === $args['format'] ) {
			$time .= ' UTC';
		}
	} else {
		return '<span class="event-time">' . esc_html( $args['fallback'] ) . '</span>';
	}

	return '<time class="event-time" datetime="' . esc_attr( $start ) . '" data-time="' . esc_attr( $start ) . '">' . $dashicon . esc_html( $time ) . '</span>';
}

/**
 * Show the event start time banner.
 *
 * @return string
 */
function wpcs_banner() {
	$time   = time();
	$output = '';
	// 10 minutes before start time.
	if ( $time < ( strtotime( get_option( 'wpad_start_time', '' ) ) - 600 ) ) {
		// Actual start time.
		if ( $time < strtotime( get_option( 'wpad_start_time', '' ) ) ) {
			$start  = gmdate( 'F j, Y', strtotime( get_option( 'wpad_start_time', '' ) ) );
			$until  = human_time_diff( $time, strtotime( get_option( 'wpad_start_time', '' ) ) );
			$append = " - in just <strong>$until</strong>!";
		}
		$output = "<div class='wpad-callout'><p>WP Accessibility Day starts $start $append <a href='" . esc_url( get_option( 'wpcs_field_registration' ) ) . "'>Register today!</a> </p></div>";
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
		ob_start();
		foreach ( $speakers_cpt as $post_id ) {
			if ( ! is_numeric( $post_id ) ) {
				$concat    = $post_id;
				$full_name = $concat;
				$headshot  = '';
			} else {
				$first_name = get_post_meta( $post_id, 'wpcsp_first_name', true );
				$last_name  = get_post_meta( $post_id, 'wpcsp_last_name', true );
				$concat     = $first_name . ' ' . $last_name;
				$full_name  = '<a href="' . get_permalink( $post_id ) . '">' . $concat . '</a>';
				$headshot   = get_the_post_thumbnail( $post_id, 'thumbnail' );
			}
			$list[]             = $concat;
			$title_organization = array();
			$talk_html          = '';
			$wrap               = '';
			$unwrap             = '';
			if ( 'lightning' === $talk_type ) {
				global $wpdb;
				$wrap   = '<div class="wp-block-column">';
				$unwrap = '</div>';
				$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_wpcs_session_speakers' AND meta_value = %d LIMIT 1", $post_id ) );

				$talk_html = '
				<div class="lightning-talk">
					<h3><a href="' . get_the_permalink( $result[0]->post_id ) . '">' . get_post_field( 'post_title', $result[0]->post_id ) . '</a></h3>
					<div class="talk-description">
						' . wp_trim_words( get_post_field( 'post_content', $result[0]->post_id ) ) . '
					</div>
				</div>';
				$meta      = get_post_meta( $result[0]->post_id, '_wpad_session', true );
				if ( ! $meta ) {
					update_post_meta( $result[0]->post_id, '_wpad_session', $session_id );
				}
			}
			echo $wrap;
			echo $talk_html;
			?>
			<div class="wpcsp-session-speaker">
				<?php
				if ( $headshot ) {
					echo $headshot;
				}
				if ( $full_name || $title_organization ) {
					?>
					<div class="wpcsp-session-speaker-data">
					<?php
				}
				if ( $full_name ) {
					?>
					<div class="wpcsp-session-speaker-name">
						<?php echo $full_name; ?>
					</div>
					<?php
				}
				if ( $title_organization ) {
					?>
					<div class="wpcsp-session-speaker-title-organization">
						<?php echo implode( ', ', $title_organization ); ?>
					</div>
					<?php
				}
				if ( $full_name || $title_organization ) {
					?>
					</div>
					<?php
				}
				?>
			</div>
			<?php
			echo $unwrap;
		}
		$html .= ob_get_clean();
	}
	$html = ( 'lightning' !== $talk_type ) ? '<div class="wpcsp-speakers">' . $speakers_heading . $html . '</div>' : $html;

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
	$filetypes = array( '.ppt', '.pptx', '.pdf', '.key', '.otp', '.pps', '.ppsx' );
	$list      = array();
	$extension = 'url';
	if ( is_array( $slides ) ) {
		foreach ( $slides as $slide ) {
			foreach ( $filetypes as $ext ) {
				$extension = 'url';
				$ends_with = wpcs_ends_with( $slide, $ext );
				if ( $ends_with ) {
					$extension = $ext;
					break;
				}
			}
			if ( 'url' !== $extension ) {
				$class  = sanitize_title( $extension );
				$list[] = '<a href="' . esc_url( $slide ) . '" class="' . $class . '">' . 'Slides (' . strtoupper( $class ) . ')</a>';
			} else {
				$list[] = ( esc_url( $slide ) ) ? '<a href="' . esc_url( $slide ) . '">Slides (URL)</a>' : '';
			}
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
		echo wp_kses_post( '<div class="wpcs-slides-wrapper"><h3>' . __( 'Slides', 'wpa-conference' ) . '</h3><ul class="wpcs-slides">' . $output . '</ul></div>' );
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
	$filetypes = array( '.doc', '.docx', '.xls', '.xlsx', '.pdf' );
	$list      = array();
	$extension = 'url';
	if ( is_array( $resources ) ) {
		foreach ( $resources as $resource ) {
			foreach ( $filetypes as $ext ) {
				$extension = 'url';
				$ends_with = wpcs_ends_with( $resource, $ext );
				if ( $ends_with ) {
					$extension = $ext;
					break;
				}
			}
			if ( 'url' !== $extension ) {
				$name = '';
				if ( current_user_can( 'manage_options' ) ) {
					$parts = wp_parse_url( $resource );
					$path  = $parts['path'];
					$split = explode( '/', $path );
					$name  = end( $split );
					$name  = ' - ' . str_replace( array( '-', '_', $extension ), ' ', $name );
				}
				$class  = sanitize_title( $extension );
				$list[] = '<a href="' . esc_url( $resource ) . '" class="' . $class . '">' . 'Session Resource (' . strtoupper( $class ) . ')' . $name . '</a>';
			} else {
				$list[] = ( esc_url( $resource ) ) ? '<a href="' . esc_url( $resource ) . '">Session Resource (URL)</a>' : '';
			}
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
		echo wp_kses_post( '<div class="wpcs-resources-wrapper"><h3>' . __( 'Resources', 'wpa-conference' ) . '</h3><ul class="wpcs-resources">' . $output . '</ul></div>' );
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
