<?php
/*
Plugin Name: Auto Thickbox
Plugin URI: http://www.semiologic.com/software/auto-thickbox/
Description: Automatically enables thickbox on thumbnail images (i.e. opens the images in a fancy pop-up).
Author: Denis de Bernardy, Mike Koepke
Version: 2.5 dev
Author URI: http://www.getsemiologic.com
Text Domain: auto-thickbox
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.

**/



/**
 * auto_thickbox
 *
 * @package Auto Thickbox
 **/

class auto_thickbox {

	protected $anchor_utils;

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @return  void
	 */
	public function load_language( $domain )
	{
		load_plugin_textdomain(
			$domain,
			FALSE,
			$this->plugin_path . 'lang'
		);
	}

	/**
	 * Constructor.
	 *
	 *
	 */
	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'auto-thickbox' );

		add_action( 'plugins_loaded', array ( $this, 'init' ) );
    } #auto_thickbox

	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( !is_admin() && isset($_SERVER['HTTP_USER_AGENT']) &&
      	strpos($_SERVER['HTTP_USER_AGENT'], 'W3C_Validator') === false) {

			if ( !class_exists('anchor_utils') )
			    include $this->plugin_path . '/anchor-utils/anchor-utils.php';

			$this->anchor_utils = new anchor_utils( true );

			add_action('wp_enqueue_scripts', array($this, 'scripts'));
			add_action('wp_enqueue_scripts', array($this, 'styles'));

			add_action('wp_footer', array($this, 'thickbox_images'), 20);

			add_filter('filter_anchor', array($this, 'filter'));
		}
	}

    /**
	 * filter()
	 *
	 * @param array $anchor
	 * @return array $anchor
	 **/

	function filter($anchor) {
		if ( preg_match("/\.(?:jpe?g|gif|png)\b/i", $anchor['attr']['href']) )
			return auto_thickbox::image($anchor);
		elseif ( !empty($anchor['attr']['class']) && in_array('thickbox', $anchor['attr']['class']) )
			return auto_thickbox::iframe($anchor);
		else
			return $anchor;
	} # filter()
	
	
	/**
	 * image()
	 *
	 * @param array $anchor
	 * @return array $anchor
	 **/

	function image($anchor) {
		if ( !preg_match("/^\s*<\s*img\s.+?>\s*$/is", $anchor['body']) )
			return $anchor;
		
		if ( !$anchor['attr']['class'] ) {
			$anchor['attr']['class'][] = 'thickbox';
			$anchor['attr']['class'][] = 'no_icon';
		} else {
			if ( !in_array('thickbox', $anchor['attr']['class']) && !in_array('nothickbox', $anchor['attr']['class']) && !in_array('no_thickbox', $anchor['attr']['class']) )
				$anchor['attr']['class'][] = 'thickbox';
			if ( !in_array('no_icon', $anchor['attr']['class']) && !in_array('noicon', $anchor['attr']['class']) )
				$anchor['attr']['class'][] = 'no_icon';
		}
		
		if ( in_the_loop() && !$anchor['attr']['rel'] )
			$anchor['attr']['rel'][] = 'gallery-' . get_the_ID();
		
		if ( empty($anchor['attr']['title']) ) {
			if ( preg_match("/\b(?:alt|title)\s*=\s*('|\")(.*?)\\1/i", $anchor['body'], $title) ) {
				$anchor['attr']['title'] = end($title);
			}
		}
		
		return $anchor;
	} # image()


    /**
     * iframe()
     *
     * @param $anchor
     * @return string
     */
	
	function iframe($anchor) {
		if ( strpos($anchor['attr']['href'], 'TB_iframe=true') !== false )
			return $anchor;

		# strip anchor ref
		$href = explode('#', $anchor['attr']['href']);
		$anchor['attr']['href'] = array_shift($href);
		
		$anchor['attr']['href'] .= ( ( strpos($anchor['attr']['href'], '?') === false ) ? '?' : '&' )
			. 'TB_iframe=true&width=720&height=540';
		
		return $anchor;
	} # iframe()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		wp_enqueue_script('thickbox');
		wp_localize_script('thickbox', 'thickboxL10n', array(
			'next' => __('Next &gt;', 'auto-thickbox'),
			'prev' => __('&lt; Prev', 'auto-thickbox'),
			'image' => __('Image', 'auto-thickbox'),
			'of' => __('of', 'auto-thickbox'),
			'close' => __('Close', 'auto-thickbox'),
			'l10n_print_after' => 'try{convertEntities(thickboxL10n);}catch(e){};'
		));
	} # scripts()
	

	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		wp_enqueue_style('thickbox');
	} # styles()


	/**
	 * thickbox_images()
	 *
	 * @return void
	 **/

	function thickbox_images() {
		$includes_url = includes_url();
		
		echo <<<EOS

<script type="text/javascript">
var tb_pathToImage = "{$includes_url}js/thickbox/loadingAnimation.gif";
var tb_closeImage = "{$includes_url}js/thickbox/tb-close.png";
</script>

EOS;
	} # thickbox_images()
} # auto_thickbox

$auto_thickbox = auto_thickbox::get_instance();