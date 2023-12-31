<?php
/**
 * WP Accessibility Day - Conference Schedule
 *
 * @link              https://wpconferenceschedule.com
 * @since             1.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       WP Accessibility Day - Conference Schedule
 * Plugin URI:        https://wpconferenceschedule.com
 * Description:       Forked from WP Conference Schedule by Road Warrior Creative.
 * Version:           2.0.0
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
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require 'src/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$wpcsp_update_checker = PucFactory::buildUpdateChecker(
	'https://github.com/wpa11yday/conference/',
	__FILE__,
	'wpad-conference-schedule'
);

// Set the branch that contains the stable release.
$wpcsp_update_checker->setBranch( 'master' );

include( dirname( __FILE__ ) . '/src/wp-conference-schedule.php' );

/**
 * Plugin Activation & Deactivation
 */
register_activation_hook( __FILE__, 'wpcsp_pro_activation' );
register_deactivation_hook( __FILE__, 'wpcsp_pro_deactivation' );
register_uninstall_hook( __FILE__, 'wpcsp_pro_uninstall' );
