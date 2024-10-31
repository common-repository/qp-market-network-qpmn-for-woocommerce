<?php

namespace QPMN\Partner\Pub;

use QPMN\Partner\Qpmn_Install;

class Qpmn
{

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
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{
		$postType = get_post_type();
		$isProduct = is_product() ?? false;

		if ($postType === 'product' && $isProduct) {
			//product page
			wp_enqueue_style($this->plugin_name . '-boostrap', plugin_dir_url(__FILE__) . '../Admin/css/bootstrap/qpmn-bootstrap-iso.min.css', array(), $this->version);

			wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/qpmn-public-iso.min.css', array(), $this->version, 'all');
			//not recommend self hosting font awesome
		}
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		$cartItemKey = '';
		$postType = get_post_type();
		$isProduct = is_product() ?? false;
		if ($postType === 'product' && $isProduct) {
			$post = get_post();
			$postId = $post->ID ?? '';
			//product page
			if (QPMN_ENV == 'DEBUG') {
				wp_register_script($this->plugin_name . '-vuejs', plugin_dir_url(__FILE__) . '../Admin/js/vue/2.6.14/vue.js', [], $this->version, true);
				wp_enqueue_script($this->plugin_name . '-vuejs');
			} else {
				wp_register_script($this->plugin_name . '-vuejs', plugin_dir_url(__FILE__) . '../Admin/js/vue/2.6.14/vue.min.js', [], $this->version, true);
				wp_enqueue_script($this->plugin_name . '-vuejs');
			}

			wp_register_script($this->plugin_name . '-vuex', plugin_dir_url(__FILE__) . '../Admin/js/vuex/3.6.2/vuex.min.js', [$this->plugin_name . '-vuejs'], $this->version, true);
			wp_enqueue_script($this->plugin_name . '-vuex');

			wp_register_script($this->plugin_name . '-axios', plugin_dir_url(__FILE__) . '../Admin/js/axios/0.26.1/axios.min.js', [$this->plugin_name . '-vuejs'], $this->version, true);
			wp_enqueue_script($this->plugin_name . '-axios');

			$scriptName = 'qpmn-builder';
			wp_register_script(
				$this->plugin_name . $scriptName,
				plugin_dir_url(__FILE__) . 'js/qpmn-builder.js',
				array(
					'jquery',
					$this->plugin_name . '-vuejs',
					$this->plugin_name . '-vuex',
					$this->plugin_name . '-axios',
					'wp-i18n'
				),
				$this->version,
				true
			);

			if (isset($_GET['cart_item_key']) && !empty($_GET['cart_item_key'])) {
				//validate cart item key
				$item = WC()->cart->get_cart_item( $_GET['cart_item_key']);
				if (!empty($item)) {
					$cartItemKey = $_GET['cart_item_key']; 
				}
			}
				
			//originhash generate by hash algorithm crc32 to detect query string data origin
			wp_localize_script($this->plugin_name . $scriptName, 'qpmn_builder_obj', array(
				'ajax_url' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('QPMN\API\WC\Ajax\Cart::update_cart' . $cartItemKey),
				'originhash' => hash('crc32', get_site_url() . $cartItemKey),
			));
			wp_enqueue_script($this->plugin_name . $scriptName);
			$path = plugin_dir_path( __FILE__ ) . '../Languages/';
			wp_set_script_translations($this->plugin_name  . $scriptName, Qpmn_Install::PLUGIN_NAME, $path);
		}
	}

	public function add_style_attributes($html, $handle)
	{

		return $html;
	}
}
