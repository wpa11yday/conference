<?php
/**
 * Activation functions.
 *
 * @package wpcsp
 */

/**
 * Activation callback.
 *
 * @return void
 */
function wpcsp_pro_activation() {
	flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
}
