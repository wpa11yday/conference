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
 * Return an associative array of term_id -> term object mapping for all selected tracks.
 *
 * In case of 'all' is used as a value for $selected_tracks, information for all available tracks
 * gets returned.
 *
 * @param string $selected_tracks Comma-separated list of tracks to display or 'all'.
 *
 * @return array Associative array of terms with term_id as the key.
 */
function wpcs_get_schedule_tracks( $selected_tracks ) {
	$tracks = array();
	if ( 'all' === $selected_tracks ) {
		// Include all tracks.
		$tracks = get_terms( 'wpcs_track' );
	} else {
		// Loop through given tracks and look for terms.
		$terms = array_map( 'trim', explode( ',', $selected_tracks ) );

		foreach ( $terms as $term_slug ) {
			$term = get_term_by( 'slug', $term_slug, 'wpcs_track' );
			if ( $term ) {
				$tracks[ $term->term_id ] = $term;
			}
		}
	}

	return $tracks;
}

/**
 * Return a time-sorted associative array mapping timestamp -> track_id -> session id.
 *
 * @param string $schedule_date               Date for which the sessions should be retrieved.
 * @param bool   $tracks_explicitly_specified True if tracks were explicitly specified in the shortcode,
 *                                            false otherwise.
 * @param array  $tracks                      Array of terms for tracks from wpcs_get_schedule_tracks().
 *
 * @return array Associative array of session ids by time and track.
 */
function wpcs_get_schedule_sessions( $schedule_date, $tracks_explicitly_specified, $tracks ) {
	$query_args = array(
		'post_type'      => 'wpcs_session',
		'posts_per_page' => - 1,
		'meta_query'     => array(
			'relation' => 'AND',
			array(
				'key'     => '_wpcs_session_time',
				'compare' => 'EXISTS',
			),
		),
	);

	if ( $schedule_date && strtotime( $schedule_date ) ) {
		$query_args['meta_query'][] = array(
			'key'     => '_wpcs_session_time',
			'value'   => array(
				strtotime( $schedule_date ),
				strtotime( $schedule_date . ' +1 day' ),
			),
			'compare' => 'BETWEEN',
			'type'    => 'NUMERIC',
		);
	}

	if ( $tracks_explicitly_specified ) {
		// If tracks were provided, restrict the lookup in WP_Query.
		if ( ! empty( $tracks ) ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'wpcs_track',
				'field'    => 'id',
				'terms'    => array_values( wp_list_pluck( $tracks, 'term_id' ) ),
			);
		}
	}

	// Loop through all sessions and assign them into the formatted.
	// $sessions array: $sessions[ $time ][ $track ] = $session_id.
	// Use 0 as the track ID if no tracks exist.
	$sessions       = array();
	$sessions_query = new WP_Query( $query_args );

	foreach ( $sessions_query->posts as $session ) {
		$time  = absint( get_post_meta( $session->ID, '_wpcs_session_time', true ) );
		$terms = get_the_terms( $session->ID, 'wpcs_track' );

		if ( ! isset( $sessions[ $time ] ) ) {
			$sessions[ $time ] = array();
		}

		if ( empty( $terms ) ) {
			$sessions[ $time ][0] = $session->ID;
		} else {
			foreach ( $terms as $track ) {
				$sessions[ $time ][ $track->term_id ] = $session->ID;
			}
		}
	}

	// Sort all sessions by their key (timestamp).
	ksort( $sessions );

	return $sessions;
}

/**
 * Return an array of columns identified by term ids to be used for schedule table.
 *
 * @param array $tracks                      Array of terms for tracks from wpcs_get_schedule_tracks().
 * @param array $sessions                    Array of sessions from wpcs_get_schedule_sessions().
 * @param bool  $tracks_explicitly_specified True if tracks were explicitly specified in the shortcode,
 *                                           false otherwise.
 *
 * @return array Array of columns identified by term ids.
 */
function wpcs_get_schedule_columns( $tracks, $sessions, $tracks_explicitly_specified ) {
	$columns = array();

	// Use tracks to form the columns.
	if ( $tracks ) {
		foreach ( $tracks as $track ) {
			$columns[ $track->term_id ] = $track->term_id;
		}
	} else {
		$columns[0] = 0;
	}

	// Remove empty columns unless tracks have been explicitly specified.
	if ( ! $tracks_explicitly_specified ) {
		$used_terms = array();

		foreach ( $sessions as $time => $entry ) {
			if ( is_array( $entry ) ) {
				foreach ( $entry as $term_id => $session_id ) {
					$used_terms[ $term_id ] = $term_id;
				}
			}
		}

		$columns = array_intersect( $columns, $used_terms );
		unset( $used_terms );
	}

	return $columns;
}

