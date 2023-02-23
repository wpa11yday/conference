<?php
/**
 * Deactivate functions.
 *
 * @package wpcsp
 */

/**
 * Deactivation callback.
 *
 * @return void
 */
function wpcsp_pro_deactivation() {
	flush_rewrite_rules(); // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.flush_rewrite_rules_flush_rewrite_rules
}
