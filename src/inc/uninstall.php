<?php
/**
 * Uninstall functions.
 *
 * @package wpcsp
 */

/**
 * Uninstall callback.
 *
 * @return void
 */
function wpcsp_pro_uninstall() {
	delete_transient( 'wpcsp_license_valid' );
}
