/*
================================================================================
Comment Upvoter Javascript
================================================================================
AUTHOR: Christian Wach <needle@haystack.co.uk>
--------------------------------------------------------------------------------
NOTES

--------------------------------------------------------------------------------
*/

/**
 * Set up clicks on our "Like" elements.
 *
 * @since 0.1
 */
function comment_upvoter_click() {

	// define vars
	var me, counter, upvotes;

	// unbind first - allows this function to be called multiple times
	jQuery( '.comment_upvoter' ).unbind( 'click' );

	// rebind
	jQuery( '.comment_upvoter' ).click( function( event ) {

		// store this
		me = jQuery(this);

		// send ajax request
		jQuery.post (

			// url
			comment_upvoter.ajax_url,

			// data
			{
				'action' : 'comment_upvoter',
				'do': me.attr( 'data-do' ),
				'comment_id': me.attr( 'data-comment_id' )
			},

			// on response
			function ( response ) {

				// update counter
				counter = me.children( 'span.upvotes' );
				upvotes = parseInt( counter.text() );
				counter.text( (upvotes + 1).toString() );

			}

		);

		// prevent link from being followed
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

	// init click handler
	comment_upvoter_click();

});


