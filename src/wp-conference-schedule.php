<?php
/**
 * Forked from WP Conference Schedule by Road Warrior Creative.
 *
 * @link              https://wpconferenceschedule.com
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       Conference Schedule
 * Plugin URI:        https://wpaccessibility.day
 * Description:       Generates people, sponsor, donor, and session post types & displays schedule information.
 * Version:           2.1.1
 * Author:            WP Accessibility Day
 * Author URI:        https://wpaccessibility.day
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wpa-conference
 * Update URI:        https://github.com/wpa11yday/
 *
 * @package           wpcsp
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Plugin directory.
define( 'WPCS_DIR', plugin_dir_path( __FILE__ ) );

// Version.
define( 'WPCS_VERSION', '2.1.1' );

// Plugin File URL.
define( 'PLUGIN_FILE_URL', __FILE__ );

// Includes.
require_once WPCS_DIR . 'inc/blocks.php';
require_once WPCS_DIR . 'inc/post-stati.php';
require_once WPCS_DIR . 'inc/post-types.php';
require_once WPCS_DIR . 'inc/taxonomies.php';
require_once WPCS_DIR . 'inc/schedule-output-functions.php';
require_once WPCS_DIR . 'inc/settings.php';
require_once WPCS_DIR . 'inc/social.php';
require_once WPCS_DIR . 'inc/activation.php';
require_once WPCS_DIR . 'inc/deactivation.php';
require_once WPCS_DIR . 'inc/uninstall.php';
require_once WPCS_DIR . 'inc/enqueue-scripts.php';
require_once WPCS_DIR . 'inc/meta.php';
require_once WPCS_DIR . 'inc/cmb2/init.php';
require_once WPCS_DIR . 'inc/cmb-field-select2/cmb-field-select2.php';
require_once WPCS_DIR . 'inc/cmb2-conditional-logic/cmb2-conditional-logic.php';

add_shortcode( 'schedule', 'wpcs_schedule' );
add_shortcode( 'donors', 'wpcsp_donors_shortcode', 10, 2 );
add_shortcode( 'partners', 'wpcsp_partners_shortcode', 10, 2 );
add_shortcode( 'microsponsors', 'wpcs_display_microsponsors', 10, 2 );
add_shortcode( 'attendees', 'wpcs_shortcode_people' );
add_shortcode( 'able', 'wpcs_get_video' );
add_shortcode( 'wpad', 'wpcs_event_start' );

/**
 * Redirect low level sponsors singular pages.
 *
 * @return void
 */
function wpcs_redirect_sponsors() {
	if ( is_singular( 'wpcsp_sponsor' ) ) {
		if ( has_term( 'micro', 'wpcsp_sponsor_level' ) ) {
			wp_redirect( get_option( 'wpcsp_field_sponsors_page_url', home_url() ) );
			exit;
		}
	}
}
add_action( 'template_redirect', 'wpcs_redirect_sponsors' );

/**
 * The Conference Schedule output and meta.
 */
class WPCS_Conference_Schedule {