/**
 * Update and preprocess input attributes for [schedule] shortcode.
 *
 * @param array $props Array of attributes from shortcode.
 *
 * @return array Array of attributes, after preprocessing.
 */
function wpcs_preprocess_schedule_attributes( $props ) {

	// Set Defaults.
	$attr = array(
		'date'         => null,
		'tracks'       => 'all',
		'session_link' => 'permalink', // permalink|anchor|none.
		'color_scheme' => 'light', // light/dark.
		'align'        => '', // alignwide|alignfull.
		'layout'       => 'table',
		'row_height'   => 'match',
		'content'      => 'none', // none|excerpt|full.
	);

	// Check if props exist. Fixes PHP errors when shortcode doesn't have any attributes.
	if ( $props ) :

		// Set Attribute values base on props.
		if ( isset( $props['date'] ) ) {
			$attr['date'] = $props['date'];
		}

		if ( isset( $props['color_scheme'] ) ) {
			$attr['color_scheme'] = $props['color_scheme'];
		}

		if ( isset( $props['layout'] ) ) {
			$attr['layout'] = $props['layout'];
		}

		if ( isset( $props['row_height'] ) ) {
			$attr['row_height'] = $props['row_height'];
		}

		if ( isset( $props['content'] ) ) {
			$attr['content'] = $props['content'];
		}

		if ( isset( $props['session_link'] ) ) {
			$attr['session_link'] = $props['session_link'];
		}

		if ( isset( $props['align'] ) && 'wide' === $props['align'] ) {
			$attr['align'] = 'alignwide';
		} elseif ( isset( $props['align'] ) && 'full' === $props['align'] ) {
			$attr['align'] = 'alignfull';
		}

		if ( isset( $props['tracks'] ) ) {
			$attr['tracks'] = $props['tracks'];
		}

		foreach ( array( 'tracks', 'session_link', 'color_scheme' ) as $key_for_case_sensitive_value ) {
			$attr[ $key_for_case_sensitive_value ] = strtolower( $attr[ $key_for_case_sensitive_value ] );
		}

		if ( ! in_array( $attr['session_link'], array( 'permalink', 'anchor', 'none' ), true ) ) {
			$attr['session_link'] = 'permalink';
		}

	endif;

	return $attr;
}

/**
 * Schedule Block and Shortcode Dynamic content Output.
 *
 * @param array $props Array of attributes from shortcode.
 *
 * @return array Array of attributes, after preprocessing.
 */
