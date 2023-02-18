<?php
/**
 * custom option and settings
 */
function wpcs_settings_init() {

	// register a settings section in the "wpcs" page
	add_settings_section(
		'wpcs_section_settings',
		__( 'General Settings', 'wpcs' ),
		'wpcs_section_settings_cb',
		'wpcs'
	);

	// register byline setting for "wpcs" page
	register_setting("wpcs", "wpcs_field_registration");
 
	// register byline field in the "wpcs_section_info" section, inside the "wpcs" page
	add_settings_field("wpcs_field_registration", "Registration URL", "wpcs_field_registration_cb", "wpcs", "wpcs_section_settings");

	// register schedule page URL setting for "wpcs" page
	register_setting("wpcs", "wpcs_field_schedule_page_url");
 
	// register schedule page URL field in the "wpcs_section_info" section, inside the "wpcs" page
	add_settings_field("wpcs_field_schedule_page_url", "Schedule Page URL", "wpcs_field_schedule_page_url_cb", "wpcs", "wpcs_section_settings");
}
 
/**
 * register our wpcs_settings_init to the admin_init action hook
 */
add_action( 'admin_init', 'wpcs_settings_init' );
 
/**
 * custom option and settings:
 * callback functions
 */


// section callbacks can accept an $args parameter, which is an array.
// $args have the following keys defined: title, id, callback.
// the values are defined at the add_settings_section() function.
function wpcs_section_info_cb( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Show your thanks to WP Conference Schedule for creating an amazing free plugin by giving them recognition with a small text-only link at the bottom of your conference schedule.', 'wpcs' ); ?></p>
	<?php
}

function wpcs_section_settings_cb( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( '', 'wpcs' ); ?></p>
	<?php
}
 
function wpcs_field_registration_cb(){
	?>
	<input type="url" name="wpcs_field_registration" value="<?php echo esc_url( get_option( 'wpcs_field_registration' ) ); ?>" style="width:100%;max-width: 450px;" aria-describedby="wpcs_field_registration_description" />
	<p class="description" id="wpcs_field_registration_description">The URL of your registration form.</p>
	<?php
}

function wpcs_field_schedule_page_url_cb(){
	?>
	<input type="text" name="wpcs_field_schedule_page_url" value="<?php echo esc_url( get_option('wpcs_field_schedule_page_url') ); ?>" style="width:100%;max-width: 450px;" aria-describedby="wpcs_field_schedule_page_url_description">
	<p class="description" id="wpcs_field_schedule_page_url_description">The URL of the page that your conference schedule is embedded on.</p>
	<?php
}
 
/**
 * top level menu
 */
function wpcs_options_page() {
	// add top level menu page
	add_options_page(
		'WP Conference Schedule',
		'WP Conference Schedule',
		'manage_options',
		'wp-conference-schedule',
		'wpcs_options_page_html',
		'dashicons-schedule'
	);
}
 
/**
 * register our wpcs_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'wpcs_options_page' );
 
/**
 * callback functions
 */
function wpcs_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// wordpress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'wpcs_messages', 'wpcs_message', __( 'Settings Saved', 'wpcs' ), 'updated' );
	}

	// show error/update messages
	//settings_errors( 'wpcs_messages' );
	?>
	<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "wpcs"
			settings_fields( 'wpcs' );
			// output setting sections and their fields
			// (sections are registered for "wpcs", each field is registered to a specific section)
			do_settings_sections( 'wpcs' );
			// output save settings button
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

	// register schedule page URL setting for "wpcs" page
	register_setting("wpcs", "wpcsp_field_speakers_page_url", "wpcsp_sanitize_field_speakers_page_url");
 
	// register schedule page URL field in the "wpcs_section_info" section, inside the "wpcs" page
	add_settings_field("wpcsp_field_speakers_page_url", "Speakers Page URL", "wpcsp_field_speakers_page_url_cb", "wpcs", "wpcs_section_settings");

	// register schedule page URL setting for "wpcs" page
	register_setting("wpcs", "wpcsp_field_sponsor_page_url", "wpcsp_sanitize_field_sponsor_page_url");
 
	// register schedule page URL field in the "wpcs_section_info" section, inside the "wpcs" page
	add_settings_field("wpcsp_field_sponsor_page_url", "Sponsor URL Redirect", "wpcsp_field_sponsor_page_url_cb", "wpcs", "wpcs_section_settings");

}
add_action( 'admin_init', 'wpcsp_settings_init', 11 );

/**
 * Speakers page url callback
 *
 * @return void
 */
function wpcsp_field_speakers_page_url_cb(){
	?>
	<input type="text" name="wpcsp_field_speakers_page_url" value="<?php echo get_option('wpcsp_field_speakers_page_url'); ?>" style="width: 450px;">
	<p class="description">The URL of the page that your speakers are embedded on.</p>
	<?php
}

/**
 * Sanitize the speakers page url value before being saved to database
 *
 * @param string $speakers_page_url
 * @return string
 */
function wpcsp_sanitize_field_speakers_page_url( $speakers_page_url ) {
	return sanitize_text_field($speakers_page_url);
}

/**
 * Sponsor page url callback
 *
 * @return void
 */
function wpcsp_field_sponsor_page_url_cb(){
	$sponsor_url = get_option('wpcsp_field_sponsor_page_url');
	?>
	<select name="wpcsp_field_sponsor_page_url" id="sponsors_url">
		<option value="sponsor_page" <?php if($sponsor_url == "sponsor_page"){ echo "selected";} ?>>Redirect to Sponsor Page</option>
		<option value="sponsor_site" <?php if($sponsor_url == "sponsor_site"){ echo "selected";} ?>>Redirect to Sponsor Site </option>
	</select>
	<p class="description">The location to redirect sponsor links to on the session single page.</p>
	<?php
}

/**
 * Sanitize the sponsor page url value before being saved to database
 *
 * @param string $redirect
 * @return string
 */
function wpcsp_sanitize_field_sponsor_page_url( $redirect ) {
	if($redirect == 'sponsor_page' || $redirect == 'sponsor_site'){
		return $redirect;
	}else{
		return;
	}
}