	/**
	 * Fired when plugin file is loaded.
	 */
	public function __construct() {

		add_action( 'admin_init', array( $this, 'wpcs_admin_init' ) );
		add_action( 'admin_print_styles', array( $this, 'wpcs_admin_css' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wpcs_admin_enqueue_scripts' ) );
		add_action( 'admin_print_footer_scripts', array( $this, 'wpcs_admin_print_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wpcs_enqueue_scripts' ) );
		add_action( 'save_post', array( $this, 'wpcs_save_post_session' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $this, 'wpcs_manage_post_types_columns_output' ), 10, 2 );
		add_action( 'cmb2_admin_init', array( $this, 'wpcs_session_metabox' ) );
		add_action( 'add_meta_boxes', array( $this, 'wpcs_add_meta_boxes' ) );
		add_filter( 'wpcs_filter_session_speaker_meta_field', array( $this, 'filter_session_speaker_meta_field' ), 11, 1 );
		add_shortcode( 'wpcs_sponsors', array( $this, 'shortcode_sponsors' ) );
		add_shortcode( 'wpcs_speakers', array( $this, 'shortcode_speakers' ) );
		add_filter( 'wpcs_filter_session_speakers', array( $this, 'filter_session_speakers' ), 11, 2 );
		add_filter( 'wpcs_session_content_header', array( $this, 'session_content_header' ), 11, 1 );
		add_action( 'wpsc_single_taxonomies', array( $this, 'single_session_tags' ) );
		add_filter( 'wpcs_filter_single_session_speakers', array( $this, 'filter_single_session_speakers' ), 11, 2 );
		add_filter( 'wpcs_session_content_footer', array( $this, 'session_sponsors' ), 11, 1 );
		add_filter( 'manage_wpcs_session_posts_columns', array( $this, 'wpcs_manage_post_types_columns' ) );
		add_filter( 'manage_edit-wpcs_session_sortable_columns', array( $this, 'wpcs_manage_sortable_columns' ) );
		add_filter( 'display_post_states', array( $this, 'wpcs_display_post_states' ) );
	}

	/**
	 * Runs during admin_init.
	 */
	public function wpcs_admin_init() {
		add_action( 'pre_get_posts', array( $this, 'wpcs_admin_pre_get_posts' ) );
	}

	/**
	 * Runs during pre_get_posts in admin.
	 *
	 * @param WP_Query $query The query.
	 */
	public function wpcs_admin_pre_get_posts( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		$current_screen = get_current_screen();

		// Order by session time.
		if ( 'edit-wpcs_session' === $current_screen->id && $query->get( 'orderby' ) === '_wpcs_session_time' ) {
			$query->set( 'meta_key', '_wpcs_session_time' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @uses wp_enqueue_style()
	 * @uses wp_enqueue_script()
	 *
	 * @return void
	 */
	public function wpcs_admin_enqueue_scripts() {
		// Doesn't do anything since removing datepicker.
	}

	/**
	 * Print JavaScript.
	 */
	public function wpcs_admin_print_scripts() {
		global $post_type;

		// DatePicker for Session posts.
		if ( 'wpcs_session' === $post_type ) :
			?>

			<script type="text/javascript">
				jQuery( document ).ready( function( $ ) {
					$( '#wpcs-session-hour' ).on( 'change', function(e) {
						let selected = $( this ).val();
						let date     = $( '#wpcs-session-hour option[value=' + selected + ']' ).data( 'date' );
						$( '#wpcs-session-date' ).val( date );
						$( this ).parent( 'p' ).find( 'span.description' ).remove();
						$( this ).parent( 'p' ).append( '<span class="description" id="wpcs-session-hour-description">' + date + '</span>' );
					});
				} );
			</script>

			<?php
		endif;
	}

	/**
	 * Enqueue the scripts and styles.
	 *
	 * @uses wp_enqueue_style()
	 * @uses wp_enqueue_script()
	 */
	public function wpcs_enqueue_scripts() {
		wp_enqueue_style(
			'font-awesome',
			'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
			array(),
			'1.0.0'
		);
		$version   = ( str_contains( home_url(), 'staging.wpaccessibility.day' ) ) ? WPCS_VERSION . '-' . wp_rand( 1000, 10000 ) : WPCS_VERSION;
		$timezones = ( SCRIPT_DEBUG ) ? 'assets/js/conference-time-zones.js' : 'assets/js/conference-time-zones-min.js';
		$mastodon  = ( SCRIPT_DEBUG ) ? 'assets/js/mastodon-share.js' : 'assets/js/mastodon-share-min.js';
		wp_enqueue_script( 'wpcs_scripts', plugins_url( $timezones, __FILE__ ), array( 'jquery' ), $version );
		wp_enqueue_script( 'mastodon-share', plugins_url( $mastodon, __FILE__ ), array(), $version, true );
	}

	/**
	 * Runs during admin_print_styles, adds CSS for custom admin columns and block editor
	 *
	 * @uses wp_enqueue_style()
	 */
	public function wpcs_admin_css() {
		$version = ( str_contains( home_url(), 'staging.wpaccessibility.day' ) ) ? WPCS_VERSION . '-' . wp_rand( 1000, 10000 ) : WPCS_VERSION;
		wp_enqueue_style( 'wpcs-admin', plugins_url( '/assets/css/admin.css', __FILE__ ), array(), $version );
	}

	/**
	 * Update the session metadata.
	 *
	 * @return void
	 */
	public function wpcs_update_session_date_meta() {
		$post_id = null;
		if ( isset( $_REQUEST['post'] ) || isset( $_REQUEST['post_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$post_id = empty( $_REQUEST['post_ID'] ) ? absint( $_REQUEST['post'] ) : absint( $_REQUEST['post_ID'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$session_date = get_post_meta( $post_id, '_wpcs_session_date', true );
		$session_time = get_post_meta( $post_id, '_wpcs_session_time', true );

		if ( $post_id && ! $session_date && $session_time ) {
			update_post_meta( $post_id, '_wpcs_session_date', $session_time );
		}
	}

	/**
	 * Session CMB metabox.
	 *
	 * @return void
	 */
	public function wpcs_session_metabox() {

		$cmb = new_cmb2_box(
			array(
				'id'           => 'wpcs_session_metabox',
				'title'        => __( 'Session Information', 'wpa-conference' ),
				'object_types' => array( 'wpcs_session' ), // Post type.
				'context'      => 'normal',
				'priority'     => 'high',
				'show_names'   => true, // Show field names on the left.
			)
		);

		// filter speaker meta field.
		if ( has_filter( 'wpcs_filter_session_speaker_meta_field' ) ) {
			/**
			 * Filter session speaker meta field.
			 *
			 * @hook wpcs_filter_session_speaker_meta_field
			 *
			 * @param {object} CMB2 generated metabox for speaker meta fields.
			 *
			 * @return {object}
			 */
			$cmb = apply_filters( 'wpcs_filter_session_speaker_meta_field', $cmb );
		} else {
			// Speaker Name(s).
			$cmb->add_field(
				array(
					'name' => __( 'Speaker Name(s)', 'wpa-conference' ),
					'id'   => '_wpcs_session_speakers',
					'type' => 'text',
				)
			);
		}
	}

	/**
	 * Fired during add_meta_boxes, adds extra meta boxes to our custom post types.
	 */
	public function wpcs_add_meta_boxes() {
		add_meta_box( 'session-info', __( 'Session Info', 'wpa-conference' ), array( $this, 'wpcs_metabox_session_info' ), 'wpcs_session', 'normal' );
	}

	/**
	 * Session info metabox.
	 *
	 * @return void
	 */
	public function wpcs_metabox_session_info() {
		$post = get_post();
		if ( ! $post ) {
			return;
		}
		$session_time    = absint( get_post_meta( $post->ID, '_wpcs_session_time', true ) );
		$start_date      = gmdate( 'Y-m-d', strtotime( get_option( 'wpad_start_time' ) ) );
		$end_date        = gmdate( 'Y-m-d', strtotime( get_option( 'wpad_start_time' ) . ' + 24 hours' ) );
		$default_date    = gmdate( 'Y-m-d', strtotime( get_option( 'wpad_start_time' ) ) );
		$default_hours   = ( get_user_meta( wp_get_current_user()->ID, '_last_entered', true ) ) ? gmdate( 'G', get_user_meta( wp_get_current_user()->ID, '_last_entered', true ) ) : gmdate( 'G', strtotime( get_option( 'wpad_start_time' ) ) );
		$default_minutes = ( get_user_meta( wp_get_current_user()->ID, '_last_entered', true ) ) ? gmdate( 'i', get_user_meta( wp_get_current_user()->ID, '_last_entered', true ) ) : gmdate( 'i', strtotime( get_option( 'wpad_start_time' ) ) );

		$session_type    = get_post_meta( $post->ID, '_wpcs_session_type', true );
		$opening_remarks = get_option( 'wpcs_opening_remarks', false );
		$opening_remarks = ( (int) $post->ID === (int) $opening_remarks ) ? 'true' : 'false';
		if ( 'lightning' === $session_type || 'true' === $opening_remarks ) {
			// Lightning talks & opening remarks don't have their own times.
			$session_date    = '';
			$session_hours   = '';
			$session_minutes = '';
		} else {
			$session_date    = ( $session_time ) ? gmdate( 'Y-m-d', $session_time ) : $default_date;
			$session_hours   = ( $session_time ) ? gmdate( 'G', $session_time ) : $default_hours;
			$session_minutes = ( $session_time ) ? gmdate( 'i', $session_time ) : $default_minutes;
		}
		$session_youtube = get_post_meta( $post->ID, '_wpcs_youtube_id', true );
		$session_asl     = get_post_meta( $post->ID, '_wpcs_asl_id', true );

		wp_nonce_field( 'edit-session-info', 'wpcs-meta-session-info' );
		?>
		<fieldset>
			<legend><?php _e( 'Session Schedule', 'wpa-conference' ); ?></legend>
		<p>
			<input type="hidden" id="wpcs-session-date" name="wpcs-session-date" value="<?php echo esc_attr( $session_date ); ?>" />
			<label for="wpcs-session-hour"><?php esc_html_e( 'Hour:', 'wpa-conference' ); ?></label>

			<select id="wpcs-session-hour" name="wpcs-session-hour" aria-describedby="wpcs-session-hour-description">
				<option value="">Not assigned</option>
				<?php
				for ( $i = 0; $i <= 23; $i++ ) {
					$setdate = ( $i > 14 ) ? $start_date : $end_date;
					?>
				<option data-date="<?php echo esc_attr( $setdate ); ?>" value="<?php echo esc_attr( $i ); ?>" <?php selected( $i, $session_hours ); ?>>
					<?php echo esc_html( $i ); ?>
				</option>
					<?php
				}
				?>
			</select> :

			<label for="wpcs-session-minutes" class="screen-reader-text"><?php esc_html_e( 'Minutes:', 'wpa-conference' ); ?></label>
			<select id="wpcs-session-minutes" name="wpcs-session-minutes">
				<option value="00" <?php selected( 00, $session_minutes ); ?>>00</option>
				<option value="45" <?php selected( 45, $session_minutes ); ?>>45</option>
			</select>
		</p>
		<p>
			<label for="wpcs-session-type"><?php esc_html_e( 'Type:', 'wpa-conference' ); ?></label>
			<select id="wpcs-session-type" name="wpcs-session-type">
				<option value="session" <?php selected( $session_type, 'session' ); ?>><?php esc_html_e( 'Regular Session', 'wpa-conference' ); ?></option>
				<option value="panel" <?php selected( $session_type, 'panel' ); ?>><?php esc_html_e( 'Panel', 'wpa-conference' ); ?></option>
				<option value="lightning" <?php selected( $session_type, 'lightning' ); ?>><?php esc_html_e( 'Lightning Talk', 'wpa-conference' ); ?></option>
				<option value="lightning-group" <?php selected( $session_type, 'lightning-group' ); ?>><?php esc_html_e( 'Lightning Talks Group', 'wpa-conference' ); ?></option>
				<option value="custom" <?php selected( $session_type, 'custom' ); ?>><?php esc_html_e( 'Custom', 'wpa-conference' ); ?></option>
			</select>
		</p>
		<p>
			<input type="checkbox" id="wpcs-session-is-opening-remarks" name="wpcs-opening-remarks" value="true" <?php checked( 'true', $opening_remarks ); ?> aria-describedby="is-opening-remarks" /> <label for="wpcs-session-is-opening-remarks"><?php esc_html_e( 'Opening Remarks', 'wpa-conference' ); ?></label>
			<br /><em id="is-opening-remarks">Opening remarks are on a different schedule, this allows us to pull them out separately.</em>
		</p>
		</fieldset>
		<fieldset>
			<legend><?php _e( 'Session Recording', 'wpa-conference' ); ?></legend>
			<p>
				<label for="wpcs-session-youtube"><?php esc_html_e( 'YouTube ID', 'wpa-conference' ); ?></label>
				<input type="text" id="wpcs-session-youtube" name="wpcs-session-youtube" value="<?php echo esc_attr( $session_youtube ); ?>" />
			</p>
			<p>
				<label for="wpcs-session-asl"><?php esc_html_e( 'ASL YouTube ID', 'wpa-conference' ); ?></label>
				<input type="text" id="wpcs-session-asl" name="wpcs-session-asl" value="<?php echo esc_attr( $session_asl ); ?>" />
			</p>
		</fieldset>

		<?php
	}

	/**
	 * Fired when a post is saved, updates additional sessions metadada.
	 *
	 * @param  int      $post_id The post ID.
	 * @param  \WP_POST $post    The post.
	 * @return void
	 */
	public function wpcs_save_post_session( $post_id, $post ) {
		if ( wp_is_post_revision( $post_id ) || 'wpcs_session' !== $post->post_type ) {
			return;
		}

		if ( isset( $_POST['wpcs-meta-speakers-list-nonce'] ) && wp_verify_nonce( sanitize_text_field( $_POST['wpcs-meta-speakers-list-nonce'] ), 'edit-speakers-list' ) && current_user_can( 'edit_post', $post_id ) ) {

			// Update the text box as is for backwards compatibility.
			$speakers = sanitize_text_field( $_POST['wpcs-speakers-list'] ?? '' );
			update_post_meta( $post_id, '_conference_session_speakers', $speakers );
		}

		if ( isset( $_POST['wpcs-meta-session-info'] ) && wp_verify_nonce( sanitize_text_field( $_POST['wpcs-meta-session-info'] ), 'edit-session-info' ) ) {

			// Update session time.
			if ( isset( $_POST['wpcs-session-hour'] ) && is_numeric( $_POST['wpcs-session-hour'] ) ) {
				$session_time = strtotime(
					sprintf(
						'%s %d:%02d',
						sanitize_text_field( $_POST['wpcs-session-date'] ?? '' ),
						absint( $_POST['wpcs-session-hour'] ?? 0 ),
						absint( $_POST['wpcs-session-minutes'] ?? 0 )
					)
				);
			} else {
				$session_time = '';
			}
			update_post_meta( $post_id, '_wpcs_session_time', $session_time );
			update_user_meta( wp_get_current_user()->ID, '_last_entered', $session_time );

			// Update session type.
			$session_type = sanitize_text_field( $_POST['wpcs-session-type'] ?? '' );
			if ( ! in_array( $session_type, array( 'session', 'lightning', 'lightning-group', 'panel', 'custom' ), true ) ) {
				$session_type = 'session';
			}
			update_post_meta( $post_id, '_wpcs_session_type', $session_type );

			if ( isset( $_POST['wpcs-opening-remarks'] ) ) {
				update_option( 'wpcs_opening_remarks', $post_id );
			}

			// Update session speakers.
			$session_speakers = sanitize_text_field( $_POST['wpcs-session-speakers'] ?? '' );
			update_post_meta( $post_id, '_wpcs_session_speakers', $session_speakers );

			// Update session YouTube ID.
			$session_youtube = sanitize_text_field( $_POST['wpcs-session-youtube'] ?? '' );
			update_post_meta( $post_id, '_wpcs_youtube_id', $session_youtube );
			$session_asl = sanitize_text_field( $_POST['wpcs-session-asl'] ?? '' );
			update_post_meta( $post_id, '_wpcs_asl_id', $session_asl );

			// Update language captions.
			$languages = wpcs_get_languages( false );
			foreach ( $languages as $key => $language ) {
				if ( 'en' === $key ) {
					$session_caption = sanitize_text_field( $_POST['wpcs-session-caption'] ?? '' );
					update_post_meta( $post_id, '_wpcs_caption_url', $session_caption );
				} else {
					$session_caption = sanitize_text_field( $_POST[ 'wpcs-session-caption-' . $key ] ?? '' );
					update_post_meta( $post_id, '_wpcs_caption_url_' . $key, $session_caption );
				}
			}
		}
	}

	/**
	 * Filters our custom post types columns.
	 *
	 * @uses current_filter()
	 * @see __construct()
	 *
	 * @param  array $columns The columns.
	 * @return array
	 */
	public function wpcs_manage_post_types_columns( $columns ) {
		$current_filter = current_filter();

		switch ( $current_filter ) {
			case 'manage_wpcs_session_posts_columns':
				$columns                              = array_slice( $columns, 0, 1, true ) + array( 'conference_session_time' => __( 'Time', 'wpa-conference' ) ) + array_slice( $columns, 1, null, true );
				$columns['conference_session_slides'] = __( 'Slides', 'wpa-conference' );
				$columns['conference_session_asl']    = __( 'ASL', 'wpa-conference' );
				$columns['conference_session_video']  = __( 'Video', 'wpa-conference' );
				break;
			default:
		}

		return $columns;
	}

	/**
	 * Custom columns output
	 *
	 * This generates the output to the extra columns added to the posts lists in the admin.
	 *
	 * @see wpcs_manage_post_types_columns()
	 *
	 * @param  string $column  The columns.
	 * @param  int    $post_id The post ID.
	 * @return void
	 */
	public function wpcs_manage_post_types_columns_output( $column, $post_id ) {
		switch ( $column ) {

			case 'conference_session_time':
				$session_time = absint( get_post_meta( $post_id, '_wpcs_session_time', true ) );
				$session_time = ( $session_time ) ? gmdate( 'H:i', $session_time ) : '&mdash;';
				echo esc_html( $session_time );
				break;

			case 'conference_session_slides':
				$slides = wpcs_get_slides( $post_id );
				if ( ! empty( $slides ) ) {
					echo '<span class="dashicons dashicons-yes" aria-hidden="true" aria-label="Yes"></span>';
				}
				break;

			case 'conference_session_video':
				$youtube = get_post_meta( $post_id, '_wpcs_youtube_id', true );
				if ( $youtube ) {
					echo '<span class="dashicons dashicons-yes" aria-hidden="true" aria-label="Yes"></span>';
				}
				break;

			case 'conference_session_asl':
				$asl = get_post_meta( $post_id, '_wpcs_asl_id', true );
				if ( $asl ) {
					echo '<span class="dashicons dashicons-yes" aria-hidden="true" aria-label="Yes"></span>';
				}
				break;

			default:
		}
	}

	/**
	 * Additional sortable columns for WP_Posts_List_Table.
	 *
	 * @param  array $sortable The sortable columns.
	 * @return array
	 */
	public function wpcs_manage_sortable_columns( $sortable ) {
		$current_filter = current_filter();

		if ( 'manage_edit-wpcs_session_sortable_columns' === $current_filter ) {
			$sortable['conference_session_time'] = '_wpcs_session_time';
		}

		return $sortable;
	}

	/**
	 * Display an additional post label if needed.
	 *
	 * @param  mixed $states The post states.
	 * @return mixed
	 */
	public function wpcs_display_post_states( $states ) {
		$post = get_post();
		if ( ! get_post_type( $post ) ) {
			return null;
		}

		if ( 'wpcs_session' !== $post->post_type ) {
			return $states;
		}

		$session_type = get_post_meta( $post->ID, '_wpcs_session_type', true );
		if ( ! in_array( $session_type, array( 'session', 'lightning', 'lightning-group', 'panel', 'custom' ), true ) ) {
			$session_type = 'session';
		}

		if ( 'session' === $session_type ) {
			$states['wpcs-session-type'] = __( 'Session', 'wpa-conference' );
		} elseif ( 'lightning' === $session_type ) {
			$states['wpcs-session-type'] = __( 'Lightning Talks', 'wpa-conference' );
		} elseif ( 'lightning-group' === $session_type ) {
			$states['wpcs-session-type'] = __( 'Lightning Talk Group', 'wpa-conference' );
		} elseif ( 'panel' === $session_type ) {
			$states['wpcs-session-type'] = __( 'Panel', 'wpa-conference' );
		}

		return $states;
	}

	/**
	 * The [wpcs_sponsors] shortcode handler.
	 *
	 * @param  array  $attr    The shortcode attributes.
	 * @param  string $content The shortcode content.
	 * @return string
	 */
	public function shortcode_sponsors( $attr, $content ) {
		$attr = shortcode_atts(
			array(
				'link'           => 'none', // 'website' or 'post'.
				'title'          => 'hidden',
				'content'        => 'hidden',
				'excerpt_length' => 55,
				'heading_level'  => 'h2',
				'level'          => 'platinum,gold,silver,bronze,microsponsor,donor',
				'exclude'        => '',
				'type'           => 'display',
			),
			$attr
		);

		$levels  = ( '' !== $attr['level'] ) ? explode( ',', $attr['level'] ) : array();
		$exclude = ( '' !== $attr['exclude'] ) ? explode( ',', $attr['exclude'] ) : array();

		$attr['link'] = strtolower( $attr['link'] );
		$terms        = get_terms( 'wpcsp_sponsor_level', array( 'get' => 'all' ) );
		$sortable     = array();
		foreach ( $terms as $term ) {
			$sortable[ $term->slug ] = $term;
		}

		ob_start();
		?>

		<div class="wpcsp-sponsors">
			<?php
			if ( 'list' === $attr['type'] ) {
				echo '<ul>';
			}
			if ( is_array( $levels ) && ! empty( $levels ) ) {
				$terms = array();
				foreach ( $levels as $level ) {
					$terms[] = ( isset( $sortable[ $level ] ) ) ? $sortable[ $level ] : array();
				}
			}
			foreach ( $terms as $term ) :
				if ( empty( $term ) ) {
					continue;
				}
				if ( '' !== $attr['level'] && ( ! in_array( $term->slug, $levels, true ) || in_array( $term->slug, $exclude, true ) ) ) {
					continue;
				}
				$sponsors = new WP_Query(
					array(
						'post_type'      => 'wpcsp_sponsor',
						'order'          => 'ASC',
						'orderby'        => 'title',
						'posts_per_page' => -1,
						'taxonomy'       => $term->taxonomy,
						'term'           => $term->slug,
					)
				);

				if ( ! $sponsors->have_posts() ) {
					continue;
				}
				$header            = '';
				$footer            = '';
				$secondary_heading = 'span';
				if ( 'list' !== $attr['type'] ) {
					$heading_level = ( $attr['heading_level'] ) ? $attr['heading_level'] : 'h2';
					$header        = '<div class="wpcsp-sponsor-level wpcsp-sponsor-level-' . sanitize_html_class( $term->slug ) . '">
					<' . esc_html( $heading_level ) . ' class="wpcsp-sponsor-level-heading"><span>' . $term->name . '</span></' . esc_html( $heading_level ) . '>

					<ul class="wpcsp-sponsor-list">';
					$footer        = '</ul></div>';

					$secondary_heading = 'h3';
				}
				echo $header;
				while ( $sponsors->have_posts() ) :
					$sponsors->the_post();
					$website     = get_post_meta( get_the_ID(), 'wpcsp_website_url', true );
					$logo_height = ( get_term_meta( $term->term_id, 'wpcsp_logo_height', true ) ) ? get_term_meta( $term->term_id, 'wpcsp_logo_height', true ) . 'px' : 'auto';
					$logo_width  = ( 'micro' === $term->slug ) ? '140px' : 'auto';
					$image       = ( has_post_thumbnail() ) ? '<img class="wpcsp-sponsor-image" src="' . get_the_post_thumbnail_url( get_the_ID(), 'full' ) . '" alt="' . get_the_title( get_the_ID() ) . '" style="width: auto; max-width: ' . $logo_width . '; max-height: ' . $logo_height . ';"  />' : null;
					?>

					<li id="wpcsp-sponsor-<?php the_ID(); ?>" class="wpcsp-sponsor">
						<?php if ( 'visible' === $attr['title'] ) : ?>
							<?php
							if ( 'website' === $attr['link'] && $website ) :
								?>
								<<?php echo $secondary_heading; ?>>
									<a href="<?php echo esc_url( $website ); ?>" rel="sponsored">
										<?php the_title(); ?>
									</a>
								</<?php echo $secondary_heading; ?>>
								<?php
							elseif ( 'post' === $attr['link'] ) :
								?>
								<<?php echo $secondary_heading; ?>>
									<a href="<?php echo esc_url( get_permalink() ); ?>">
										<?php the_title(); ?>
									</a>
								</<?php echo $secondary_heading; ?>>
								<?php
							else :
								?>
								<<?php echo $secondary_heading; ?>>
									<?php the_title(); ?>
								</<?php echo $secondary_heading; ?>>
								<?php
							endif;
						endif;
						if ( 'list' !== $attr['type'] ) {
							?>

						<div class="wpcsp-sponsor-description">
							<?php if ( 'website' === $attr['link'] && $website && $image ) : ?>
								<a href="<?php echo esc_url( $website ); ?>" rel="sponsored">
									<?php echo wp_kses_post( $image ); ?>
								</a>
							<?php elseif ( 'post' === $attr['link'] && $image ) : ?>
								<a href="<?php echo esc_url( get_permalink() ); ?>">
									<?php echo wp_kses_post( $image ); ?>
								</a>
							<?php else : ?>
								<?php echo wp_kses_post( $image ); ?>
							<?php endif; ?>

							<?php if ( 'full' === $attr['content'] ) : ?>
								<?php the_content(); ?>
							<?php elseif ( 'excerpt' === $attr['content'] ) : ?>
								<?php
								echo wp_kses_post(
									wpautop(
										wp_trim_words(
											get_the_content(),
											absint( $attr['excerpt_length'] ),
											apply_filters( 'excerpt_more', ' ' . '&hellip;' )
										)
									)
								);
								?>
							<?php endif; ?>
						</div>
							<?php
						}
						?>
					</li>
					<?php
				endwhile;
				echo $footer;
			endforeach;
			if ( 'list' === $attr['type'] ) {
				echo '</ul>';
			}
			?>
		</div>

		<?php

		wp_reset_postdata();
		$content = ob_get_contents();
		ob_end_clean();
		return $content;
	}

	/**
	 * The [wpcs_speakers] shortcode handler.
	 *
	 * @param  array $attr The shortcode attributes.
	 * @return string
	 */
	public function shortcode_speakers( $attr ) {
		global $post;

		// Prepare the shortcodes arguments.
		$attr = shortcode_atts(
			array(
				'show_image'     => true,
				'image_size'     => 150,
				'show_content'   => true,
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'desc',
				'speaker_link'   => '',
				'track'          => '',
				'groups'         => '',
				'columns'        => 1,
				'gap'            => 30,
				'align'          => 'left',
				'heading_level'  => 'h2',
			),
			$attr
		);

		foreach ( array( 'orderby', 'order', 'speaker_link' ) as $key_for_case_sensitive_value ) {
			$attr[ $key_for_case_sensitive_value ] = strtolower( $attr[ $key_for_case_sensitive_value ] );
		}

		$attr['show_image']   = $this->str_to_bool( $attr['show_image'] );
		$attr['show_content'] = $this->str_to_bool( $attr['show_content'] );
		$attr['orderby']      = in_array( $attr['orderby'], array( 'date', 'title', 'rand' ), true ) ? $attr['orderby'] : 'date';
		$attr['order']        = in_array( $attr['order'], array( 'asc', 'desc' ), true ) ? $attr['order'] : 'desc';
		$attr['speaker_link'] = in_array( $attr['speaker_link'], array( 'permalink' ), true ) ? $attr['speaker_link'] : '';
		$attr['track']        = array_filter( explode( ',', $attr['track'] ) );
		$attr['groups']       = array_filter( explode( ',', $attr['groups'] ) );

		// Fetch all the relevant sessions.
		$session_args = array(
			'post_type'      => 'wpcs_session',
			'posts_per_page' => -1,
		);

		if ( ! empty( $attr['track'] ) ) {
			$session_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wpcs_track',
					'field'    => 'slug',
					'terms'    => $attr['track'],
				),
			);
		}

		$sessions = get_posts( $session_args );

		// Parse the sessions.
		$speaker_ids     = array();
		$speakers_tracks = array();
		foreach ( $sessions as $session ) {
			// Get the speaker IDs for all the sessions in the requested tracks.
			$session_speaker_ids = get_post_meta( $session->ID, '_rwc_cs_speaker_id' );
			$speaker_ids         = array_merge( $speaker_ids, $session_speaker_ids );

			// Map speaker IDs to their corresponding tracks.
			$session_terms = wp_get_object_terms( $session->ID, 'RWC_track' );
			foreach ( $session_speaker_ids as $speaker_id ) {
				if ( isset( $speakers_tracks[ $speaker_id ] ) ) {
					$speakers_tracks[ $speaker_id ] = array_merge( $speakers_tracks[ $speaker_id ], wp_list_pluck( $session_terms, 'slug' ) );
				} else {
					$speakers_tracks[ $speaker_id ] = wp_list_pluck( $session_terms, 'slug' );
				}
			}
		}

		// Remove duplicate entries.
		$speaker_ids = array_unique( $speaker_ids );
		foreach ( $speakers_tracks as $speaker_id => $tracks ) {
			$speakers_tracks[ $speaker_id ] = array_unique( $tracks );
		}

		// Fetch all specified speakers.
		$speaker_args = array(
			'post_type'      => 'wpcsp_speaker',
			'posts_per_page' => intval( $attr['posts_per_page'] ),
			'orderby'        => $attr['orderby'],
			'order'          => $attr['order'],
		);

		if ( ! empty( $attr['track'] ) ) {
			$speaker_args['post__in'] = $speaker_ids;
		}

		if ( ! empty( $attr['groups'] ) ) {
			$speaker_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'wpcsp_speaker_level',
					'field'    => 'slug',
					'terms'    => $attr['groups'],
				),
			);
		}

		$speakers = new WP_Query( $speaker_args );

		if ( ! $speakers->have_posts() ) {
			return '';
		}
		$heading_level = ( in_array( $attr['heading_level'], array( 'h2', 'h3', 'h4', 'h5', 'h6', 'p' ), true ) ) ? $attr['heading_level'] : 'h2';
		// Render the HTML for the shortcode.
		ob_start();
		?>

		<ul class="wpcsp-speakers" style="text-align: <?php echo esc_attr( $attr['align'] ); ?>; list-style: none; padding: 0; margin: 0; display: grid; grid-template-columns: repeat(<?php echo esc_attr( $attr['columns'] ); ?>, 1fr); grid-gap: <?php echo esc_attr( $attr['gap'] ); ?>px;">

			<?php
			while ( $speakers->have_posts() ) :
				$speakers->the_post();

				$post_id            = get_the_ID();
				$first_name         = get_post_meta( $post_id, 'wpcsp_first_name', true );
				$last_name          = get_post_meta( $post_id, 'wpcsp_last_name', true );
				$full_name          = $first_name . ' ' . $last_name;
				$title_organization = array();
				$title              = get_post_meta( $post_id, 'wpcsp_title', true );
				$organization       = get_post_meta( $post_id, 'wpcsp_organization', true );

				if ( $title ) {
					$title_organization[] = $title;
				}
				if ( $organization ) {
					$title_organization[] = $organization;
				}

				$speaker_classes = array( 'wpcsp-speaker', 'wpcsp-speaker-' . sanitize_html_class( $post->post_name ) );

				if ( isset( $speakers_tracks[ get_the_ID() ] ) ) {
					foreach ( $speakers_tracks[ get_the_ID() ] as $track ) {
						$speaker_classes[] = sanitize_html_class( 'wpcsp-track-' . $track );
					}
				}
				?>

				<li class="wpcsp-speaker" id="wpcsp-speaker-<?php echo sanitize_html_class( $post->post_name ); ?>" class="<?php echo esc_attr( implode( ' ', $speaker_classes ) ); ?>">
					<?php
					if ( has_post_thumbnail( $post_id ) && true === $attr['show_image'] ) {
						$image_size = $attr['image_size'];
						if ( ! has_image_size( $image_size ) && ! is_numeric( $image_size ) ) {
							$image_size = 'thumbnail';
						}
						$image_size = ( is_numeric( $image_size ) ) ? array( $image_size, $image_size ) : $image_size;
						$thumb_id   = get_post_thumbnail_id( $post_id );
						$thumb_size = wp_get_attachment_image_src( $thumb_id, $image_size );
						if ( 720 > $thumb_size[1] || 720 > $thumb_size[2] ) {
							$image_size = 'small-square';
						}
						echo get_the_post_thumbnail( $post_id, $image_size, array( 'class' => 'wpcsp-speaker-image' ) );
					}
					?>

					<<?php echo esc_html( $heading_level ); ?> class="wpcsp-speaker-name">
						<?php if ( 'permalink' === $attr['speaker_link'] ) : ?>

							<a href="<?php the_permalink(); ?>">
								<?php echo wp_kses_post( $full_name ); ?>
							</a>

						<?php else : ?>

							<?php echo wp_kses_post( $full_name ); ?>

						<?php endif; ?>
					</<?php echo esc_html( $heading_level ); ?>>

					<?php if ( $title_organization ) { ?>
						<p class="wpcsp-speaker-title-organization">
							<?php echo wp_kses_post( implode( ', ', $title_organization ) ); ?>
						</p>
					<?php } ?>

					<div class="wpcsp-speaker-description">
						<?php
						if ( true === $attr['show_content'] ) {
							the_content();}
						?>
					</div>
				</li>

			<?php endwhile; ?>

		</ul>

		<?php

		wp_reset_postdata();
		return ob_get_clean();
	}

	/**
	 * Convert a string representation of a boolean to an actual boolean
	 *
	 * @param string|bool $value The value to convert.
	 *
	 * @return bool
	 */
	public function str_to_bool( $value ) {
		if ( true === $value ) {
			return true;
		}

		if ( in_array( strtolower( (string) trim( $value ) ), array( 'yes', 'true', '1' ), true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Filter session speaker meta field
	 *
	 * @param \CMB2_Base $cmb The CMB2 object.
	 * @return \CMB2_Base
	 */
	public function filter_session_speaker_meta_field( $cmb ) {

		// Get speakers.
		$args     = array(
			'numberposts' => -1,
			'post_type'   => 'wpcsp_speaker',
			'post_status' => array( 'pending', 'publish', 'approved' ),
		);
		$speakers = get_posts( $args );
		$speakers = wp_list_pluck( $speakers, 'post_title', 'ID' );

		$cmb->add_field(
			array(
				'name'    => 'Speakers',
				'id'      => 'wpcsp_session_speakers',
				'desc'    => 'Select speakers. Drag to reorder.',
				'type'    => 'pw_multiselect',
				'options' => $speakers,
			)
		);

		// Get sponsors.
		$args    = array(
			'numberposts' => -1,
			'post_type'   => 'wpcsp_sponsor',
		);
		$sponsor = get_posts( $args );
		$sponsor = wp_list_pluck( $sponsor, 'post_title', 'ID' );

		$cmb->add_field(
			array(
				'name'    => 'Sponsors',
				'id'      => 'wpcsp_session_sponsors',
				'desc'    => 'Select sponsor. Drag to reorder.',
				'type'    => 'pw_multiselect',
				'options' => $sponsor,
			)
		);

		$cmb->add_field(
			array(
				'name'       => 'Slides',
				'id'         => 'wpcsp_session_slides',
				'type'       => 'text',
				'repeatable' => true,
			)
		);

		$cmb->add_field(
			array(
				'name'       => 'Slide Labels',
				'id'         => 'wpcsp_session_slide_labels',
				'type'       => 'text',
				'repeatable' => true,
			)
		);

		$cmb->add_field(
			array(
				'name'       => 'Resources',
				'id'         => 'wpcsp_session_resources',
				'type'       => 'text',
				'repeatable' => true,
			)
		);

		$cmb->add_field(
			array(
				'name'       => 'Resource Labels',
				'id'         => 'wpcsp_session_resource_labels',
				'type'       => 'text',
				'repeatable' => true,
			)
		);
		return $cmb;
	}

	/**
	 * Get the session speakers HTML.
	 *
	 * @param  string $speakers_typed The manually created speaker HTML.
	 * @param  int    $session_id     The session ID.
	 * @return string
	 */
	public function filter_session_speakers( $speakers_typed, $session_id ) {

		$speaker_display = get_post_meta( $session_id, 'wpcsp_session_speaker_display', true );

		if ( 'typed' === $speaker_display ) {
			return $speakers_typed;
		}

		$html         = '';
		$speakers_cpt = get_post_meta( $session_id, 'wpcsp_session_speakers', true );

		if ( $speakers_cpt ) {
			ob_start();
			foreach ( $speakers_cpt as $post_id ) {
				$first_name         = get_post_meta( $post_id, 'wpcsp_first_name', true );
				$last_name          = get_post_meta( $post_id, 'wpcsp_last_name', true );
				$full_name          = $first_name . ' ' . $last_name;
				$title_organization = array();

				?>
				<div class="wpcsp-session-speaker">

					<?php if ( $full_name ) { ?>
						<div class="wpcsp-session-speaker-name">
							<?php echo wp_kses_post( $full_name ); ?>
						</div>
					<?php } ?>

					<?php if ( $title_organization ) { ?>
						<div class="wpcsp-session-speaker-title-organization">
							<?php echo wp_kses_post( implode( ', ', $title_organization ) ); ?>
						</div>
					<?php } ?>

				</div>
				<?php
			}
			$html .= ob_get_clean();
		}

		return $html;
	}

	/**
	 * Get single session speaker HTML.
	 *
	 * @param  string $speakers_typed The manually typed speaker HTML.
	 * @param  int    $session_id     The session ID.
	 * @return string
	 */
	public function filter_single_session_speakers( $speakers_typed, $session_id ) {

		$speaker_display = get_post_meta( $session_id, 'wpcsp_session_speaker_display', true );
		if ( 'typed' === $speaker_display ) {
			return $speakers_typed;
		}

		$html         = '';
		$speakers_cpt = get_post_meta( $session_id, 'wpcsp_session_speakers', true );

		if ( $speakers_cpt ) {
			ob_start();
			?>
			<div class="wpcsp-single-session-speakers">
				<h2 class="wpcsp-single-session-speakers-title">Speakers</h2>
				<?php
				foreach ( $speakers_cpt as $post_id ) {
					$first_name         = get_post_meta( $post_id, 'wpcsp_first_name', true );
					$last_name          = get_post_meta( $post_id, 'wpcsp_last_name', true );
					$full_name          = $first_name . ' ' . $last_name;
					$title_organization = array();

					?>
					<div class="wpcsp-single-session-speakers-speaker">

						<?php
						if ( has_post_thumbnail( $post_id ) ) {
							echo get_the_post_thumbnail( $post_id, 'thumbnail', array( 'class' => 'wpcsp-single-session-speakers-speaker-image' ) );
						}
						?>

						<?php if ( $full_name ) { ?>
							<h3 class="wpcsp-single-session-speakers-speaker-name">
								<a href="<?php the_permalink( $post_id ); ?>">
									<?php echo wp_kses_post( $full_name ); ?>
								</a>
							</h3>
						<?php } ?>

						<?php if ( $title_organization ) { ?>
							<div class="wpcsp-single-session-speakers-speaker-title-organization">
								<?php echo wp_kses_post( implode( ', ', $title_organization ) ); ?>
							</div>
						<?php } ?>

					</div>
					<?php
				}
				?>
			</div>
			<?php
			$html .= ob_get_clean();
		}

		return $html;
	}

	/**
	 * Get the session content header.
	 *
	 * @param  int $session_id The session ID.
	 * @return string
	 */
	public function session_content_header( $session_id ) {
		$html         = '';
		$session_tags = get_the_terms( $session_id, 'wpcs_session_tag' );
		if ( $session_tags ) {
			ob_start();
			?>
			<ul class="wpcsp-session-tags">
				<?php foreach ( $session_tags as $session_tag ) { ?>
					<?php
					$term_url = get_term_link( $session_tag->term_id, 'wpcs_session_tag' );

					if ( is_wp_error( $term_url ) ) {
						$term_url = '';
					}
					?>
					<li class="wpcsp-session-tags-tag">
						<a href="<?php echo esc_url( $term_url ); ?>" class="wpcsp-session-tags-tag-link">
							<?php echo wp_kses_post( $session_tag->name ); ?>
						</a>
					</li>
				<?php } ?>
			</ul>
			<?php
			$html = ob_get_clean();
		}
		return $html;
	}

	/**
	 * Output single session tags.
	 *
	 * @return void
	 */
	public function single_session_tags() {
		$terms = get_the_terms( get_the_ID(), 'wpcs_session_tag' );
		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			$term_names = wp_list_pluck( $terms, 'name' );
			$terms      = implode( ', ', $term_names );
			if ( $terms ) {
				echo '<li class="wpsc-single-session-taxonomies-taxonomy wpsc-single-session-location"><i class="fas fa-tag" aria-hidden="true"></i>' . wp_kses_post( $terms ) . '</li>';
			}
		}
	}

	/**
	 * Output Session Sponsors.
	 *
	 * @param int $session_id The session ID.
	 * @return mixed
	 */
	public function session_sponsors( $session_id ) {

		$session_sponsors = get_post_meta( $session_id, 'wpcsp_session_sponsors', true );
		if ( ! $session_sponsors ) {
			return '';
		}

		$sponsors = array();
		foreach ( $session_sponsors as $sponsor_li ) {
			$sponsors[] .= get_the_title( $sponsor_li );
		}

		ob_start();

		if ( $sponsors ) {
			echo '<div class="wpcs-session-sponsor"><span class="wpcs-session-sponsor-label">Presented by: </span>' . wp_kses_post( implode( ', ', $sponsors ) ) . '</div>';
		}

		$html = ob_get_clean();
		return $html;
	}
}

/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'wpcsp_pro_activation' );
register_deactivation_hook( __FILE__, 'wpcsp_pro_deactivation' );
register_uninstall_hook( __FILE__, 'wpcsp_pro_uninstall' );

/**
 * Define file path and basename
 */
$ac_pro_plugin_directory = __FILE__;
$ac_pro_plugin_basename  = plugin_basename( __FILE__ );

/**
 * Filters and Actions
 */
add_action( 'wp_enqueue_scripts', 'wpcsp_pro_enqueue_styles' );
add_action( 'cmb2_admin_init', 'wpcsp_speaker_metabox' );
add_action( 'cmb2_admin_init', 'wpcsp_media_partner_metabox' );
add_action( 'cmb2_admin_init', 'wpcsp_donor_metabox' );
add_action( 'cmb2_admin_init', 'wpcsp_sponsor_metabox' );
add_action( 'cmb2_admin_init', 'wpcsp_sponsor_level_metabox' );

/**
 * Generate speaker metaboxes.
 *
 * @return void
 */
function wpcsp_speaker_metabox() {

	$cmb = new_cmb2_box(
		array(
			'id'           => 'wpcsp_speaker_metabox',
			'title'        => __( 'Speaker Information', 'wpa-conference' ),
			'object_types' => array( 'wpcsp_speaker' ), // Post type.
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left.
		)
	);

	// First name.
	$cmb->add_field(
		array(
			'name' => __( 'First Name', 'wpa-conference' ),
			'id'   => 'wpcsp_first_name',
			'type' => 'text',
		)
	);

	// Last name.
	$cmb->add_field(
		array(
			'name' => __( 'Last Name', 'wpa-conference' ),
			'id'   => 'wpcsp_last_name',
			'type' => 'text',
		)
	);

	// Title.
	$cmb->add_field(
		array(
			'name' => __( 'Title', 'wpa-conference' ),
			'id'   => 'wpcsp_title',
			'type' => 'text',
		)
	);

	// Organization.
	$cmb->add_field(
		array(
			'name' => __( 'Organization', 'wpa-conference' ),
			'id'   => 'wpcsp_organization',
			'type' => 'text',
		)
	);

	// Country.
	$cmb->add_field(
		array(
			'name' => __( 'Country', 'wpa-conference' ),
			'id'   => 'wpcsp_country',
			'type' => 'text',
		)
	);

	// Pronouns.
	$cmb->add_field(
		array(
			'name' => __( 'Pronouns', 'wpa-conference' ),
			'id'   => 'wpcsp_pronouns',
			'type' => 'text',
		)
	);

	// Author email.
	$cmb->add_field(
		array(
			'name' => __( 'Author email', 'wpa-conference' ),
			'id'   => 'wpcsp_user_email',
			'type' => 'text_email',
		)
	);

	// Mastodon.
	$cmb->add_field(
		array(
			'name' => __( 'Mastodon', 'wpa-conference' ),
			'id'   => 'wpcsp_mastodon_url',
			'type' => 'text_url',
		)
	);

	// Bluesky.
	$cmb->add_field(
		array(
			'name' => __( 'Bluesky', 'wpa-conference' ),
			'id'   => 'wpcsp_bluesky_url',
			'type' => 'text_url',
		)
	);

	// Threads.
	$cmb->add_field(
		array(
			'name' => __( 'Threads', 'wpa-conference' ),
			'id'   => 'wpcsp_threads_url',
			'type' => 'text_url',
		)
	);

	// Facebook URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Facebook URL', 'wpa-conference' ),
			'id'        => 'wpcsp_facebook_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Twitter URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Twitter URL', 'wpa-conference' ),
			'id'        => 'wpcsp_twitter_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Github URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Github URL', 'wpa-conference' ),
			'id'        => 'wpcsp_github_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// WordPress Profile URL.
	$cmb->add_field(
		array(
			'name'      => __( 'WordPress Profile URL', 'wpa-conference' ),
			'id'        => 'wpcsp_wordpress_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Instagram URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Instagram URL', 'wpa-conference' ),
			'id'        => 'wpcsp_instagram_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Linkedin URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Linkedin URL', 'wpa-conference' ),
			'id'        => 'wpcsp_linkedin_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// YouTube URL.
	$cmb->add_field(
		array(
			'name'      => __( 'YouTube URL', 'wpa-conference' ),
			'id'        => 'wpcsp_youtube_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Website URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Website URL', 'wpa-conference' ),
			'id'        => 'wpcsp_website_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);
}

/**
 * Generate donor metabox.
 *
 * @return void
 */
function wpcsp_donor_metabox() {

	$cmb = new_cmb2_box(
		array(
			'id'           => 'wpcsp_donor_metabox',
			'title'        => __( 'Donor Information', 'wpa-conference' ),
			'object_types' => array( 'wpcsp_donor' ), // Post type.
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left.
		)
	);

	// Donor Company.
	$cmb->add_field(
		array(
			'name' => __( 'Company', 'wpa-conference' ),
			'id'   => 'wpcsp_donor_company',
			'type' => 'text',
		)
	);

	// Donor City.
	$cmb->add_field(
		array(
			'name' => __( 'City', 'wpa-conference' ),
			'id'   => 'wpcsp_donor_city',
			'type' => 'text',
		)
	);

	// Donor State/Province/Region.
	$cmb->add_field(
		array(
			'name' => __( 'State / Province / Region', 'wpa-conference' ),
			'id'   => 'wpcsp_donor_state',
			'type' => 'text',
		)
	);

	// Donor Country.
	$cmb->add_field(
		array(
			'name' => __( 'Country', 'wpa-conference' ),
			'id'   => 'wpcsp_donor_country',
			'type' => 'text',
		)
	);

	// Donor Twitter/X.
	$cmb->add_field(
		array(
			'name' => __( 'Twitter/X Handle (Must include @ symbol)', 'wpa-conference' ),
			'id'   => 'wpcsp_donor_twitter',
			'type' => 'text',
		)
	);
}

/**
 * Generate media partner metaboxes.
 *
 * @return void
 */
function wpcsp_media_partner_metabox() {

	$cmb = new_cmb2_box(
		array(
			'id'           => 'wpcsp_media_partner_metabox',
			'title'        => __( 'Media Partner Information', 'wpa-conference' ),
			'object_types' => array( 'wpcsp_media_partner' ), // Post type.
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left.
		)
	);

	// Website URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Website URL', 'wpa-conference' ),
			'id'        => 'wpcsp_website_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Instagram URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Instagram URL', 'wpa-conference' ),
			'id'        => 'wpcsp_instagram_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Facebook URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Facebook URL', 'wpa-conference' ),
			'id'        => 'wpcsp_facebook_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Linkedin URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Linkedin URL', 'wpa-conference' ),
			'id'        => 'wpcsp_linkedin_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// YouTube URL.
	$cmb->add_field(
		array(
			'name'      => __( 'YouTube URL', 'wpa-conference' ),
			'id'        => 'wpcsp_youtube_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Bluesky URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Bluesky URL', 'wpa-conference' ),
			'id'        => 'wpcsp_bluesky_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Threads URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Threads URL', 'wpa-conference' ),
			'id'        => 'wpcsp_threads_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Twitter URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Twitter URL', 'wpa-conference' ),
			'id'        => 'wpcsp_twitter_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);
}