function wpcs_scheduleOutput( $props ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid

	$output = '';

	$dates = explode( ',', $props['date'] );
	if ( $dates ) {

		$current_tab = ( isset( $_GET['wpcs-tab'] ) && ! empty( $_GET['wpcs-tab'] ) ) ? intval( $_GET['wpcs-tab'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( count( $dates ) > 1 ) {

			$output .= '<div class="wpcsp-tabs tabs">';

			$output       .= '<div class="wpcsp-tabs-list" role="tablist" aria-label="Conference Schedule Tabs">';
				$tab_count = 1;
			foreach ( $dates as $date ) {

				if ( $current_tab ) {
					$tabindex = ( $tab_count === $current_tab ) ? 0 : -1;
					$selected = ( $tab_count === $current_tab ) ? 'true' : 'false';
				} else {
					$tabindex = ( 1 === $tab_count ) ? 0 : -1;
					$selected = ( 1 === $tab_count ) ? 'true' : 'false';
				}

				$output .= '<button class="wpcsp-tabs-list-button" role="tab" aria-selected="' . $selected . '" aria-controls="wpcsp-panel-' . $tab_count . '" id="tab-' . $tab_count . '" data-id="' . $tab_count . '" tabindex="' . $tabindex . '">' . gmdate( 'l, F j', strtotime( $date ) ) . '</button>';
				$tab_count++;
			}
			$output .= '</div>';
		}

		$panel_count = 1;
		foreach ( $dates as $date ) {
			$props['date'] = $date;

			if ( count( $dates ) > 1 ) {
				$props['date'] = $date;

				if ( $current_tab ) {
					$hidden = ( $panel_count === $current_tab ) ? '' : 'hidden';
				} else {
					$hidden = ( 1 === $panel_count ) ? '' : 'hidden';
				}

				$output .= '<div class="wpcsp-tabs-panel" id="wpcsp-panel-' . $panel_count . '" role="tabpanel" tabindex="0" aria-labelledby="tab-' . $panel_count . '" ' . $hidden . '>';
				$panel_count++;
			}

			$attr                        = wpcs_preprocess_schedule_attributes( $props );
			$tracks                      = wpcs_get_schedule_tracks( $attr['tracks'] );
			$tracks_explicitly_specified = 'all' !== $attr['tracks'];
			$sessions                    = wpcs_get_schedule_sessions( $attr['date'], $tracks_explicitly_specified, $tracks );
			$columns                     = wpcs_get_schedule_columns( $tracks, $sessions, $tracks_explicitly_specified );

			if ( 'table' === $attr['layout'] ) {

				$html  = '<div class="wpcs-schedule-wrapper ' . $attr['align'] . '">';
				$html .= '<table class="wpcs-schedule wpcs-color-scheme-' . $attr['color_scheme'] . ' wpcs-layout-' . $attr['layout'] . '" border="0">';
				$html .= '<thead>';
				$html .= '<tr>';

				// Table headings.
				$html .= '<th class="wpcs-col-time">' . esc_html__( 'Time', 'wpa-conference' ) . '</th>';
				foreach ( $columns as $term_id ) {
					$track = get_term( $term_id, 'wpcs_track' );
					$html .= sprintf(
						'<th class="wpcs-col-track"> <span class="wpcs-track-name">%s</span> <span class="wpcs-track-description">%s</span> </th>',
						isset( $track->term_id ) ? esc_html( $track->name ) : '',
						isset( $track->term_id ) ? esc_html( $track->description ) : ''
					);
				}

				$html .= '</tr>';
				$html .= '</thead>';

				$html .= '<tbody>';

				$time_format = get_option( 'time_format', 'g:i a' );

				foreach ( $sessions as $time => $entry ) {

					$skip_next = 0;
					$colspan   = 0;

					$columns_html = '';
					foreach ( $columns as $key => $term_id ) {

						// Allow the below to skip some items if needed.
						if ( $skip_next > 0 ) {
							$skip_next--;
							continue;
						}

						// For empty items print empty cells.
						if ( empty( $entry[ $term_id ] ) ) {
							$columns_html .= '<td class="wpcs-session-empty"></td>';
							continue;
						}

						// For custom labels print label and continue.
						if ( is_string( $entry[ $term_id ] ) ) {
							$columns_html .= sprintf( '<td colspan="%d" class="wpcs-session-custom">%s</td>', count( $columns ), esc_html( $entry[ $term_id ] ) );
							break;
						}

						// Gather relevant data about the session.
						$colspan              = 1;
						$classes              = array();
						$session              = get_post( $entry[ $term_id ] );
						$session_title        = apply_filters( 'the_title', $session->post_title );
						$session_tracks       = get_the_terms( $session->ID, 'wpcs_track' );
						$session_track_titles = is_array( $session_tracks ) ? implode( ', ', wp_list_pluck( $session_tracks, 'name' ) ) : '';
						$session_type         = get_post_meta( $session->ID, '_wpcs_session_type', true );
						$speakers             = get_post_meta( $session->ID, '_wpcs_session_speakers', true );

						if ( ! in_array( $session_type, array( 'session', 'custom', 'mainstage' ), true ) ) {
							$session_type = 'session';
						}

						// Add CSS classes to help with custom styles.
						if ( is_array( $session_tracks ) ) {
							foreach ( $session_tracks as $session_track ) {
								$classes[] = 'wpcs-track-' . $session_track->slug;
							}
						}

						$classes[] = 'wpcs-session-type-' . $session_type;
						$classes[] = 'wpcs-session-' . $session->post_name;

						$content  = '';
						$content .= '<div class="wpcs-session-cell-content">';

						// Session Content Header Filter.
						/**
						 * Filter session content header content.
						 *
						 * @hook wpcs_session_content_header
						 *
						 * @param {string} Empty string to use default header. HTML string to replace.
						 *
						 * @return {string}
						 */
						$wpcs_session_content_header = apply_filters( 'wpcs_session_content_header', '', $session->ID );
						$content                    .= ( '' !== $wpcs_session_content_header ) ? $wpcs_session_content_header : '';

						// Determine the session title.
						if ( 'permalink' === $attr['session_link'] && ( 'session' === $session_type || 'mainstage' === $session_type ) ) {
							$session_title_html = sprintf( '<h3><a class="wpcs-session-title" href="%s">%s</a></h3>', esc_url( get_permalink( $session->ID ) ), $session_title );
						} elseif ( 'anchor' === $attr['session_link'] && ( 'session' === $session_type || 'mainstage' === $session_type ) ) {
							$session_title_html = sprintf( '<h3><a class="wpcs-session-title" href="%s">%s</a></h3>', esc_url( '#' . get_post_field( 'post_name', $session->ID ) ), $session_title );
						} else {
							$session_title_html = sprintf( '<h3><span class="wpcs-session-title">%s</span></h3>', $session_title );
						}

						$content .= $session_title_html;

						if ( 'full' === $attr['content'] ) {
							$session_content = get_post_field( 'post_content', $session->ID );
							if ( $session_content ) {
								$content .= $session_content;
							}
						} elseif ( 'excerpt' === $attr['content'] ) {
							$session_excerpt = get_the_excerpt( $session->ID );
							if ( $session_excerpt ) {
								$content .= '<p>' . $session_excerpt . '</p>';
							}
						}

						// Add speakers names to the output string.
						if ( $speakers ) {
							$content .= sprintf( ' <span class="wpcs-session-speakers">%s</span>', esc_html( $speakers ) );
						}

						// Session Content Footer Filter.
						$wpcs_session_content_footer = apply_filters( 'wpcs_session_content_footer', $session->ID );
						$content                    .= ( $wpcs_session_content_footer !== $session->ID ) ? $wpcs_session_content_footer : '';

						// End of cell-content.
						$content .= '</div>';

						$columns_clone = $columns;

						// If the next element in the table is the same as the current one, use colspan.
						if ( key( array_slice( $columns, -1, 1, true ) ) !== $key ) {
							foreach ( $columns_clone as $pair['key'] => $pair['value'] ) {
								if ( $pair['key'] === $key ) {
									continue;
								}

								if ( ! empty( $entry[ $pair['value'] ] ) && $entry[ $pair['value'] ] === $session->ID ) {
									$colspan++;
									$skip_next++;
								} else {
									break;
								}
							}
						}

						$columns_html .= sprintf( '<td colspan="%d" class="%s" data-track-title="%s" data-session-id="%s">%s</td>', $colspan, esc_attr( implode( ' ', $classes ) ), $session_track_titles, esc_attr( $session->ID ), $content );
					}

					$global_session      = count( $columns ) === $colspan ? ' wpcs-global-session' . ' wpcs-global-session-' . esc_html( $session_type ) : '';
					$global_session_slug = $global_session ? ' ' . sanitize_html_class( sanitize_title_with_dashes( $session->post_title ) ) : '';

					$html .= sprintf( '<tr class="%s">', sanitize_html_class( 'wpcs-time-' . gmdate( $time_format, $time ) ) . $global_session . $global_session_slug );
					$html .= sprintf( '<td class="wpcs-time">%s</td>', str_replace( ' ', '&nbsp;', esc_html( gmdate( $time_format, $time ) ) ) );
					$html .= $columns_html;
					$html .= '</tr>';
				}

				$html   .= '</tbody>';
				$html   .= '</table>';
				$html   .= '</div>';
				$output .= $html;

			} elseif ( 'grid' === $attr['layout'] ) {

				$html          = '';
				$schedule_date = $attr['date'];
				$time_format   = get_option( 'time_format', 'g:i a' );

				$query_args = array(
					'post_type'      => 'wpcs_session',
					'posts_per_page' => - 1,
					'meta_key'       => '_wpcs_session_time',
					'orderby'        => 'meta_value_num',
					'order'          => 'ASC',
					'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'AND',
						array(
							'key'     => '_wpcs_session_time',
							'compare' => 'EXISTS',
						),
					),
				);
				if ( $schedule_date && strtotime( $schedule_date ) ) {
					$query_args['meta_query'][] = array(
						'key'     => '_wpcs_session_time',
						'value'   => array(
							strtotime( $schedule_date ),
							strtotime( $schedule_date . ' +1 day' ),
						),
						'compare' => 'BETWEEN',
						'type'    => 'NUMERIC',
					);
				}
				if ( $tracks_explicitly_specified ) {
					// If tracks were provided, restrict the lookup in WP_Query.
					if ( ! empty( $tracks ) ) {
						$query_args['tax_query'][] = array(
							'taxonomy' => 'wpcs_track',
							'field'    => 'id',
							'terms'    => array_values( wp_list_pluck( $tracks, 'term_id' ) ),
						);
					}
				}

				$sessions_query = new WP_Query( $query_args );

				$array_times = array();
				foreach ( $sessions_query->posts as $session ) {
					$time     = absint( get_post_meta( $session->ID, '_wpcs_session_time', true ) );
					$end_time = absint( get_post_meta( $session->ID, '_wpcs_session_end_time', true ) );

					if ( ! in_array( $end_time, $array_times, true ) ) {
						array_push( $array_times, $end_time );
					}

					if ( ! in_array( $time, $array_times, true ) ) {
						array_push( $array_times, $time );
					}
				}
				asort( $array_times );
				// Reset PHP Array Index.
				$array_times = array_values( $array_times );
				// Remove last time item.
				unset( $array_times[ count( $array_times ) - 1 ] );

				switch ( $attr['row_height'] ) {
					case 'match':
						$row_height = '1fr';
						break;
					case 'auto':
						$row_height = 'auto';
						break;
				}

				$html .= '<style>
		@media screen and (min-width:700px) {
			#wpcs_' . $array_times[0] . '.wpcs-layout-grid {
				display: grid;
				grid-gap: 1em;
				grid-template-rows:
					[tracks] auto';

				foreach ( $array_times as $array_time ) {
					$html .= '[time-' . $array_time . '] ' . $row_height;
				}

					$html .= ';';

				$html .= 'grid-template-columns: [times] 4em';

					// Reset PHP Array Index.
					$tracks = array_values( $tracks );

					$len = count( $tracks );

					// Check the above var dump for issue.
				for ( $i = 0; $i < ( $len ); $i++ ) {
					if ( 0 === $i ) {
						$html .= '[' . $tracks[ $i ]->slug . '-start] 1fr';
					} elseif ( ( $len - 1 ) === $i ) {
						$html .= '[' . $tracks[ ( $i - 1 ) ]->slug . '-end ' . $tracks[ $i ]->slug . '-start] 1fr';
						$html .= '[' . $tracks[ $i ]->slug . '-end];';
					} else {
						$html .= '[' . $tracks[ ( $i - 1 ) ]->slug . '-end ' . $tracks[ $i ]->slug . '-start] 1fr';
					}
				}

					$html .= ';';

					$html .= '
			}
		}
		</style>';

				// Schedule Wrapper.
				$html .= '<div id="wpcs_' . $array_times[0] . '" class="schedule wpcs-schedule wpcs-color-scheme-' . $attr['color_scheme'] . ' wpcs-layout-' . $attr['layout'] . ' wpcs-row-height-' . $attr['row_height'] . '" aria-labelledby="schedule-heading">';

					// Track Titles.
				if ( $tracks ) {
					foreach ( $tracks as $track ) {
						$html .= sprintf(
							'<span class="wpcs-col-track" style="grid-column: ' . $track->slug . '; grid-row: tracks;"> <span class="wpcs-track-name">%s</span> <span class="wpcs-track-description">%s</span> </span>',
							isset( $track->term_id ) ? esc_html( $track->name ) : '',
							isset( $track->term_id ) ? esc_html( $track->description ) : ''
						);
					}
				}

				// Time Slots.
				if ( $array_times ) {
					foreach ( $array_times as $array_time ) {
						$html .= '<h2 class="wpcs-time" style="grid-row: time-' . $array_time . ';">' . gmdate( $time_format, $array_time ) . '</h2>';
					}
				}

				// Sessions.
				$query_args['meta_query'][] = array(
					'key'     => '_wpcs_session_time',
					'value'   => array(
						strtotime( $schedule_date ),
						strtotime( $schedule_date . ' +1 day' ),
					),
					'compare' => 'BETWEEN',
					'type'    => 'NUMERIC',
				);

				$sessions_query = new WP_Query( $query_args );

				foreach ( $sessions_query->posts as $session ) {
					$classes              = array();
					$session              = get_post( $session );
					$session_url          = get_the_permalink( $session->ID );
					$session_title        = apply_filters( 'the_title', $session->post_title );
					$session_tracks       = get_the_terms( $session->ID, 'wpcs_track' );
					$session_track_titles = is_array( $session_tracks ) ? implode( ', ', wp_list_pluck( $session_tracks, 'name' ) ) : '';
					$session_type         = get_post_meta( $session->ID, '_wpcs_session_type', true );
					$speakers             = apply_filters( 'wpcs_filter_session_speakers', get_post_meta( $session->ID, '_wpcs_session_speakers', true ), $session->ID );

					$start_time = get_post_meta( $session->ID, '_wpcs_session_time', true );
					$end_time   = get_post_meta( $session->ID, '_wpcs_session_end_time', true );
					$minutes    = ( $end_time - $start_time ) / 60;

					if ( ! in_array( $session_type, array( 'session', 'custom', 'mainstage' ), true ) ) {
						$session_type = 'session';
					}

					$tracks_array       = array();
					$tracks_names_array = array();
					if ( $session_tracks ) {
						foreach ( $session_tracks as $session_track ) {

							// Check if the session track is in the main tracks array.
							if ( $track ) {
								$remove_track = false;
								foreach ( $tracks as $track ) {
									if ( $track->slug === $session_track->slug ) {
										$remove_track = true;
									}
								}
							}

							// Don't save session track if track doesn't exist.
							if ( true === $remove_track ) {
								$tracks_array[]       = $session_track->slug;
								$tracks_names_array[] = $session_track->name;
							}
						}
					}
					$tracks_classes = implode( ' ', $tracks_array );

					// Add CSS classes to help with custom styles.
					if ( is_array( $session_tracks ) ) {
						foreach ( $session_tracks as $session_track ) {
							$classes[] = 'wpcs-track-' . $session_track->slug;
						}
					}
					$classes[] = 'wpcs-session-type-' . $session_type;
					$classes[] = 'wpcs-session-' . $session->post_name;

					$tracks_array_length = esc_attr( count( $tracks_array ) );

					$grid_column_end = '';
					if ( 1 !== (int) $tracks_array_length ) {
						$grid_column_end = ' / ' . $tracks_array[ $tracks_array_length - 1 ];
					}

					$html .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . ' ' . $tracks_classes . '" style="grid-column: ' . $tracks_array[0] . $grid_column_end . '; grid-row: time-' . $start_time . ' / time-' . $end_time . ';">';

					$html .= '<div class="wpcs-session-cell-content">';

						// Session Content Header Filter.
						$wpcs_session_content_header = apply_filters( 'wpcs_session_content_header', $session->ID );
						$html                       .= ( $wpcs_session_content_header !== $session->ID ) ? $wpcs_session_content_header : '';

						// Determine the session title.
					if ( 'permalink' === $attr['session_link'] && ( 'session' === $session_type || 'mainstage' === $session_type ) ) {
						$html .= sprintf( '<h3><a class="wpcs-session-title" href="%s">%s</a></h3>', esc_url( get_permalink( $session->ID ) ), $session_title );
					} elseif ( 'anchor' === $attr['session_link'] && ( 'session' === $session_type || 'mainstage' === $session_type ) ) {
						$html .= sprintf( '<h3><a class="wpcs-session-title" href="%s">%s</a></h3>', esc_url( '#' . get_post_field( 'post_name', $session->ID ) ), $session_title );
					} else {
						$html .= sprintf( '<h3><span class="wpcs-session-title">%s</span></h3>', $session_title );
					}

						// Add time to the output string.
						$html     .= '<div class="wpcs-session-time">';
							$html .= gmdate( $time_format, $start_time ) . ' - ' . gmdate( $time_format, $end_time );
					if ( $minutes ) {
						$html .= '<span class="wpcs-session-time-duration"> (' . $minutes . ' min)</span>';
					}
						$html .= '</div>';

						// Add tracks to the output string.
						$html .= '<div class="wpcs-session-track">' . implode( ', ', $tracks_names_array ) . '</div>';

					if ( 'full' === $attr['content'] ) {
						$content = get_post_field( 'post_content', $session->ID );
						if ( $content ) {
							$html .= $content;
						}
					} elseif ( 'excerpt' === $attr['content'] ) {
						$excerpt = get_the_excerpt( $session->ID );
						if ( $excerpt ) {
							$html .= '<p>' . $excerpt . '</p>';
						}
					}

						// Add speakers names to the output string.
					if ( $speakers ) {
						$html .= sprintf( ' <div class="wpcs-session-speakers">%s</div>', wp_specialchars_decode( $speakers ) );
					}

						// Session Content Footer Filter.
						$wpcs_session_content_footer = apply_filters( 'wpcs_session_content_footer', $session->ID );
						$html                       .= ( $wpcs_session_content_footer !== $session->ID ) ? $wpcs_session_content_footer : '';

					$html .= '</div>';

					$html .= '</div>';
				}

				$html .= '</div>';
				if ( get_option( 'wpcs_field_byline' ) ) {
					$html .= '<div class="wpcs-promo"><small>Powered by <a href="https://wpconferenceschedule.com" target="_blank">WP Conference Schedule</a></small></div>';
				}

				$output .= $html;

			}

			$output .= '</div>';
		}
	}

	if ( count( $dates ) > 1 ) {
		$output .= '</div"><!-- tabs -->';
	}

	return $output;

}

