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
	$query = array(
		'post_type'      => 'wpcs_session',
		'post_status'    => 'publish',
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
	$return       = get_transient( 'wpcs_schedule' );
	$current_talk = '';
	if ( $return && ! isset( $_GET['reset_cache'] ) ) {
		return $return;
	} else {
		$return = '';
	}
	$begin = strtotime( '2022-11-02 14:45 UTC' );
	$end   = strtotime( '2022-11-03 15:00 UTC' );
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
		$time_html = '<div class="talk-header"><h2 class="talk-time" data-time="' . $datatime . '" id="talk-time-' . $time . '"><div class="time-wrapper"><span>' . $time . ':00 UTC<span class="screen-reader-text">,&nbsp;</span></span>' . ' </div></h2><div class="talk-wrapper">%s</div></div>';
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
					$talk_heading
					$control
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
 * Show the event start time banner.
 *
 * @return string
 */
function wpcs_banner() {
	$time   = time();
	$output = '';
	if ( $time < strtotime( '2022-11-02 14:50 UTC' ) ) {
		if ( $time < strtotime( '2022-11-02 15:00 UTC' ) ) {
			$start  = gmdate( 'F j, Y', strtotime( '2022-11-02 15:00 UTC' ) );
			$until  = human_time_diff( $time, strtotime( '2022-11-02 15:00 UTC' ) );
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
 * @return string Output HTML
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
			$first_name         = get_post_meta( $post_id, 'wpcsp_first_name', true );
			$last_name          = get_post_meta( $post_id, 'wpcsp_last_name', true );
			$full_name          = '<a href="' . get_permalink( $post_id ) . '">' . $first_name . ' ' . $last_name . '</a>';
			$list[]             = $first_name . ' ' . $last_name;
			$title_organization = array();
			$title              = ( get_post_meta( $post_id, 'wpcsp_title', true ) ) ? $title_organization[] = get_post_meta( $post_id, 'wpcsp_title', true ) : null;
			$organization       = ( get_post_meta( $post_id, 'wpcsp_organization', true ) ) ? $title_organization[] = get_post_meta( $post_id, 'wpcsp_organization', true ) : null;
			$headshot           = get_the_post_thumbnail( $post_id, 'thumbnail' );
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
 * Fetch Gravity Forms donors.
 *
 * @return array
 */
function wpcs_get_donors() {
	global $wpdb;
	$donors  = array();
	$query   = 'SELECT * FROM wp_gf_entry WHERE form_id = 6';
	$entries = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $entries as $entry ) {
		$meta = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM wp_gf_entry_meta WHERE entry_id = %d', $entry->id ) );
		$data = array();
		foreach ( $meta as $value ) {
			$data['payment_date'] = $entry->payment_date;
			$data['paid']         = $entry->payment_status;
			switch ( $value->meta_key ) {
				case '6':
					$data['amount'] = $entry->payment_amount;
					break;
				case '3.3':
					$data['first_name'] = $value->meta_value;
					break;
				case '3.6':
					$data['last_name'] = $value->meta_value;
					break;
				case '5':
					$data['email'] = $value->meta_value;
					break;
				case '4':
					$data['company'] = $value->meta_value;
					break;
				case '8.3':
					$data['city'] = $value->meta_value;
					break;
				case '8.4':
					$data['state'] = $value->meta_value;
					break;
				case '8.6':
					$data['country'] = $value->meta_value;
					break;
				case '11':
					$data['public'] = $value->meta_value;
					break;
			}
		}
		if ( 'Yes, you can list my name, company, and location on the WP Accessibility Day list of donors.' !== $data['public'] || 'Paid' !== $data['paid'] ) {
			continue;
		} else {
			$donors[] = $data;
		}
	}

	return array_reverse( $donors );
}

/**
 * Display donors who agreed to be displayed.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function wpcs_display_donors( $atts = array(), $content = '' ) {
	$output = get_transient( 'wpcs_donors' );
	if ( $output ) {
		return $output;
	} else {
		$output = '';
	}
	$donors    = wpcs_get_donors();
	$attendees = wpcs_get_microsponsors( true );
	$donors    = array_merge( $donors, $attendees );
	$output    = '';
	foreach ( $donors as $donor ) {
		$name    = $donor['first_name'] . ' ' . $donor['last_name'];
		$company = $donor['company'];
		if ( $donor['city'] === $donor['state'] ) {
			$loc = $donor['city'];
		} else {
			$loc = $donor['city'] . ', ' . $donor['state'];
		}
		$location = $loc . ', ' . $donor['country'];
		$location = ( $company ) ? ', ' . $location : $location;
		$date     = gmdate( 'F, Y', strtotime( $donor['payment_date'] ) );
		$output  .= '<li><strong>' . esc_html( $name ) . '</strong> <span class="date">' . $date . '</span><br /><span class="info">' . esc_html( $company . $location ) . '</span></li>';
	}
	$output = '<ul class="wpad-donors"' . $output . '</ul>';
	set_transient( 'wpcs_donors', $output, 300 );

	return $output;
}

/**
 * Fetch Gravity Forms microsponsors.
 *
 * @param bool $low_donors Fetching microsponsors or donors list.
 *
 * @return array
 */
function wpcs_get_microsponsors( $low_donors = false ) {
	global $wpdb;
	$sponsors = array();
	$query    = 'SELECT * FROM wp_gf_entry WHERE form_id = 13';
	$entries  = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $entries as $entry ) {
		$meta = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM wp_gf_entry_meta WHERE entry_id = %d', $entry->id ) );
		$data = array();
		foreach ( $meta as $value ) {
			$data['payment_date'] = $entry->payment_date;
			$data['paid']         = $entry->payment_status;
			$data['amount']       = str_replace( '$', '', $entry->payment_amount );
			switch ( $value->meta_key ) {
				case '1.3':
					$data['first_name'] = $value->meta_value;
					break;
				case '1.6':
					$data['last_name'] = $value->meta_value;
					break;
				case '5':
					$data['email'] = $value->meta_value;
					break;
				case '6':
					$data['company'] = $value->meta_value;
					break;
				case '7.3':
					$data['city'] = $value->meta_value;
					break;
				case '7.4':
					$data['state'] = $value->meta_value;
					break;
				case '7.6':
					$data['country'] = $value->meta_value;
					break;
				case '1.3':
					$data['fname'] = $value->meta_value;
					break;
				case '1.6':
					$data['lname'] = $value->meta_value;
					break;
				case '16':
					$data['link'] = $value->meta_value;
					break;
				case '12':
					$data['image'] = $value->meta_value;
					break;
				case '23':
					$data['paid'] = $value->meta_value;
					break;
				case '26':
					$data['public'] = $value->meta_value; // public microsponsor.
					break;
				case '20':
					$data['attendee'] = $value->meta_value; // public attendee.
					break;
			}
		}
		// If we're fetching low donors, use their attendee status & values not equal to 10.
		// If we're fetching microsponsors, use their sponsor status & values <= 10.
		$skip = ( $low_donors ) ? ( 'Yes' !== $data['attendee'] || 10 !== (int) $data['amount'] ) : ( 'Yes' !== $data['public'] || (int) $data['amount'] <= 10 );
		if ( $skip ) {
			continue;
		} else {
			$sponsors[] = $data;
		}
	}

	return $sponsors;
}

/**
 * Display microsponsors who agreed to be displayed.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function wpcs_display_microsponsors( $atts = array(), $content = '' ) {
	$args   = shortcode_atts(
		array(
			'maxheight' => '80px',
		),
		$atts,
		'microsponsors'
	);
	$mh     = $args['maxheight'];
	$output = get_transient( 'wpcs_microsponsors' );
	if ( $output ) {
		return $output;
	} else {
		$output = '';
	}
	$sponsors = wpcs_get_microsponsors();
	if ( is_array( $sponsors ) && count( $sponsors ) > 0 ) {
		foreach ( $sponsors as $sponsor ) {
			$name    = $sponsor['first_name'] . ' ' . $sponsor['last_name'];
			$company = $sponsor['company'];
			$link    = $sponsor['link'];
			$image   = $sponsor['image'];
			if ( ! $image ) {
				continue;
			}
			$wrap    = ( wp_http_validate_url( $link ) ) ? '<a href="' . esc_url( $link ) . '">' : '';
			$unwrap  = ( '' !== $wrap ) ? '</a>' : '';
			$label   = ( $company ) ? $company : $name;
			$output .= '<li class="wpcsp-sponsor"><div class="wpcsp-sponsor-description">' . $wrap . '<img class="wpcsp-sponsor-image" src="' . esc_url( $image ) . '" alt="' . esc_html( $label ) . '" style="width: auto; max-height: ' . esc_attr( $mh ) . '" />' . $unwrap . '</div></li>';
		}
	}
	$output = '<ul class="wpcsp-sponsor-list wpad-microsponsors">' . $output . '</ul>';
	set_transient( 'wpcs_microsponsors', $output, 300 );

	return $output;
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