/**
 * Generate sponsor metaboxes.
 *
 * @return void
 */
function wpcsp_sponsor_metabox() {

	$cmb = new_cmb2_box(
		array(
			'id'           => 'wpcsp_sponsor_metabox',
			'title'        => __( 'Sponsor Information', 'wpa-conference' ),
			'object_types' => array( 'wpcsp_sponsor' ), // Post type.
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true, // Show field names on the left.
		)
	);

	// Website URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Website URL', 'wpa-conference' ),
			'id'        => 'wpcsp_website_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Instagram URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Instagram URL', 'wpa-conference' ),
			'id'        => 'wpcsp_instagram_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Facebook URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Facebook URL', 'wpa-conference' ),
			'id'        => 'wpcsp_facebook_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Linkedin URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Linkedin URL', 'wpa-conference' ),
			'id'        => 'wpcsp_linkedin_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// YouTube URL.
	$cmb->add_field(
		array(
			'name'      => __( 'YouTube URL', 'wpa-conference' ),
			'id'        => 'wpcsp_youtube_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Bluesky URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Bluesky URL', 'wpa-conference' ),
			'id'        => 'wpcsp_bluesky_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Threads URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Threads URL', 'wpa-conference' ),
			'id'        => 'wpcsp_threads_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Twitter URL.
	$cmb->add_field(
		array(
			'name'      => __( 'Twitter URL', 'wpa-conference' ),
			'id'        => 'wpcsp_twitter_url',
			'type'      => 'text_url',
			'protocols' => array( 'http', 'https' ), // Array of allowed protocols.
		)
	);

	// Sponsor Swag.
	$cmb->add_field(
		array(
			'name' => __( 'Digital Swag', 'wpa-conference' ),
			'desc' => __( 'Use this field to add swag for attendees.', 'wpa-conference' ),
			'id'   => 'wpcsp_sponsor_swag',
			'type' => 'wysiwyg',
		)
	);
}

