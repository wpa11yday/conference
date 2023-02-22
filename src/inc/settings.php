<?php
/**
 * Settings functions.
 *
 * @package wpcsp
 */

/**
 * Custom option and settings
 */
function wpcs_settings_init() {
	// register a settings section in the "wpcs" page.
	add_settings_section(
		'wpcs_section_settings',
		__( 'General Settings', 'wpa-conference' ),
		'wpcs_section_settings_cb',
		'wpa-conference'
	);

	// register byline setting for "wpcs" page.
	register_setting( 'wpa-conference', 'wpcs_field_registration' );

	// register byline field in the "wpcs_section_info" section, inside the "wpcs" page.
	add_settings_field( 'wpcs_field_registration', 'Registration URL', 'wpcs_field_registration_cb', 'wpa-conference', 'wpcs_section_settings' );

	// register schedule page URL setting for "wpcs" page.
	register_setting( 'wpa-conference', 'wpcs_field_schedule_page_url' );

	// register schedule page URL field in the "wpcs_section_info" section, inside the "wpcs" page.
	add_settings_field( 'wpcs_field_schedule_page_url', 'Schedule Page URL', 'wpcs_field_schedule_page_url_cb', 'wpa-conference', 'wpcs_section_settings' );
}

/**
 * Register our wpcs_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'wpcs_settings_init' );

/**
 * Custom option and settings:
 * callback functions
 */


/**
 * Section callbacks can accept an $args parameter, which is an array.
 *
 * The values are defined at the add_settings_section() function.
 *
 * @param  array $args The args have the following keys defined: title, id, callback.
 * @return void
 */
function wpcs_section_info_cb( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ?? '' ); ?>"><?php esc_html_e( 'Show your thanks to WP Conference Schedule for creating an amazing free plugin by giving them recognition with a small text-only link at the bottom of your conference schedule.', 'wpa-conference' ); ?></p>
	<?php
}

/**
 * Section settings callback.
 *
 * @param  array $args The args.
 * @return void
 */
function wpcs_section_settings_cb( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ?? '' ); ?>">
		<?php echo esc_html( $args['title'] ?? '' ); ?>
	</p>
	<?php
}

/**
 * Field registration callback.
 *
 * @return void
 */
function wpcs_field_registration_cb() {
	?>
	<input type="url" name="wpcs_field_registration" value="<?php echo esc_url( get_option( 'wpcs_field_registration' ) ); ?>" style="width:100%;max-width: 450px;" aria-describedby="wpcs_field_registration_description" />
	<p class="description" id="wpcs_field_registration_description">The URL of your registration form.</p>
	<?php
}

/**
 * Field Schedule page URL callback.
 *
 * @return void
 */
function wpcs_field_schedule_page_url_cb() {
	?>
	<input type="text" name="wpcs_field_schedule_page_url" value="<?php echo esc_url( get_option( 'wpcs_field_schedule_page_url' ) ); ?>" style="width:100%;max-width: 450px;" aria-describedby="wpcs_field_schedule_page_url_description">
	<p class="description" id="wpcs_field_schedule_page_url_description">The URL of the page that your conference schedule is embedded on.</p>
	<?php
}

/**
 * Top level menu.
 */
function wpcs_options_page() {
	// add top level menu page.
	add_menu_page(
		'WP Conference Schedule',
		'WP Conference Schedule',
		'manage_options',
		'wp-conference-schedule',
		'wpcs_options_page_html',
		'dashicons-schedule',
		10
	);
}

/**
 * Register our wpcs_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'wpcs_options_page' );

/**
 * The options page HTML callback.
 */
function wpcs_options_page_html() {
	// check user capabilities.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages.

	// check if the user have submitted the settings.
	// WordPress will add the "settings-updated" $_GET parameter to the url.
	if ( isset( $_GET['settings-updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		// add settings saved message with the class of "updated".
		add_settings_error( 'wpcs_messages', 'wpcs_message', __( 'Settings Saved', 'wpa-conference' ), 'updated' );
	}

	?>
	<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "wpcs".
			settings_fields( 'wpa-conference' );
			// output setting sections and their fields.
			// (sections are registered for "wpcs", each field is registered to a specific section).
			do_settings_sections( 'wpa-conference' );
			// output save settings button.
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}

/**
 * Custom options and settings
 *
 * @return void
 */
function wpcsp_settings_init() {

	// register schedule page URL setting for "wpcs" page.
	register_setting( 'wpa-conference', 'wpcsp_field_speakers_page_url', 'wpcsp_sanitize_field_speakers_page_url' );

	// register schedule page URL field in the "wpcs_section_info" section, inside the "wpcs" page.
	add_settings_field( 'wpcsp_field_speakers_page_url', 'Speakers Page URL', 'wpcsp_field_speakers_page_url_cb', 'wpa-conference', 'wpcs_section_settings' );

	// register schedule page URL setting for "wpcs" page.
	register_setting( 'wpa-conference', 'wpcsp_field_sponsor_page_url', 'wpcsp_sanitize_field_sponsor_page_url' );

	// register schedule page URL field in the "wpcs_section_info" section, inside the "wpcs" page.
	add_settings_field( 'wpcsp_field_sponsor_page_url', 'Sponsor URL Redirect', 'wpcsp_field_sponsor_page_url_cb', 'wpa-conference', 'wpcs_section_settings' );

}
add_action( 'admin_init', 'wpcsp_settings_init', 11 );

/**
 * Speakers page url callback
 *
 * @return void
 */
function wpcsp_field_speakers_page_url_cb() {
	?>
	<input type="text" name="wpcsp_field_speakers_page_url" value="<?php echo esc_attr( get_option( 'wpcsp_field_speakers_page_url' ) ); ?>" style="width: 450px;">
	<p class="description">The URL of the page that your speakers are embedded on.</p>
	<?php
}

/**
 * Sanitize the speakers page url value before being saved to database
 *
 * @param string $speakers_page_url The page URL.
 * @return string
 */
function wpcsp_sanitize_field_speakers_page_url( $speakers_page_url ) {
	return sanitize_text_field( $speakers_page_url );
}

/**
 * Sponsor page url callback
 *
 * @return void
 */
function wpcsp_field_sponsor_page_url_cb() {
	$sponsor_url = get_option( 'wpcsp_field_sponsor_page_url' );
	?>
	<select name="wpcsp_field_sponsor_page_url" id="sponsors_url">
		<option value="sponsor_page" 
		<?php
		if ( 'sponsor_page' === $sponsor_url ) {
			echo 'selected';}
		?>
		>Redirect to Sponsor Page</option>
		<option value="sponsor_site" 
		<?php
		if ( 'sponsor_site' === $sponsor_url ) {
			echo 'selected';}
		?>
		>Redirect to Sponsor Site </option>
	</select>
	<p class="description">The location to redirect sponsor links to on the session single page.</p>
	<?php
}

/**
 * Sanitize the sponsor page url value before being saved to database
 *
 * @param string $redirect The redirect path.
 * @return string
 */
function wpcsp_sanitize_field_sponsor_page_url( $redirect ) {
	if ( in_array( $redirect, array( 'sponsor_page', 'sponsor_site' ), true ) ) {
		return $redirect;
	} else {
		return '';
	}
}
