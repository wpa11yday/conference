(function($) {

	// we create a copy of the WP inline edit post function
	var $wp_inline_edit = inlineEditPost.edit;

	// and then we overwrite the function with our own code
	inlineEditPost.edit = function( id ) {

		// "call" the original WP edit function
		// we don't want to leave WordPress hanging
		$wp_inline_edit.apply( this, arguments );

		// now we take care of our business

		// get the post ID
		var $post_id = 0;
		if ( typeof( id ) == 'object' ) {
			$post_id = parseInt( this.getId( id ) );
		}

		if ( $post_id > 0 ) {
			// define the edit row
			var $edit_row = $( '#edit-' + $post_id );
			var $post_row = $( '#post-' + $post_id );

			// get the data
			var $youtube_id = $( '.column-conference_session_video .id', $post_row ).text();
			var $asl_id     = $( '.column-conference_session_asl .id', $post_row ).text();

			// populate the data
			$( ':input[name="wpad_youtube_id"]', $edit_row ).val( $youtube_id );
			$( ':input[name="wpad_asl_id"]', $edit_row ).val( $asl_id );
		}
	};

})(jQuery);