/**
 * Generate the sponsor level metabox.
 *
 * @return void
 */
function wpcsp_sponsor_level_metabox() {

	$cmb = new_cmb2_box(
		array(
			'id'           => 'wpcsp_sponsor_level_metabox',
			'title'        => esc_html__( 'Category Metabox', 'wpa-conference' ), // Doesn't output for term boxes.
			'object_types' => array( 'term' ), // Tells CMB2 to use term_meta vs post_meta.
			'taxonomies'   => array( 'wpcsp_sponsor_level' ), // Tells CMB2 which taxonomies should have these fields.
		)
	);

	// Logo Height.
	$cmb->add_field(
		array(
			'name'       => __( 'Logo Height', 'wpa-conference' ),
			'desc'       => __( 'Pixels', 'wpa-conference' ),
			'id'         => 'wpcsp_logo_height',
			'type'       => 'text_small',
			'attributes' => array(
				'type'    => 'number',
				'pattern' => '\d*',
			),
		)
	);
}

// Load the plugin class.
$GLOBALS['wpcs_plugin'] = new WPCS_Conference_Schedule();

/**
 * Register supported caption languages and provide labels. English version of language is used in admin; native is used on front-end.
 *
 * @param string $lang Language code or `false` to return entire array.
 *
 * @return string|array
 */
