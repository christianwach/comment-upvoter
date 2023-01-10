/**
 * Comment Upvoter Javascript.
 *
 * @package Comment_Upvoter
 */

/**
 * Set up clicks on our "Like" elements.
 *
 * @since 0.1
 */
function comment_upvoter_click() {

	// Define vars.
	var me, counter, upvotes;

	// Unbind first - allows this function to be called multiple times.
	jQuery( '.comment_upvoter' ).unbind( 'click' );

	// Rebind.
	jQuery( '.comment_upvoter' ).click( function( event ) {

		// Store this.
		me = jQuery(this);

		// Send ajax request.
		jQuery.post(

			// Target URL.
			comment_upvoter.ajax_url,

			// Add data.
			{
				action: 'comment_upvoter',
				is_upvote: me.attr( 'data-do' ),
				comment_id: me.attr( 'data-comment_id' ),
				_ajax_nonce: comment_upvoter.ajax_nonce
			},

			/**
			 * AJAX callback.
			 *
			 * @since 0.1
			 *
			 * @param {String} response The response from the server.
			 */
			function( response ) {

				// Bail on failure.
				if ( ! response.success ) {
					return;
				}

				// Update counter.
				counter = me.children( 'span.upvotes' );
				upvotes = parseInt( response.count );
				counter.text( upvotes.toString() );

			}

		);

		// Prevent link from being followed.
		event.preventDefault();
		return false;

	});

}

/**
 * Initialise on document ready.
 *
 * @since 0.1
 */
jQuery(document).ready( function($) {

	// Init click handler.
	comment_upvoter_click();

});
