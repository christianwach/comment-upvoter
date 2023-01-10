<?php
/**
 * Plugin Name: Comment Upvoter
 * Plugin URI: https://github.com/christianwach/comment-upvoter
 * Description: Lets logged-in readers "like" a comment. Designed for use with CommentPress Core.
 * Version: 0.2
 * Author: Christian Wach
 * Author URI: https://haystack.co.uk
 * Text Domain: comment-upvoter
 * Domain Path: /languages
 * Kudos to: http://pippinsplugins.com/featured-comments
 *
 * @package Comment_Upvoter
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Define version. Bumping this refreshes CSS and JS.
define( 'COMMENT_UPVOTER_VERSION', '0.2' );



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
	 * @var object $instance The class instance.
	 */
	private static $instance;

	/**
	 * The name of the custom action.
	 *
	 * @since 0.1
	 * @access private
	 * @var str $action The action name.
	 */
	private static $action;

	/**
	 * The name of the identifying label.
	 *
	 * @since 0.1
	 * @access private
	 * @var str $label The name of the identifying label.
	 */
	private static $label;

	/**
	 * The AJAX nonce action.
	 *
	 * @since 0.2
	 * @access private
	 * @var str $nonce_action The AJAX nonce action.
	 */
	private static $nonce_action = 'upvoter_comment_nonce';

	/**
	 * Returns an instance of this class.
	 *
	 * @since 0.1
	 *
	 * @return Comment_Upvoter $instance The plugin instance.
	 */
	public static function instance() {

		// Do we have it?
		if ( ! isset( self::$instance ) ) {

			// Bootstrap.
			self::$instance = new Comment_Upvoter();
			self::$instance->initialise();

			/**
			 * Fires when this plugin has loaded.
			 *
			 * @since 0.1
			 */
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
	private function initialise() {

		// Define action.
		self::$action = 'upvote';

		// Define label.
		self::$label = __( 'Like', 'comment-upvoter' );

		// Use translation.
		add_action( 'plugins_loaded', [ $this, 'enable_translation' ] );

		// Inject Javascript.
		add_action( 'wp_enqueue_scripts', [ $this, 'print_scripts' ] );

		// Inject CSS.
		add_action( 'wp_enqueue_scripts', [ $this, 'print_styles' ] );

		// Register with AJAX handler.
		add_action( 'wp_ajax_comment_upvoter', [ $this, 'ajax' ] );
		add_action( 'wp_ajax_nopriv_comment_upvoter', [ $this, 'ajax' ] );

		// Filter comment text.
		add_filter( 'cp_comment_action_links', [ $this, 'comment_action' ], 10, 2 );

		/*
		// Create special page link.
		add_action( 'cp_before_blog_page', [ $this, 'comment_page_link' ), 11 ];
		*/

	}

	/**
	 * Load translation files.
	 *
	 * A good reference on how to implement translation in WordPress:
	 *
	 * @see http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 *
	 * @since 0.1
	 */
	public function enable_translation() {

		// Load translations.
		load_plugin_textdomain(
			'comment-upvoter',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages/'
		);

	}

	/**
	 * Enqueue plugin Javacript.
	 *
	 * @since 0.1
	 */
	public function print_scripts() {

		// Enqueue javascript.
		wp_enqueue_script(
			'comment_upvoter',
			plugin_dir_url( __FILE__ ) . 'comment-upvoter.js',
			[ 'jquery' ],
			COMMENT_UPVOTER_VERSION,
			false
		);

		// Add localisation.
		wp_localize_script(
			'comment_upvoter',
			'comment_upvoter',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'ajax_nonce' => wp_create_nonce( self::$nonce_action ),
			]
		);

	}

	/**
	 * Enqueue plugin stylesheet.
	 *
	 * @since 0.1
	 */
	public function print_styles() {

		// Add basic stylesheet.
		wp_enqueue_style(
			'comment_upvoter_css',
			plugin_dir_url( __FILE__ ) . 'comment-upvoter.css',
			false,
			COMMENT_UPVOTER_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Respond to AJAX requests.
	 *
	 * @since 0.1
	 */
	public function ajax() {

		// Init return.
		$data = [
			'success' => false,
			'count' => false,
		];

		// Since this is an AJAX request, check security.
		$result = check_ajax_referer( self::$nonce_action, false, false );
		if ( $result === false ) {
			wp_send_json( $data );
		}

		// Get "action". This should be "upvote".
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['is_upvote'] ) ? sanitize_text_field( wp_unslash( $_POST['is_upvote'] ) ) : '';

		// Bail if no action.
		if ( empty( $action ) ) {
			wp_send_json( $data );
		}

		// Bail if not ours.
		if ( $action != self::$action ) {
			wp_send_json( $data );
		}

		// Try to get the comment ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$comment_id = isset( $_POST['comment_id'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['comment_id'] ) ) : false;
		if ( empty( $comment_id ) ) {
			wp_send_json( $data );
		}

		// Bail if not a valid comment.
		$comment = get_comment( $comment_id );
		if ( ! $comment ) {
			wp_send_json( $data );
		}

		// Get existing meta.
		$count = self::get_comment_upvotes( $comment_id );

		// Increment.
		$count++;

		// Okay, update meta.
		update_comment_meta( $comment_id, 'upvotes', $count );

		// Update data.
		$data['success'] = true;
		$data['count'] = $count;

		// That's all folks.
		wp_send_json( $data );

	}

	/**
	 * Show our button.
	 *
	 * @since 0.1
	 *
	 * @param string $existing The existing comment action string.
	 * @param object $comment The WordPress comment object.
	 * @return string $existing The modified comment action string.
	 */
	public function comment_action( $existing, $comment ) {

		// Get ID.
		$comment_id = $comment->comment_ID;

		// Get existing meta.
		$count = self::get_comment_upvotes( $comment_id );

		// Init return.
		$html = '<span class="alignright comment-upvote">';

		// Try and get the requesting URL.
		$link = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		// Add our link.
		$html .= '<a href="' . $link . '" ' .
			'class="comment_upvoter ' . self::$action . '" ' .
			'data-do="' . self::$action . '" ' .
			'data-comment_id="' . $comment_id . '" ' .
			'title="' . self::$label . '">' .
			self::$label . ' (<span class="upvotes">' . $count . '</span>)' .
		'</a>';

		// Close.
		$html .= '</span>';

		// Prepend to existing.
		$existing = $html . $existing;

		// --<
		return $existing;

	}

	/**
	 * Get current number of upvotes.
	 *
	 * @since 0.1
	 *
	 * @param int $comment_id The numeric ID of the comment.
	 * @return int $count The number of upvotes.
	 */
	public function get_comment_upvotes( $comment_id ) {

		// Get existing.
		$count = get_comment_meta( $comment_id, 'upvotes', true );

		// Did we get one?
		if ( $count === '' ) {
			$count = 0;
		}

		// Cast as integer.
		$count = (int) $count;

		// --<
		return $count;

	}


}

/**
 * Instantiate plugin object.
 *
 * @since 0.1
 *
 * @return Comment_Upvoter The plugin instance.
 */
function wp_comment_upvoter_load() {
	return Comment_Upvoter::instance();
}

// Bootstrap Comment Upvoter.
wp_comment_upvoter_load();