function wpcs_get_languages( $lang = false ) {
	$labels = array(
		// $langcode => [English version, Native version].
		'bg' => array( 'Bulgarian', 'Български' ),
		'de' => array( 'German', 'Deutsch' ),
		'en' => array( 'English', 'English' ),
		'es' => array( 'Spanish', 'Español' ),
		'fr' => array( 'French', 'Français' ),
		'he' => array( 'Hebrew', 'עִברִית' ),
		'hu' => array( 'Hungarian', 'Magyar' ),
		'it' => array( 'Italian', 'Italiano' ),
		'ja' => array( 'Japanese', '日本語' ),
		'ms' => array( 'Malay', 'Melayu' ),
		'nl' => array( 'Dutch', 'Nederlands' ),
		'no' => array( 'Norwegian', 'Norsk' ),
		'pt' => array( 'Portuguese', 'Português' ),
	);
	if ( ! $lang ) {
		return $labels;
	}

	return ( isset( $labels[ $lang ] ) ) ? $labels[ $lang ] : array( $lang, $lang );
}

/**
 * Get video HTML.
 *
 * @return string
 */
function wpcs_get_video() {
	$captions  = wpcs_get_captions();
	$count     = 0;
	$subtitles = '';
	if ( ! empty( $captions ) ) {
		foreach ( $captions as $lang => $caption ) {
			$label = wpcs_get_languages( $lang )[1];
			if ( $caption ) {
				$subtitles .= '<track kind="captions" src="' . esc_url( $caption ) . '" srclang="' . esc_attr( $lang ) . '" label="' . $label . '">';
			}
		}
		$count = count( $captions );
	}
	if ( $count <= 1 ) {
		// translators: Link to translation interest form.
		$translate = sprintf( __( 'This session is only available in English! Can you <a href="%s">help translate it</a>?', 'wpa-conference' ), 'https://wpaccessibility.day/translate/' );
	} else {
		// translators: Number of translations available; Link to translation interest form.
		$translate = sprintf( __( 'This session is available in %1$d languages! Can you <a href="%2$s">help translate more</a>?', 'wpa-conference' ), $count, 'https://wpaccessibility.day/translate/' );
	}
	$sign_src = wpcs_get_asl();
	if ( $sign_src ) {
		$sign_src = ' data-youtube-sign-src="' . esc_attr( $sign_src ) . '"';
	}
	$holder = $sign_src ? '<div class="holder"><p><em>Space for positioning sign language player</em></p></div>' : '';

	return '
	<div class="wp-block-group alignwide wpad-video-player">
		<h2>Session Video</h2>
		<div class="video-wrapper">
			<video id="able-player-' . get_the_ID() . '" data-skin="2020" data-heading-level="0" data-able-player data-transcript-div="able-player-transcript-' . get_the_ID() . '" preload="auto" poster="' . wpcs_get_poster() . '" data-youtube-id="' . wpcs_get_youtube() . '"' . $sign_src . '>
				' . $subtitles . '
			</video>
			' . $holder . '
		</div>
		<div id="able-player-transcript-' . get_the_ID() . '"></div>
		<div class="please-translate">
			<p>
				' . $translate . '
			</p>
		</div>
	</div>';
}

