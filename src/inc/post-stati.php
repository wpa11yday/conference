<?php
/**
 * Register post stati.
 *
 * @package wpcsp
 */

add_action( 'init', 'wpcs_register_post_stati' );
/**
 * Registers the custom post stati, runs during init.
 *
 * @return void
 */
function wpcs_register_post_stati() {
	register_post_status(
		'approved',
		array(
			'label'                     => _x( 'Approved', 'post status', 'wpa-conference' ),
			'public'                    => true,
			'exclude_from_search'       => true,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			// Translators: the placeholder is for the number of approved posts.
			'label_count'               => _n_noop( 'Approved <span class="count">(%s)</span>', 'Approved <span class="count">(%s)</span>', 'wpa-conference' ),
		)
	);
}

add_action( 'post_submitbox_misc_actions', 'wpcs_add_post_stati_to_dropdown' );
/**
 * Add the post stati to the dropdown.
 *
 * @return void
 */
function wpcs_add_post_stati_to_dropdown() {
	global $post;

	$label       = __( 'Approved', 'wpa-conference' );
	$val         = 'approved';
	$is_approved = $val === $post->post_status;

	?>
<script>
document.addEventListener( 'DOMContentLoaded', () => {
	const postStatusDisplay  = document.getElementById('post-status-display');
	const postStatusSelect   = document.querySelector('select[name="post_status"]');
	const postStatusQE       = document.querySelector('select[name="_status"]');
	const postStatusApproved = document.createElement('option');
	const postStatusLabel    = <?php echo wp_json_encode( $label ); ?>;
	const postStatusVal      = <?php echo wp_json_encode( $val ); ?>;

	postStatusApproved.setAttribute('value', postStatusVal);
	postStatusApproved.innerText =  postStatusLabel;

	if ( postStatusSelect ) {
		postStatusSelect.appendChild(postStatusApproved);
	}

	if ( postStatusQE ) {
		postStatusQE.appendChild(postStatusApproved)
	}

	<?php if ( $is_approved ) : ?>
		if ( postStatusDisplay ) {
			postStatusDisplay.innerText = postStatusLabel;
		}

		if ( postStatusSelect ) {
			postStatusSelect.value = postStatusVal;
		}
	<?php endif; ?>
} );
</script>
	<?php
}

add_action( 'admin_footer-edit.php', 'wpcs_add_post_stati_to_quick_edit' );
/**
 * Add the post stati to the dropdown.
 *
 * @return void
 */
function wpcs_add_post_stati_to_quick_edit() {
	$label = __( 'Approved', 'wpa-conference' );
	$val   = 'approved';
	?>
<script>
document.addEventListener( 'DOMContentLoaded', () => {
	const postStatusQE       = document.querySelector('select[name="_status"]');
	const postStatusApproved = document.createElement('option');
	const postStatusLabel    = <?php echo wp_json_encode( $label ); ?>;
	const postStatusVal      = <?php echo wp_json_encode( $val ); ?>;

	postStatusApproved.setAttribute('value', postStatusVal);
	postStatusApproved.innerText =  postStatusLabel;

	if ( postStatusQE ) {
		postStatusQE.appendChild(postStatusApproved)
	}
} );
</script>
	<?php
}

add_action( 'display_post_states', 'wpcs_display_post_states' );
/**
 * Add the post stati to the inline edit.
 *
 * @param array $states The post states.
 * @return array
 */
function wpcs_display_post_states( $states ) {
	global $post;

	$label = __( 'Approved', 'wpa-conference' );
	$val   = 'approved';

	if ( get_query_var( 'post_status' ) === $val || $val !== $post->post_status ) {
		return $states;
	}

	return array( $label );
}
