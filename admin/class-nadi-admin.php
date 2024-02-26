<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://nadi.pro
 * @since      1.0.0
 *
 * @package    Nadi
 * @subpackage Nadi/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Nadi
 * @subpackage Nadi/admin
 * @author     Nadi Pro <tech@nadi.pro>
 */
class Nadi_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Nadi_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Nadi_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/nadi-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Nadi_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Nadi_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/nadi-admin.js', array( 'jquery' ), $this->version, false );

	}

	public function register_settings()
	{
		// Register a setting for API key
		register_setting('nadi_settings', 'nadi_api_key');

		// Register a setting for Application key
		register_setting('nadi_settings', 'nadi_application_key');

		// Register a setting for Collector Endpoint (for enterprise users)
		register_setting('nadi_settings', 'nadi_collector_endpoint');
	}

	public function settings_page()
	{
		add_options_page('Nadi Settings', 'Nadi', 'manage_options', 'nadi_settings', 'nadi_render_settings_page');
	}

}