/**
 * Append video to end of session post if has captions & video.
 *
 * @param string $content The content.
 *
 * @return string
 */
function wpcs_add_video( $content ) {
	if ( ! wpcs_get_captions() || ! wpcs_get_youtube() ) {
		return $content;
	} else {
		return wpcs_get_video() . $content;
	}
}
add_filter( 'the_content', 'wpcs_add_video' );

/**
 * Get video poster (the post thumbnail URL).
 *
 * @return string
 */
function wpcs_get_poster() {
	$poster = get_the_post_thumbnail_url();

	return $poster;
}

/**
 * Get captions URLs.
 *
 * @return bool|array
 */
function wpcs_get_captions() {
	$cache_break = ( current_user_can( 'manage_options' ) ) ? true : false;
	$post_id     = get_the_ID();
	$languages   = wpcs_get_languages( false );
	$captions    = array();
	$has_caption = false;
	$args        = array(
		'fields' => 'slugs',
	);
	$terms       = wp_get_object_terms( $post_id, 'wpcs_session_lang', $args );
	foreach ( $languages as $key => $language ) {
		$filename = get_post_field( 'post_name', $post_id ) . '-' . $key;
		$year     = gmdate( 'Y', strtotime( get_option( 'wpad_start_time' ) ) );
		$filepath = plugin_dir_path( __FILE__ ) . 'assets/captions/' . $year . '/' . $filename . '.vtt';
		$file_url = plugins_url( '/assets/captions/' . $year . '/' . $filename . '.vtt', __FILE__ );
		global $wp_filesystem;
		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();
		if ( $wp_filesystem->exists( $filepath ) ) {
			if ( ! has_term( $key, 'wpcs_session_lang' ) ) {
				$terms[] = $key;
				wp_set_object_terms( $post_id, $terms, 'wpcs_session_lang' );
			}
			$has_caption      = ( 'en' === $key ) ? true : $has_caption;
			$captions[ $key ] = ( $cache_break ) ? add_query_arg( 'version', wp_rand( 10000, 99999 ), $file_url ) : $file_url;
		}
	}
	// We only display videos if they have English captions, first. Translations are secondary.
	if ( ! $has_caption ) {
		return false;
	}

	return $captions;
}

