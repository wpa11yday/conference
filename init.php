<?php
/**
 * @link              https://wpconferenceschedule.com
 * @since             1.0.0
 * @package           wp_conference_schedule
 *
 * @wordpress-plugin
 * Plugin Name:       WP Accessibility Day - Conference Schedule
 * Plugin URI:        https://wpconferenceschedule.com
 * Description:       Forked from WP Conference Schedule by Road Warrior Creative.
 * Version:           1.0.4
 * Author:            WP Accessibility Day
 * Author URI:        https://wpaccessibility.day
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-conference-schedule
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include( dirname( __FILE__ ) . '/src/wp-conference-schedule.php' );

/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'wpcsp_pro_activation' );
register_deactivation_hook( __FILE__, 'wpcsp_pro_deactivation' );
register_uninstall_hook( __FILE__, 'wpcsp_pro_uninstall' );
