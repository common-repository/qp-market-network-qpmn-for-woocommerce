<?php

namespace QPMN\Partner;

use QPMN\Partner\Admin;
use QPMN\Partner\WC;
use QPMN\Partner\Pub;

class Qpmn
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Qpmn_Loader    $loader    Maintains and registers all hooks for the plugin.
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
	public function __construct()
	{
		if (defined('QPMN_VERSION')) {
			$this->version = QPMN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'qpmn';


		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_rest_api();


		/**
		 * woo-commerce  
		 */
		if ($this->wooCheck()) {
			$this->define_wc_hooks();
		}
	}

	/**
	 * check required dependency plugin installed
	 * - wooooooocommerce
	 * - qpmn
	 *
	 * @return void
	 */
	public function wooCheck()
	{
		$result = false;
		$requiredPlugin = 'woocommerce/woocommerce.php';

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		if (is_multisite()) {
			if (is_plugin_active_for_network($requiredPlugin)) {
				$result = is_plugin_active_for_network($requiredPlugin);
			} else {
				$result = is_plugin_active($requiredPlugin);
			}

		} else {
			$result = is_plugin_active($requiredPlugin);
		}
   
		return $result;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Qpmn_Loader. Orchestrates the hooks of the plugin.
	 * - Qpmn_i18n. Defines internationalization functionality.
	 * - Qpmn_Admin. Defines all hooks for the admin area.
	 * - Qpmn_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{
		$this->loader = new Qpmn_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Qpmn_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new Qpmn_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

		$this->loader->add_filter('load_textdomain_mofile', $plugin_i18n, 'load_my_own_textdomain', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		$pluginHooks = new Qpmn_Install();
		$this->loader->add_action('init', $pluginHooks, 'check_version', 20);
		$this->loader->add_action('plugins_loaded', $pluginHooks, 'check_db_version');

		$pluginHooks = new Admin\Qpmn_Admin($this->get_plugin_name(), $this->get_version());
		$this->loader->add_action('admin_enqueue_scripts', $pluginHooks, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $pluginHooks, 'enqueue_scripts');
		$this->loader->add_action('admin_init', $pluginHooks, 'init_admin');
        $this->loader->add_filter('style_loader_tag', $pluginHooks, 'add_style_attributes', 10, 2);

		$pluginHooks = new Admin\Qpmn_Admin_Menu();
        $this->loader->add_filter('set-screen-option', $pluginHooks, 'set_screen', 10, 3);


		$pluginHooks = new Admin\Qpmn_Admin_Menu();
		//add admin menu
		$this->loader->add_action('admin_menu', $pluginHooks, 'admin_menus');

		//schedule hooks
		$plugin_order_cron = new WC\Schedule\Order();
		$plugin_order_cron->init_hooks();
	}

	private function define_wc_hooks()
	{
		$plugin_product = new WC\QPMN_WC_Product();
		$plugin_order = new WC\QPMN_WC_Order();
		// $plugin_order_metabox = new QP_WC_Order_Metabox();
		$plugin_cart = new WC\QPMN_WC_Cart();
		$plugin_account = new WC\QP_WP_Option_Account();

		//create default qp product
		$this->loader->add_action('init', $plugin_product, 'init_hooks');

		$this->loader->add_action('init', $plugin_account, 'init_hooks');

		//cart item metadata
		$this->loader->add_action('init', $plugin_cart, 'init_hooks');

		//add custom field to order item meta list
		$this->loader->add_action('init', $plugin_order, 'init_hooks');

		// $this->loader->add_action('init', $plugin_order_metabox, 'init_hooks');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new Pub\Qpmn($this->get_plugin_name(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_filter('style_loader_tag', $plugin_public, 'add_style_attributes', 10, 2);

		$plugin_ajax = new WC\Ajax\Cart();
		$this->loader->add_action('init', $plugin_ajax, 'init_hooks');
	}

	/**
	 * rest api with permission check (for logged in user)
	 *
	 * @return void
	 */
	private function define_rest_api()
	{
		$plug_api = new WC\API\QPMN\Account();
		$this->loader->add_action('rest_api_init', $plug_api, 'register_routes');

		$plug_api = new WC\API\WC\QPMNProduct();
		$this->loader->add_action('rest_api_init', $plug_api, 'register_routes');

		$plug_api = new WC\API\WC\Setting();
		$this->loader->add_action('rest_api_init', $plug_api, 'register_routes');

		$plug_api = new WC\API\WC\Logs();
		$this->loader->add_action('rest_api_init', $plug_api, 'register_routes');

		$plug_api = new WC\API\QPMN\Product();
		$this->loader->add_action('rest_api_init', $plug_api, 'register_routes');
		
		$plug_api = new WC\API\WC\OrderMetadata();
		$this->loader->add_action('rest_api_init', $plug_api, 'register_routes');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Qpmn_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}
}