/**
 * Get the ASL version of a video.
 *
 * @return string
 */
function wpcs_get_asl() {
	$asl = get_post_meta( get_the_ID(), '_wpcs_asl_id', true );

	return $asl;
}

/**
 * Get youtube ID from meta.
 *
 * @param string $type 'main' or 'asl'.
 *
 * @return string
 */
function wpcs_get_youtube( $type = 'main' ) {
	$post_id = get_the_ID();
	if ( 'main' === $type ) {
		$session_youtube = get_post_meta( $post_id, '_wpcs_youtube_id', true );
	} else {
		$session_youtube = get_post_meta( $post_id, '_wpcs_asl_id', true );
	}

	return $session_youtube;
}

/**
 * Execute the partners shortcode
 *
 * @return string
 */
function wpcsp_partners_shortcode() {
	ob_start();
	wpcsp_partners_list();
	return ob_get_clean();
}

/**
 * Function to retrieve and display the list of partners
 */
function wpcsp_partners_list() {
	$args = array(
		'post_type'      => 'wpcsp_media_partner',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'title',
		'order'          => 'ASC',
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		echo '<div class="wpcsp-partners"><ul>';
		while ( $query->have_posts() ) {
			$query->the_post();

			echo '<li class="' . esc_attr( sanitize_title( get_the_title() ) ) . '">';
			echo '<a href="' . esc_url( get_the_permalink() ) . '">';
			echo '<figure>';
			the_post_thumbnail( 'medium', array( 'alt' => '' ) );
			echo '<figcaption>';
			the_title();
			echo '</figcaption>';
			echo '</figure>';
			echo '</a>';
			echo '</li>';
		}
		echo '</ul></div>';
		wp_reset_postdata();
	}
}

/**
 * Register the donors shortcode
 *
 * @return string
 */
function wpcsp_donors_shortcode() {
	ob_start();
	wpcsp_donors_list();
	return ob_get_clean();
}

/**
 * Function to retrieve and display the list of donors
 */
function wpcsp_donors_list() {
	$args = array(
		'post_type'      => 'wpcsp_donor',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);

	$donors_query = new WP_Query( $args );

	if ( $donors_query->have_posts() ) {
		echo '<div class="wpcsp-donors"><ul>';
		while ( $donors_query->have_posts() ) {
			$donors_query->the_post();

			$donor_company = get_post_meta( get_the_ID(), 'wpcsp_donor_company', true );
			$donor_city    = get_post_meta( get_the_ID(), 'wpcsp_donor_city', true );
			$donor_state   = get_post_meta( get_the_ID(), 'wpcsp_donor_state', true );
			$donor_country = get_post_meta( get_the_ID(), 'wpcsp_donor_country', true );

			echo '<li>';
			echo '<span class="donor-name">' . get_the_title();
			if ( ! empty( $donor_company ) ) {
				echo ', ' . esc_html( $donor_company );
			}
			echo '</span>';

			if ( ! empty( $donor_city ) || ! empty( $donor_state ) || ! empty( $donor_country ) ) {
				echo '<span class="donor-location">';
				$location_parts = array_filter( array( $donor_city, $donor_state, $donor_country ) );
				echo implode( ', ', array_map( 'esc_html', $location_parts ) );
				echo '</span>';
			}
			echo '</li>';
		}
		echo '</ul></div>';
		wp_reset_postdata();
	}
}

/**
 * Add the shortcode documentation dashboard widget.
 *
 * @return void
 */
