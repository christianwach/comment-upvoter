<?php /*
--------------------------------------------------------------------------------
Plugin Name: Comment Upvoter
Description: Lets logged-in readers click an "upvote" button. Designed for use with CommentPress Core.
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



/*
--------------------------------------------------------------------------------
Comment_Upvoter Class
--------------------------------------------------------------------------------
*/

class Comment_Upvoter {
	
	
	
	/** 
	 * properties
	 */
	
	// our class instance
	private static $instance;
	
	// action
	private static $action;
	
	// label
	private static $label;
	
	
	
	/**
	 * @staticvar array $instance
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
	 * @description: initialise object
	 * @return nothing
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
	 * @description: load translation files
	 * A good reference on how to implement translation in WordPress:
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 */
	public function enable_translation() {
		
		// not used, as there are no translations as yet
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
	 * @description: inject JS
	 * @return nothing
	 */
	public function print_scripts() {
		
		// define
		wp_enqueue_script( 
			'comment_upvoter', 
			plugin_dir_url( __FILE__ ) . 'comment-upvoter.js', 
			array( 'jquery' ), 
			COMMENT_UPVOTER_VERSION
		);
		
		// localise
		wp_localize_script( 
			'comment_upvoter', 
			'comment_upvoter', 
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) 
		);
		
	}
	
	
	
	/**
	 * @description: inject CSS
	 * @return nothing
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
	 * @description: respond to AJAX requests
	 * @return nothing
	 */
	public function ajax() {
		
		// kick out if no param
		if ( ! isset( $_POST['do'] ) ) die();
		
		// get action
		$action = $_POST['do'];
		
		// kick out if not ours
		if ( $action != self::$action ) die();
		
		// get comment ID
		$comment_id = absint( $_POST['comment_id'] );
		
		// kick out if not a valid comment
		if ( ! $comment = get_comment( $comment_id ) ) die();
		
		// get existing meta
		$count = self::get_comment_upvotes( $comment_id );
		
		// increment
		$count++;
		
		// thumbs up!
		update_comment_meta( $comment_id, 'upvotes', $count );
		
		die();
		
	}
	
	
	
	/**
	 * @description: show our button
	 * @return nothing
	 */
	public function comment_action( $existing, $comment ) {
		
		// get ID
		$comment_id = $comment->comment_ID;
		
		// get existing meta
		$count = self::get_comment_upvotes( $comment_id );
		
		// init return
		$html = '<span class="alignright comment-upvote">';
		
		// add our link
		$html .= '<a href="'.$_SERVER['REQUEST_URI'].'" '.
					'class="comment_upvoter '.self::$action.'" '.
					'data-do="'.self::$action.'" '.
					'data-comment_id="'.$comment_id. '" '.
					'title="'.self::$label.'">'.
					self::$label.' (<span class="upvotes">'.$count.'</span>)'.
				 '</a>';
		
		// close
		$html .= '</span>';
		
		// --<
		return $html.$existing;
		
    }
    
    
    
	/**
	 * @description: get current number of upvotes
	 * @param int comment ID
	 * @return int number of upvotes
	 */
	public function get_comment_upvotes( $comment_id ) {
	
		// get existing
		$count = get_comment_meta( $comment_id, 'upvotes', true );
		
		// did we get one?
		if ( $count === '' ) $count = 0;
		
		// --<
		return absint( $count );
		
	}


}



/**
 * @description: instantiate plugin object
 * @return object the plugin instance
 */
function wp_comment_upvoter_load() {
	return Comment_Upvoter::instance();
}

// init Comment Upvoter
wp_comment_upvoter_load();



