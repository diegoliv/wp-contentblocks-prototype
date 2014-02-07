<?php
/*
Plugin Name: Content Blocks
Plugin URI: http://wordpress.org/plugins/wp-content-blocks/
Description: Add "blocks" to your content
Version: 1.0.2
Author: The WordPress Team
Author URI: http://wordpress.org/
License: GNU General Public License v2 or later
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class WP_Content_Blocks {
	const FEATURE = 'content-blocks';
	const VERSION = '1.0.2';

	public $name = '';
	
	protected static $instance = null;
	protected $plugin_slug = 'wp-content-blocks-v2';
	protected $plugin_dir = null;

	// get instance of plugin
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	private function __construct() {

		$this->plugin_dir = WP_PLUGIN_URL . '/' . $this->plugin_slug;

		// initialize the plugin
		add_action( 'admin_init', array( $this, 'init') );
		add_action( 'admin_init', array( $this, 'register_content_blocks') );
		add_action( 'edit_form_after_editor', array( $this, 'content_blocks_markup') );

		// assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts') );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles') );
		add_action( 'admin_print_scripts', array( $this, 'print_scripts') );
		add_action( 'admin_print_styles', array( $this, 'print_styles') );

		// returns the content-blocks collection via ajax
		add_action( 'wp_ajax_get_content_blocks', array( $this, 'get_content_blocks_coll') );
		add_action( 'wp_ajax_get_cb_button_tpl', array( $this, 'blocks_button_tpl') );

	}

	function init() {

		// add content blocks support
		add_post_type_support( 'post', self::FEATURE );

		// remove default post editor
		remove_post_type_support( 'post', 'editor' );
		// wp_enqueue_media();
	}

	function enqueue_scripts(){
		wp_register_script( 'tinymce_4', '//tinymce.cachefly.net/4.0/tinymce.min.js', null, '4.0', true );

		$skin = array(
			'CEUXskin' => plugin_dir_url( __FILE__ ) .'css/ceux-tinymce/'
		);

		wp_localize_script( 'tinymce_4', 'tinymce_vars', $skin );

		// wp_register_script( 'post-CEUX',	$this->plugin_dir . '/js/post-ceux.js', array( 'tinymce_4'), self::VERSION, true );
		wp_register_script( 'ceux-main-js',	$this->plugin_dir . '/js/main.js', array( 'backbone', 'jquery-ui-sortable'), self::VERSION, true );
		// wp_register_script( 'ceux-main-js',	$this->plugin_dir . '/js/main.js', array(), self::VERSION, true );
	}

	function enqueue_styles(){
		wp_register_style( 'ceux-style-css', $this->plugin_dir . '/css/style.css', array(), self::VERSION );
	}

	function print_scripts(){
		global $pagenow;

		if( get_post_type() == 'post' && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) ){
			// wp_deregister_script( 'tiny_mce' );
			// wp_deregister_script( 'post' );
			// wp_deregister_script( 'editorremov' );
			// wp_deregister_script( 'editor-functions' );
			wp_enqueue_script( 'tinymce_4' );				// tinyMCE 4
			// wp_enqueue_script( 'post-CEUX' );				// replaces post.min.js, removing tinyMCE code that causes an error
			// wp_enqueue_script( 'underscore' );
			// wp_enqueue_script( 'backbone' );
			// wp_enqueue_script( 'jquery-ui-sortable' );
			// wp_enqueue_script( 'tiny_mce' );
			wp_enqueue_script( 'ceux-main-js' );
		}

	}

	function print_styles(){
		global $pagenow;

		if( get_post_type() == 'post' && ( $pagenow == 'post-new.php' || $pagenow == 'post.php' ) ){
			wp_enqueue_style( 'ceux-style-css' );
		}

	}


	/*
	* Returns the global content blocks collection.
	*
	*/
	function register_content_blocks(){
		global $wp_content_blocks_tpl;

		// text
		register_content_block( array(
			'name' => __( 'Text' ),
			'slug' => 'text',
			'icon' => 'dashicons-format-aside',
			'view' => 'textView',
		), 'cb_text_tpl', true );

		// image
		register_content_block( array(
			'name' => __( 'Image' ),
			'slug' => 'image',
			'icon' => 'dashicons-format-image',
			'view' => 'imgView',
		), 'cb_img_tpl', true );

		// additional view for image content block
		$wp_content_blocks_tpl[] = array( 
			'slug' => 'image-placeholder', 
			'callback' => 'cb_img_placeholder_tpl', 
			'is_main' => false 
		);

		// gallery
		register_content_block( array(
			'name' => __( 'Gallery' ),
			'slug' => 'gallery',
			'icon' => 'dashicons-format-gallery',
			'view' => 'galleryView',
		), 'cb_gallery_tpl', true );

		// Audio
		register_content_block( array(
			'name' => __( 'Audio' ),
			'slug' => 'audio',
			'icon' => 'dashicons-format-audio',
			'view' => 'audioView',
		), 'cb_audio_tpl', true );

		// video
		register_content_block( array(
			'name' => __( 'Video' ),
			'slug' => 'video',
			'icon' => 'dashicons-format-video',
			'view' => 'videoView',
		), 'cb_video_tpl', true );

		// Quote
		register_content_block( array(
			'name' => __( 'Quote' ),
			'slug' => 'quote',
			'icon' => 'dashicons-format-quote',
			'view' => 'quoteView',
		), 'cb_quote_tpl', true );

		// Code
		register_content_block( array(
			'name' => __( 'Code' ),
			'slug' => 'code',
			'icon' => 'dashicons-format-status',
			'group' => 'other',
			'view' => 'codeView',
		), 'cb_code_tpl', true );

		// Tweet
		register_content_block( array(
			'name' => __( 'Tweet' ),
			'slug' => 'tweet',
			'icon' => 'dashicons-twitter1',
			'group' => 'other',
			'view' => 'tweetView',
		), 'cb_tweet_tpl', true );

		// Embed
		register_content_block( array(
			'name' => __( 'Embed' ),
			'slug' => 'embed',
			'icon' => 'dashicons-welcome-add-page',
			'group' => 'other',
			'view' => 'embedView',
		), 'cb_embed_tpl', true );

		// // Social
		// register_content_block( array(
		// 	'name' => __( 'Social' ),
		// 	'slug' => 'social',
		// 	'icon' => 'dashicons-format-chat',
		// 	'group' => 'other'
		// ) );

		// // Map
		// register_content_block( array(
		// 	'name' => __( 'Map' ),
		// 	'slug' => 'map',
		// 	'icon' => 'dashicons-location',
		// 	'group' => 'other'
		// ) );

		// // Slides
		// register_content_block( array(
		// 	'name' => __( 'Slides' ),
		// 	'slug' => 'slides',
		// 	'icon' => 'dashicons-slides',
		// 	'group' => 'other'
		// ) );

		// // Calendar
		// register_content_block( array(
		// 	'name' => __( 'Calendar' ),
		// 	'slug' => 'calendar',
		// 	'icon' => 'dashicons-calendar',
		// 	'group' => 'other'
		// ) );

		// // Chart
		// register_content_block( array(
		// 	'name' => __( 'Chart' ),
		// 	'slug' => 'chart',
		// 	'icon' => 'dashicons-bargraph',
		// 	'group' => 'other'
		// ) );

	}

	/*
	* "Hacks" the post editor with custom markup and implements the 
	* initial markup needed for the content blocks editor. (this is temporary)
	*
	*/
	function content_blocks_markup(){ 
		global $post;
?>

	<div id="postdivrich" class="postarea">

		<div id="wp-editor-toolbar"></div>
		<div id="wp-content-wrap" class="wp-core-ui wp-editor-wrap">
			<div id="wp-content-editor-container" class="wp-editor-container">
				<div id="content-blocks"></div>
				<textarea class="wp-editor-area" style="display:none;" cols="40" name="content" id="content"><?php echo get_post_field( 'post_content', $post->ID ); ?></textarea>
			</div>
		</div>

	</div>

		<div id="wp-blocks">
			<a href="#" id="add-block"><span class="dashicons dashicons-plus-small"></span><?php _e( 'Add Content Block' ) ?></a>
			<div id="blocksSelect">
				<span class="arrow-up"></span>
				<div id="search-container"><span class="dashicons dashicons-search"></span>
					<input type="text" name="blocks-search" id="blocks-search" value="" placeholder="<?php _e( 'Search for blocks to add' ) ?>">
				</div>
				<div id="blocks-container">
					<div class="blocks-group" id="defaults-container"></div>
					<div class="blocks-group" id="others-container"></div>											
					<div class="blocks-group" id="results-container"></div>											
				</div>
			</div>
		</div>

		<!-- templates for every content block -->
		<?php //$this->edit_prototype() ?>
		<?php $this->build_cb_templates() ?>

	<?php
	}

	function build_cb_templates(){
		global $wp_content_blocks_tpl;

		foreach( $wp_content_blocks_tpl  as $cb_tpl){
			register_content_block_view( $cb_tpl['slug'], $cb_tpl['callback'], $cb_tpl['is_main'], $cb_tpl['view'] );
		}
	}

	/*
	* Returns the global content blocks collection.
	*
	*/
	function get_content_blocks_coll(){
		global $wp_content_blocks;

		// send back data as JSON response
		wp_send_json( $wp_content_blocks );

	}

	/*
	* Returns the content block trigger button template.
	*
	*/
	function blocks_button_tpl(){
		$tpl = '<div class="customBlock" data-type="<%= slug %>"><span class="block-image dashicons <%= icon %>"></span><%= name %></div>';

		die( apply_filters( 'content_blocks_button_tpl', $tpl ) );
	}

}