function wpcs_dashboard_widget() {
	wp_add_dashboard_widget( 'wpcs_dashboard_widget', __( 'WPAD Shortcodes', 'wpa-conference' ), 'wpcs_dashboard_widget_handler' );
}
add_action( 'wp_dashboard_setup', 'wpcs_dashboard_widget' );

/**
 * Output the WP Accessibility stats widget.
 *
 * @return void
 */
function wpcs_dashboard_widget_handler() {
	$shortcodes = array(
		'schedule'      => array(
			'function' => 'wpcs_schedule',
			'args'     => array(
				'start' => array(
					'type'        => 'integer',
					'default'     => '15',
					'description' => 'The UTC time in hours when the event starts.',
				),
			),
		),
		'donors'        => array(
			'function' => 'wpcsp_donors_shortcode',
			'args'     => array(),
		),
		'microsponsors' => array(
			'function' => 'wpcs_display_microsponsors',
			'args'     => array(),
		),
		'attendees'     => array(
			'function' => 'wpcs_shortcode_people',
			'args'     => array(),
		),
		'able'          => array(
			'function' => 'wpcs_get_video',
			'args'     => array(),
		),
		'wpad'          => array(
			'function' => 'wpcs_event_start',
			'args'     => array(
				'format'   => array(
					'type'        => 'string',
					'default'     => 'H:i',
					'description' => 'A datetime format in PHP DateTimeInterface syntax.',
				),
				'fallback' => array(
					'type'        => 'string',
					'default'     => 'Fall 2024',
					'description' => 'A string for when no event date is defined',
				),
				'dashicon' => array(
					'type'        => 'string',
					'default'     => '',
					'description' => 'A dashicon slug to prepend to the output',
				),
				'time'     => array(
					'type'        => 'string',
					'default'     => '',
					'description' => 'A date and time parsable by `strtotime()`',
				),
			),
		),
		'wpcs_sponsors' => array(
			'function' => '$this->shortcode_sponsors',
			'args'     => array(),
		),
		'wpcs_speakers' => array(
			'function' => '$this->shortcode_speakers',
			'args'     => array(),
		),
		'logo'          => array(
			'function' => 'wpad_site_logo',
			'args'     => array(),
		),
	);

	$output = '';
	foreach ( $shortcodes as $code => $attributes ) {
		$output .= '<li><code>[' . $code . ']</code>/<code>' . $attributes['function'] . '()</code><ul style="padding: 1rem">';
		if ( isset( $attributes['args'] ) ) {
			foreach ( $attributes['args'] as $arg => $info ) {
				$output .= '<li><code>' . $arg . '="' . $info['default'] . '"</code></li>';
				$output .= '<li><p><code>' . $info['type'] . '</code>: ' . $info['description'] . '</p></li>';
			}
		}
		$output .= '</ul></li>';
	}
	$output = '<ul>' . $output . '</ul>';

	echo $output;
}

/**
 * Send iCal event to browser
 *
 * @return string headers & text for iCal event.
 */
function wpad_send_vcal() {
	$session_id = isset( $_GET['vcal'] ) ? absint( $_GET['vcal'] ) : false;
	if ( ! $session_id || ! 'wpcs_session' === get_post_type( $session_id ) ) {
		return;
	}
	header( 'Content-Type: text/calendar' );
	header( 'Cache-control: private' );
	header( 'Pragma: private' );
	header( 'Expires: Thu, 11 Nov 1977 05:40:00 GMT' ); // That's my birthday. :).
	header( "Content-Disposition: inline; filename=wp-accessibility-day-$session_id.ics" );
	$year    = gmdate( 'Y', strtotime( get_option( 'wpad_start_time' ) ) );
	$speaker = wpcs_session_speakers( $session_id );
	$list    = implode( ', ', $speaker['list'] );
	$url     = get_the_permalink( $session_id );
	$title   = get_the_title( $session_id );
	$start   = gmdate( 'Ymd\THi00\Z', get_post_meta( $session_id, '_wpcs_session_time', true ) );
	$end     = gmdate( 'Ymd\THi00\Z', get_post_meta( $session_id, '_wpcs_session_time', true ) + 50 * 60 );
	$excerpt = get_the_excerpt( $session_id );

	$output = "BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
PRODID:-//WP Accessibility Day//Schedule//https://wpaccessibility.day//v1//EN';
BEGIN:VEVENT
UID:wpad-$year-$session_id
LOCATION:Zoom
SUMMARY:$list - $title
DTSTAMP;TZID=Etc/UTC:$start
ORGANIZER;CN=WP Accessibility Day:MAILTO:contact@wpaccessibility.day
DTSTART;TZID=Etc/UTC:$start
DTEND;TZID=Etc/UTC:$end
URL;VALUE=URI:$url
DESCRIPTION:$excerpt
END:VEVENT
END:VCALENDAR";

	print wp_kses_post( $output );
	die;
}
add_action( 'init', 'wpad_send_vcal', 200 );

/**
 * Generate Add to Google calendar.
 *
 * @param int $session_id Session post ID.
 *
 * @return string
 */
function wpad_google_cal( $session_id ) {
	$year    = gmdate( 'Y', strtotime( get_option( 'wpad_start_time' ) ) );
	$url     = get_the_permalink( $session_id );
	$title   = get_the_title( $session_id );
	$time    = get_post_meta( $session_id, '_wpcs_session_time', true );
	$time    = ( $time ) ? $time : strtotime( get_option( 'wpad_start_time' ) );
	$start   = gmdate( 'Ymd\THi00\Z', $time );
	$end     = gmdate( 'Ymd\THi00\Z', $time + 50 * 60 );
	$excerpt = get_the_excerpt( $session_id );
	$source  = 'https://www.google.com/calendar/render?action=TEMPLATE';
	$speaker = wpcs_session_speakers( $session_id );
	$list    = implode( ', ', $speaker['list'] );

	$args = array(
		'dates'   => "$start/$end",
		'sprop'   => 'website:' . $url,
		'text'    => urlencode( "$list: $title" ),
		'sprop'   => 'name:' . urlencode( get_bloginfo( 'name' ) . ' ' . $year ),
		'details' => urlencode( stripcslashes( trim( $excerpt ) ) ),
		'sf'      => 'true',
		'output'  => 'xml',
	);

	return add_query_arg( $args, $source );
}

/**
 * Generate Add to Outlook calendar.
 *
 * @param int $session_id Session post ID.
 *
 * @return string
 */
function wpad_outlook_cal( $session_id ) {
	$title   = get_the_title( $session_id );
	$time    = get_post_meta( $session_id, '_wpcs_session_time', true );
	$time    = ( $time ) ? $time : strtotime( get_option( 'wpad_start_time' ) );
	$start   = gmdate( 'Ymd\THi00\Z', $time );
	$end     = gmdate( 'Ymd\THi00\Z', $time + 50 * 60 );
	$excerpt = get_the_excerpt( $session_id );
	$source  = 'https://outlook.live.com/calendar/0/action/compose';
	$speaker = wpcs_session_speakers( $session_id );
	$list    = implode( ', ', $speaker['list'] );

	$args = array(
		'path'    => '/calendar/action/compose/',
		'rru'     => 'addevent',
		'allday'  => 'false',
		'startdt' => $start,
		'enddt'   => $end,
		'subject' => urlencode( "$list: $title" ),
		'body'    => urlencode( stripcslashes( trim( $excerpt ) ) ),
	);

	return add_query_arg( $args, $source );
}

/**
 * Generate Add to Office 365 calendar.
 *
 * @param int $session_id Session post ID.
 *
 * @return string
 */
function wpad_office_cal( $session_id ) {
	$url = wpad_outlook_cal( $session_id );
	$url = str_replace( 'outlook.live.com', 'outlook.office.com', $url );

	return $url;
}

/**
 * Set up Add to Calendar links.
 *
 * @param int $session_id Session post ID.
 *
 * @return string
 */
function wpad_add_calendar_links( $session_id ) {
	$google  = wpad_google_cal( $session_id );
	$outlook = wpad_outlook_cal( $session_id );
	$office  = wpad_office_cal( $session_id );
	$ical    = add_query_arg( 'vcal', $session_id, get_the_permalink( $session_id ) );

	$screen_reader_text = '';
	if ( is_page( 'schedule' ) ) {
		$screen_reader_text = ' <span class="screen-reader-text">(' . get_the_title( $session_id ) . ')</span>';
	}

	$output = '<div class="wpad-calendar-links">
		<button class="button has-popup" type="button" aria-expanded="false" aria-haspopup="true" aria-controls="wpad-add-links-' . absint( $session_id ) . '">Add to Calendar' . $screen_reader_text . '</button>
		<ul id="wpad-add-links-' . absint( $session_id ) . '">
			<li><a href="' . esc_url( $google ) . '"><span class="dashicons dashicons-google" aria-hidden="true"></span> Add to Google</a></li>
			<li><a href="' . esc_url( $outlook ) . '"><span class="fa-brands fa-microsoft" aria-hidden="true"></span> Add to Outlook</a></li>
			<li><a href="' . esc_url( $office ) . '"><span class="fa-brands fa-microsoft" aria-hidden="true"></span> Add to Office 365</a></li>
			<li><a href="' . esc_url( $ical ) . '"><span class="dashicons dashicons-calendar" aria-hidden="true"></span> Download iCal</a></li>
		</ul>
	</div>';

	return $output;
}