/**
 * Return HTML from a WordPress profile via shortcode to show attendees.
 *
 * @param array $atts Shortcode attributes with one parameter, user ID.
 *
 * @return string
 */
function wpad_shortcode_people( $atts ) {
	$atts = shortcode_atts( array(
		'id' => '',
	), $atts );

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
	$output = get_transient( 'wpad_attendees' );
	if ( $output ) {
		return $output;
	} else {
		$output = '';
	}
	$users  = get_users( $args );
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
			$loc = ( '' == $state ) ? $city : $city . ', ' . $state;
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
		$social = ( ! empty( $icons ) ) ? '<div class="attendee-social">' . implode( ' ', $icons ) . '</div>' : '';
		$output .= '<li>' . $gravatar . '<div class="attendee-info"><h2 class="attendee-name">' . $name . '</h2>' . $company . $location . $social . '</div></li>';
	}
	$output = '<ul class="wpad-attendees alignwide">' . $output . '</ul>';
	set_transient( 'wpad_attendees', $output, 300 );

	return $output;
}
add_shortcode( 'attendees', 'wpad_shortcode_people' );

/**
 * Get sessions scheduled for conference.
 *
 * @return array
 */
function wpad_get_sessions() {
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
	$posts    = get_posts( $query );

	return $posts;
}