global $wp_content_blocks;
$wp_content_blocks = array();
WP_Content_Blocks::get_instance();

function register_content_block_group( $slug, $args ) {
	global $wp_content_blocks;
	$wp_content_blocks[$slug] = array(
		'blocks' => array(),
		'args' => $args
	);
}


/**
 * Register a Content Block
 * @param  Array $args
 * @param  Function $template
 * @param  Bool $is_main
 * @return null
 */
function register_content_block( $args, $template, $is_main ) {

	global $wp_content_blocks, $wp_content_blocks_tpl;

	$defaults = array(
		'name' => '',						// block label
		'slug' => 'wp-'. $args['slug'],		// slug for the content-block type
		'icon' => 'dashicons-cog',			// dashicon icon
		'group' => 'default',				// if is a default block, true, otherwise, false
		'view' => 'textView'
	);

	$params = wp_parse_args( $args, $defaults );

	$wp_content_blocks[] = $params;

	$wp_content_blocks_tpl[] = array(
		'slug' => 'wp-'. $params['slug'],
		'callback' => $template,
		'is_main' => $is_main,
		'view' => $params['view']
	);
}


/**
 * Build Content Block Template
 * @param  String $slug
 * @param  function $callback
 * @param  Bool $is_main
 * @return Template
 */
