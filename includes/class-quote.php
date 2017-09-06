<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://
 * @since      1.0.0
 *
 * @package    Quote
 * @subpackage Quote/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Quote
 * @subpackage Quote/includes
 * @author     Rein.dre <reinhold.deb@zoho.com>
 */
class Quote {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Quote_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PLUGIN_VERSION' ) ) {
			$this->version = PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'quote';

		$this->load_dependencies();
		$this->set_locale();

		add_action('init', array($this, 'set_location_trading_hour_days'));
		add_action('init', array($this, 'register_location_content_type'));
		add_action('add_meta_boxes', array($this, 'add_location_meta_boxes'));
		// add_action('save_post_wp_locations', array($this, 'save_location')););
		
		$this->define_admin_hooks();
		$this->define_public_hooks();

		add_filter('the_content', array($this, 'prepend_location_meta_to_content'));

	}
	
	public function set_location_trading_hour_days() {
		$this->wp_location_trading_hour_days = apply_filters(
			'wp_location_trading_hours_days',
			array(
				'monday' => 'Monday',
				'tuesday' => 'Tuesday',
				'wednesday' => 'Wednesday',
				'thursday' => 'Thursday',
				'friday' => 'Friday',
				'saturday' => 'Saturday',
				'sunday' => 'Sunday'
			)
		);
	}
	
	public function register_location_content_type() {
		$labels = array(
			'name'				=> 'Quote',
			'singular_name'		=> 'Quote',
			'menu_name'			=> 'Quotes',
			'name_admin_bar'	=> 'Quote',
			'add_new'			=> 'Add New',
			'add_new_item'		=> 'Add New Quote',
			'new_item'			=> 'New Quote',
			'edit_item'			=> 'Edit Quote',
			'view_item'			=> 'View Quote',
			'all_items'			=> 'All Quotes',
			'search_items'		=> 'Search Quotes',
			'parent_item_colon'	=> 'Parent Quotes:',
			'not_found'			=> 'No Quotes found.',
			'not_found_in_trash'=> 'No Quotes found in Trash.',
		);

		$args = array(
			'labels'			=> $labels,
			'public'			=> true,
			'publicly_queryable'=> true,
			'show_ui'			=> true,
			'show_in_nav'		=> true,
			'query_var'			=> true,
			'hierarchical'		=> false,
			'supports'			=> array('title', 'thumbnail', 'editor'),
			'has_archive'		=> true,
			'menu_position'		=> 20,
			'show_in_admin_bar'	=> true,
			'menu_icon'			=> 'dashicons-location-alt',
			'rewrite'			=> array('slug' => 'locations', 'with_front' => 'true')
		);

		register_post_type('wp_locations', $args);
	}
	
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Quote_Loader. Orchestrates the hooks of the plugin.
	 * - Quote_i18n. Defines internationalization functionality.
	 * - Quote_Admin. Defines all hooks for the admin area.
	 * - Quote_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-quote-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-quote-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-quote-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-quote-public.php';

		$this->loader = new Quote_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Quote_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Quote_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Quote_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Quote_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Quote_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