add_shortcode( 'schedule', 'wpaccessibilityday_schedule' );
/**
 * Generate schedule for WP Accessibility Day.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function wpaccessibilityday_schedule( $atts, $content ) {
	$return       = get_transient( 'wpad_schedule' );
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
			'start'     => '15',
		),
		$atts,
		'wpaccessibilityday_schedule'
	);

	$posts    = wpad_get_sessions();
	$schedule = array();
	foreach( $posts as $post_ID ) {
		$time              = gmdate( 'H', get_post_meta( $post_ID, '_wpcs_session_time', true ) );
		$datatime          = gmdate( 'Y-m-d\TH:i:s\Z', get_post_meta( $post_ID, '_wpcs_session_time', true ) );
		$schedule[ $time ] = array( 'id' => $post_ID, 'ts' => $datatime );
	}
	$start = $args['start'] - 24;
	$n     = 1;
	for( $i = $start; $i < $args['start']; $i++ ) {
		$number = ( isset( $_GET['buttonsoff'] ) ) ? str_pad( $n, 2, '0', STR_PAD_LEFT ) : '';
		$is_first = false;
		if ( $i === $start ) {
			$is_first = true;
		}
		if ( absint( $i ) != $i ) {
			$base = 24 - absint( $i );
		} else {
			$base = $i;
		}

		$time       = str_pad( $base, 2, '0', STR_PAD_LEFT );
		$is_current = false;

		$text    = '';
		$is_next = false;
		if ( ( time() > $begin - HOUR_IN_SECONDS ) && ( time() < $end ) ) {
			if ( ( $begin < time() && time() < $end ) && date( 'H' ) == $time && (int) date( 'i' ) < 50 || date( 'G' ) == (int) $time - 1 && (int) date( 'i' ) > 50 ) {
				$is_current = true;
			}
			if ( (int) date( 'i' ) < 50 ) {
				$text = "Now speaking: ";
			} else {
				$is_next = true;
				$text    = "Up next: ";
			}
		} else if ( ! ( time() > $end ) ) {
			$is_next = true;
			$text    = false;
		}
		
		$datatime  = $schedule[ $time ]['ts'];
		$time_html = '<div class="talk-header"><h2 class="talk-time" data-time="' . $datatime . '" id="talk-time-' . $time . '"><div class="time-wrapper"><span>' . $time . ':00 UTC<span class="screen-reader-text">,&nbsp;</span></span>' . ' </div></h2><div class="talk-wrapper">%s</div></div>';
		$talk_ID   = $schedule[ $time ]['id'];
		if ( $talk_ID ) {
			$talk_type = sanitize_html_class( get_post_meta( $talk_ID, '_wpcs_session_type', true ) );
			$speakers  = wpad_session_speakers( $talk_ID, $talk_type );
			$sponsors  = wpad_session_sponsors( $talk_ID );
			$talk      = get_post( $talk_ID );

			$talk_attr_id  = sanitize_title( $talk->post_title );
			$talk_title    = '<a href="' . esc_url( get_the_permalink( $talk_ID ) ) . '" id="talk-' . $talk_attr_id . '">' . $talk->post_title . '</a>' . " <span class='session_id'>$number</span>";
			$talk_label    = ( 'panel' === $talk_type ) ? '<strong>Panel:</strong> ' : '';
			$talk_title   .= '<div class="talk-speakers">' . $talk_label . implode( ', ', $speakers['list'] ) . '</div>';
			$talk_heading  = sprintf( $time_html, ' ' . $talk_title );
			if ( 'lightning' !== $talk_type ) {
				$wrap   = '<div class="wp-block-column">';
				$unwrap = '</div>';
			} else {
				$wrap   = '';
				$unwrap = '';
			}
			$talk_output  = $wrap . $sponsors;
			$talk_output .= ( 'lightning' != $talk_type ) ? '<div class="talk-description">' . wp_trim_words( $talk->post_content ) . '</div>' : '';
			$talk_output .= $slides . $unwrap;
			$talk_output .= $wrap . $speakers['html'] . $unwrap;

			$session_id = sanitize_title( $talk->post_title );
			$hidden     =  ( isset( $_GET['buttonsoff'] ) ) ? '' : 'hidden';
			$control    = ( isset( $_GET['buttonsoff'] ) ) ? '' : '<button type="button" class="toggle-details" aria-expanded="false"><span class="dashicons-plus dashicons" aria-hidden="true"></span> View Details<span class="screen-reader-text">: ' . $talk->post_title . '</span></button>';

			if ( $is_current || ( $is_first && $is_next ) ) {
				$hidden       = '';
				$control      = str_replace( '"false"', '"true"', $control );
				$control      = str_replace( '-plus', '-minus', $control );
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

	$opening_remarks = "<div class='wp-block-group schedule'>
				<div class='wp-block-group__inner-container'>
					<div class='wp-block-columns'>
						<div class='wp-block-column'>
							<div class='talk-header'>
								<h2 class='talk-time' data-time='2022-11-02T14:45:00Z'><div class='time-wrapper'><span>14:45 UTC<span class='screen-reader-text'>,&nbsp;</span></span></div></h2>
								<div class='talk-wrapper'>Opening Remarks</div>
							</div>
							<div class='talk-description'>
								<p>Joe Dolson, co-lead organizer of WP Accessibility Day will kick off the event with brief opening comments.</p>
							</div>
						</div>
					</div>
				</div>
			</div>";

	$links  = wpad_banner();
	$return = $links . $current_talk . $opening_remarks . implode( PHP_EOL, $output );
	set_transient( 'wpad_schedule', $return, 150 );

	return $return;
}

/**
 * Get speakers for schedule.
 *
 * @param int $session_id Talk post ID.
 *
 * @return string Output HTML
 */