function register_content_block_view( $slug, $callback, $is_main, $view ){ ?>

	<!-- <?php echo $slug ?>-->
	<script type="text/template" id="<?php echo $slug ?>">
	<?php if( $is_main ): ?>
		<ul class="ctrlbar">
			<% if(move){ %> <li class="move" title="<?php _e( 'Drag Content Block' ) ?>"><span class="dashicons dashicons-tinymce-justify"></span></li> <% } %>
			<li class="move-up" title="<?php _e( 'Move Up' ) ?>"><span class="dashicons dashicons-arr-up"></span></li>
			<li class="move-down" title="<?php _e( 'Move Down' ) ?>"><span class="dashicons dashicons-arr-down"></span></li>
		</ul>

		<% if(remove){ %><div class="remove" title="<?php _e( 'Remove Content Block' ) ?>"><span class="dashicons dashicons-no"></span></div><% } %>

		<div id="<%= wp_id %>" class="<%= block_type %>" data-view="<?php echo $view ?>">
			<?php call_user_func( $callback ) //insert block template markup here ?>
		</div>

	<?php else: ?>
		<?php call_user_func( $callback ) //insert block template markup here ?>
	<?php endif; ?>

	</script> 

<?php
}

function get_content_blocks() {
	global $wp_content_blocks;
	return $wp_content_blocks;
}

// templates for each content block

function cb_text_tpl(){ ?>
	<div class="wp-block editable" id="text-<%= wp_id %>">
		<%= block_content %>
	</div>
<?php
}

