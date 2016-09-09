<?php /*
--------------------------------------------------------------------------------
Plugin Name: Comment Upvoter
Description: Lets logged-in readers "like" a comment. Designed for use with CommentPress Core.
Version: 0.1
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
--------------------------------------------------------------------------------
Hat tip: http://pippinsplugins.com/featured-comments
--------------------------------------------------------------------------------
*/



// define version (bumping this refreshes CSS and JS)
define( 'COMMENT_UPVOTER_VERSION', '0.1' );



/**
 * Comment Upvoter Class.
 *
 * A class that encapsulates plugin functionality.
 *
 * @since 0.1
 */
class Comment_Upvoter {

	/**
	 * The class instance.
	 *
	 * @since 0.1
	 * @access private
	 * @var object $instance The class instance
	 */
	private static $instance;

	/**
	 * The name of the custom action.
	 *
	 * @since 0.1
	 * @access private
	 * @var str $action The action name
	 */
	private static $action;

	/**
	 * The name of the identifying label.
	 *
	 * @since 0.1
	 * @access private
	 * @var str $action The action name
	 */
	private static $label;



	/**
	 * Returns an instance of this class.
	 *
	 * @since 0.1
	 *
	 * @staticvar object $instance
	 * @return object Comment_Upvoter instance
	 */
	public static function instance() {

		// do we have it?
		if ( ! isset( self::$instance ) ) {

			// instantiate
			self::$instance = new Comment_Upvoter;

			// init
			self::$instance->init();

			// broadcast to other plugins
			do_action( 'comment_upvoter_loaded' );

		}

		// --<
		return self::$instance;

	}



	/**
	 * Initialise object.
	 *
	 * @since 0.1
	 */
	private function init() {

		// define action
		self::$action = 'upvote';

		// define label
		self::$label = __( 'Like', 'comment-upvoter' );

		// use translation
		add_action( 'plugins_loaded', array( $this, 'enable_translation' ) );

		// inject Javascript
		add_action( 'wp_enqueue_scripts', array( $this, 'print_scripts' ) );

		// inject CSS
		add_action( 'wp_enqueue_scripts', array( $this, 'print_styles' ) );

		// register with AJAX handler
		add_action( 'wp_ajax_comment_upvoter', array( $this, 'ajax' ) );
		add_action( 'wp_ajax_nopriv_comment_upvoter', array( $this, 'ajax' ) );

		// filter comment text
		add_filter( 'cp_comment_action_links', array( $this, 'comment_action' ), 10, 2 );

		// create special page link
		//add_action( 'cp_before_blog_page', array( $this, 'comment_page_link' ), 11 );

	}



	/**
	 * Load translation files.
	 *
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// load translations
		load_plugin_textdomain(

			// unique name
			'comment-upvoter',

			// deprecated argument
			false,

			// relative path to directory containing translation files
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'

		);

	}



	/**
	 * Enqueue plugin Javacript.
	 *
	 * @since 0.1
	 */
	public function print_scripts() {

		// enqueue javascript
		wp_enqueue_script(
			'comment_upvoter',
			plugin_dir_url( __FILE__ ) . 'comment-upvoter.js',
			array( 'jquery' ),
			COMMENT_UPVOTER_VERSION
		);

		// add localisation
		wp_localize_script(
			'comment_upvoter',
			'comment_upvoter',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
		);

	}



	/**
	 * Enqueue plugin stylesheet.
	 *
	 * @since 0.1
	 */
	public function print_styles() {

		// add basic stylesheet
		wp_enqueue_style(
			'comment_upvoter_css',
			plugin_dir_url( __FILE__ ) . 'comment-upvoter.css',
			false,
			COMMENT_UPVOTER_VERSION, // version
			'all' // media
		);

	}



	/**
	 * Respond to AJAX requests.
	 *
	 * @since 0.1
	 */
	public function ajax() {

		// bail if no param
		if ( ! isset( $_POST['do'] ) ) die();

		// get action
		$action = $_POST['do'];

		// bail if not ours
		if ( $action != self::$action ) die();

		// get comment ID
		$comment_id = absint( $_POST['comment_id'] );

		// bail if not a valid comment
		if ( ! $comment = get_comment( $comment_id ) ) die();

		// get existing meta
		$count = self::get_comment_upvotes( $comment_id );

		// increment
		$count++;

		// thumbs up!
		update_comment_meta( $comment_id, 'upvotes', $count );

		// that's all folks
		die();

	}



	/**
	 * Show our button.
	 *
	 * @since 0.1
	 *
	 * @param string $existing The existing comment action string
	 * @param object $comment The WordPress comment object
	 * @return string $existing The modified comment action string
	 */
	public function comment_action( $existing, $comment ) {

		// get ID
		$comment_id = $comment->comment_ID;

		// get existing meta
		$count = self::get_comment_upvotes( $comment_id );

		// init return
		$html = '<span class="alignright comment-upvote">';

		// add our link
		$html .= '<a href="' . $_SERVER['REQUEST_URI'] . '" ' .
					'class="comment_upvoter ' . self::$action . '" ' .
					'data-do="' . self::$action . '" ' .
					'data-comment_id="' . $comment_id .  '" ' .
					'title="' . self::$label . '">' .
					self::$label . ' (<span class="upvotes">' . $count . '</span>)' .
				 '</a>';

		// close
		$html .= '</span>';

		// prepend to existing
		$existing = $html . $existing;

		// --<
		return $existing;

    }



	/**
	 * Get current number of upvotes.
	 *
	 * @since 0.1
	 *
	 * @param int $comment_id The numerical ID of the comment
	 * @return int $count The number of upvotes
	 */
	public function get_comment_upvotes( $comment_id ) {

		// get existing
		$count = get_comment_meta( $comment_id, 'upvotes', true );

		// did we get one?
		if ( $count === '' ) $count = 0;

		// cast as integer
		$count = absint( $count );

		// --<
		return $count;

	}


}



/**
 * Instantiate plugin object.
 *
 * @since 0.1
 *
 * @return object the plugin instance
 */
function wp_comment_upvoter_load() {
	return Comment_Upvoter::instance();
}

// init Comment Upvoter
wp_comment_upvoter_load();