function wpad_session_speakers( $session_id, $talk_type = 'session' ) {
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
			$first_name           = get_post_meta( $post_id, 'wpcsp_first_name', true );
			$last_name            = get_post_meta( $post_id, 'wpcsp_last_name', true );
			$full_name            = '<a href="' . get_permalink( $post_id ) . '">' . $first_name . ' ' . $last_name . '</a>';
			$list[]               = $first_name . ' ' . $last_name;
			$title_organization   = array();
			$title                = ( get_post_meta( $post_id, 'wpcsp_title', true ) ) ? $title_organization[] = get_post_meta( $post_id, 'wpcsp_title', true ) : null;
			$organization         = ( get_post_meta( $post_id, 'wpcsp_organization', true ) ) ? $title_organization[] = get_post_meta( $post_id, 'wpcsp_organization', true ) : null;
			$headshot             = get_the_post_thumbnail( $post_id, 'thumbnail' );
			$talk_html            = '';
			$wrap                 = '';
			$unwrap               = '';
			if ( 'lightning' === $talk_type ) {
				global $wpdb;
				$wrap      = '<div class="wp-block-column">';
				$unwrap    = '</div>';
				$result    = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $wpdb->postmeta WHERE meta_key = '_wpcs_session_speakers' AND meta_value = %d LIMIT 1", $post_id ) );

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
function wpad_session_sponsors( $session_id ) {
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
				$ends_with = wpad_ends_with( $slide, $ext );
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
				$ends_with = wpad_ends_with( $resource, $ext );
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
function wpad_ends_with( $source, $ext ) {
	$length = strlen( $ext );
	if ( 0 === $length ) {
		return true;
	}

	return ( substr( $source, -$length ) === $ext );
}