function cb_img_tpl(){ ?>
	<div class="wp-block drag-drop">
		<div class="drag-drop-area supports-drag-drop">
			<h2 class="block-title"><?php _e( 'Add an Image' ); ?></h2>
			<a href="#" class="open-modal">
				<span class="dashicons dashicons-format-image"></span>
				<span class="label"><?php _e( 'Drop an image here or click to upload' ); ?></span>
			</a>
		</div>
	</div>
<?php
}

function cb_img_placeholder_tpl(){ ?>
	<div class="img-bar">
		<ul>
			<li class="opt-align align-none selected"><span class="dashicons dashicons-align-none"></span></li>
			<li class="opt-align align-left"><span class="dashicons dashicons-align-left"></span></li>
			<li class="opt-align align-center"><span class="dashicons dashicons-align-center"></span></li>
			<li class="opt-align align-right"><span class="dashicons dashicons-align-right"></span></li>
			<li class="separator"></li>
			<li class="opt-size size-thumbnail"><span class="icon-thumb"></span></li>
			<li class="opt-size size-medium"><span class="icon-medium"></span></li>
			<li class="opt-size size-full selected"><span class="icon-full"></span></li>
			<li class="separator"></li>
			<li class="remove-img"><span class="dashicons dashicons-xit"></span></li>
		</ul>
	</div>
	<div class="wp-image-placeholder">
		<img src="<%= url %>" id="<%= id %>" class="img-file alignnone">
	</div>
<?php
}

function cb_gallery_tpl(){ ?>
	<div class="wp-block drag-drop">
		<div class="drag-drop-area supports-drag-drop">
			<h2 class="block-title"><?php _e( 'Add a Gallery' ); ?></h2>
			<a href="#" class="open-modal">
				<span class="dashicons dashicons-format-gallery"></span>
				<span class="label"><?php _e( 'Drop images here or click to upload' ); ?></span>
			</a>
		</div>
	</div>
<?php
}

function cb_audio_tpl(){ ?>
	<div class="wp-block">
		<a href="#" class="open-modal">
			<span class="dashicons dashicons-format-audio"></span>
			<span class="label"><?php _e( 'Click here to upload or add an audio file' ); ?></span>
		</a>	
		<p><?php _e( 'Or, enter a video URL from your favorite audio sharing service below:' ) ?></p>
		<p>
		<div class="embed-wrapper">
			<input type="text" class="oembed" value="" placeholder="Paste an audio url into here">
			<button class="button"><?php _e( 'Fetch Audio' ); ?></button></p>
		</div>
	</div>
<?php
}

function cb_video_tpl(){ ?>
	<div class="wp-block">
		<a href="#" class="open-modal">
			<span class="dashicons dashicons-format-video"></span>
			<span class="label"><?php _e( 'Click here to upload or add a video file' ); ?></span>
		</a>	
		<p><?php _e( 'Or, enter a video URL from your favorite video sharing service below:' ) ?></p>
		<p>
		<div class="embed-wrapper">
			<input type="text" class="oembed" value="" placeholder="<?php _e( 'Paste a video url here' ) ?>">
			<button class="button"><?php _e( 'Fetch Video' ); ?></button></p>
		</div>
	</div>
<?php
}

function cb_code_tpl(){ ?>
	<pre>
		<code class="wp-block editable" id="code-<%= wp_id %>">
			<%= block_content %>
		</code>
	</pre>
<?php
}

function cb_tweet_tpl(){ ?>
	<h2><span class="title-image dashicons dashicons-twitter1"></span>Insert your tweet url</h2>
	<input type="text" class="input" value="<%= block_content %>">
<?php
}

function cb_quote_tpl(){ ?>
	<h2><span class="title-image dashicons dashicons-format-quote"></span><?php _e( 'Quote' ) ?></h2>
	<textarea id="quote_<%= wp_id %>"><%= block_content %></textarea>
	<input type="text" class="input quote-who" value="" placeholder="Who said that?">
	<input type="text" class="input quote-where" value="" placeholder="Where did it was said?">
<?php
}

function cb_embed_tpl(){ ?>
	<h2><span class="title-image dashicons dashicons-welcome-add-page"></span>Insert your embed code here</h2>
	<textarea class="input"><%= block_content %></textarea>
<?php
}