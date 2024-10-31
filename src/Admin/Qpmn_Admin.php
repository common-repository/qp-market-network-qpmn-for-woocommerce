<?php

namespace QPMN\Partner\Admin;

use QPMN\Partner\Admin\Obj\Qpmn_Admin as ObjQpmn_Admin;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\QP_WP_Option_Account;

class Qpmn_Admin
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

	private $pages;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->pages = [
			Qpmn_Admin_Menu::ADMIN_MAIN_SLUG => 1,
			Qpmn_Admin_Menu::ADMIN_PRODUCT_SLUG => 1,
			Qpmn_Admin_Menu::ADMIN_ORDER_SLUG => 1,
			Qpmn_Admin_Menu::ADMIN_LOG_SLUG => 1,
		];
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		global $pagenow;
		$page = sanitize_text_field($_GET['page'] ?? null) ;
		if ($pagenow === 'admin.php' && $page && array_key_exists($page, $this->pages)) {
			wp_enqueue_style($this->plugin_name . '-qpmn-boostrap', plugin_dir_url(__FILE__) . 'css/bootstrap/qpmn-bootstrap-iso.min.css', array(), $this->version);
			wp_enqueue_style($this->plugin_name . '-font-awesome-4.7', plugin_dir_url(__FILE__) . '../Public/css/font-awesome/4.7.0/css/font-awesome.min.css', array(), $this->version);
		}
		if ($pagenow === 'admin.php' || $pagenow === 'post.php') {
			wp_enqueue_style($this->plugin_name . '-qpmn-boostrap', plugin_dir_url(__FILE__) . 'css/bootstrap/qpmn-bootstrap-iso.min.css', array(), $this->version);
			if (QPMN_ENV == 'DEBUG') {
				wp_enqueue_style($this->plugin_name . 'admin-css', plugin_dir_url(__FILE__) . 'css/qpmn-admin-iso.css', array(), $this->version);
			} else {
				wp_enqueue_style($this->plugin_name . 'admin-css', plugin_dir_url(__FILE__) . 'css/qpmn-admin-iso.min.css', array(), $this->version);
			}
		}
	}

	private function prepare_builder_url($builderPath, $builderQueryArray) 
	{
		$existsparams = [];
		//build builder url
		$url = $builderPath;
		$urlParts = parse_url($url);
		if (!isset($urlParts['host'])) {
			//backward compatible handle
			//url path store in product metadata and builder domain store in env
			$url = Qpmn_Install::QPMN_PARTNER_BUILDER_ENDPOINT . $builderPath;
			$urlParts = parse_url($url);
		}
		if (isset($urlParts['query'])) {
			//check exists query string found
			//and assign to array
			parse_str($urlParts['query'], $existsparams);
		} 
		$tmpQuery = array_merge([], $existsparams, $builderQueryArray);

		$iframeSrc = "";
		if (isset($urlParts['host'])) {
			//build url
			$host = $urlParts['host'];
			if (isset($urlParts['port'])) {
				$host .= ":".$urlParts['port'];
			}
			$iframeSrc =  $urlParts['scheme'] .'://'. $host. $urlParts['path'] . '?'. http_build_query($tmpQuery);
		}

		return $iframeSrc;
	}
	private function prepare_qpmn_order_data()
	{
		global $post;
		$id = $post->ID;
		$order = wc_get_order($id);
		$isQPMNOrder = !empty($order->get_meta(Qpmn_Install::META_IS_QPMN_ORDER));
		if ($isQPMNOrder) {
			$items = $order->get_items();
			$qpmnItems = [];
			foreach ($items as $item) {
				/**
				 * $item WC_Order_Item_Product
				 */
				$designId = $item->get_meta(Qpmn_Install::META_QPMN_DESIGN_ID);
				$designConfig = $item->get_meta(Qpmn_Install::META_QPMN_DESIGN_CONFIG);

				$product = $item->get_product();
				/**
				 * $product WC_Product | bool 
				 */
				$builderPath = $product->get_meta(Qpmn_Install::META_QPMN_BUILDER_PATH);

				$builderUrl = $this->prepare_builder_url($builderPath, []);

				if (!empty($designId)) {
					$qpmnItems[] = [
						'id' => $item->get_id(),
						'designId' => $designId,
						'designConfig' => $designConfig,
						'isCustomizedDesign' => !empty($designConfig),
						'builderUrl' => $builderUrl,
						'designThumbnail' => $item->get_meta(Qpmn_Install::META_QPMN_DESIGN_THUMBNAIL),
						'state' 	=> wp_create_nonce('qpmn_admin_order_design_' . $designId),
					];
				}
			}

			if (count($qpmnItems)) {
				//qpmn order found

				//dependency script
				$this->load_scripts();

				//order page or plugin admin pages
				wp_register_script(
					$this->plugin_name, 
					plugin_dir_url(__FILE__) . 'js/qpmn-order-design.js', 
					array('jquery',
						$this->plugin_name . '-vuejs',
						$this->plugin_name . '-vuex',
						$this->plugin_name . '-axios',
					), 
					$this->version, 
					true 
				);

				//prepare order data 
				$data = array(
					'orderId' => $id,
					'QPMNOrderId' => $order->get_meta(Qpmn_Install::META_QPMN_ORDER_ID),
					'ajax_url' 	=> rest_url(Qpmn_Install::PLUGIN_API_NAMESPACE),
					'orderPageUrl' => admin_url("post.php?post=$id&action=edit"),
					'nonce' 	=> wp_create_nonce('wp_rest'),
					'items' => $qpmnItems,
				);
				wp_localize_script($this->plugin_name, 'qpmn_admin_order_obj', $data);
				wp_enqueue_script($this->plugin_name);
			}
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{
		global $pagenow;
		$page = sanitize_text_field($_GET['page'] ?? '');
		$action = sanitize_text_field($_GET['action'] ?? '');

		if ($pagenow === 'admin.php' && $page && array_key_exists($page, $this->pages)) {
			//plugin admin pages
			$this->load_scripts();
		}

		if ($pagenow === 'post.php' && $action && $action == 'edit') {
			global $my_admin_page;
			$screen = get_current_screen();
			if ( is_admin() && ($screen->id == 'shop_order') ) {
				$this->prepare_qpmn_order_data();
			}
		}
		if ($pagenow === 'admin.php') {
			wp_register_script($this->plugin_name . 'admin', plugin_dir_url(__FILE__) . 'js/qpmn-admin.js', array('jquery'), $this->version, false);
			wp_localize_script($this->plugin_name . 'admin', 'qpmn_admin_obj', ObjQpmn_Admin::get_obj());
			wp_enqueue_script($this->plugin_name . 'admin');
		}
	}

	private function load_scripts()
	{
		wp_register_script($this->plugin_name . '-bootstrap', plugin_dir_url(__FILE__) . 'js/bootstrap/5.1.3/bootstrap.bundle.min.js', array('jquery'), $this->version, false);
		wp_enqueue_script($this->plugin_name . '-bootstrap');

		if (QPMN_ENV == 'DEBUG') {
			wp_register_script($this->plugin_name . '-vuejs', plugin_dir_url(__FILE__) . 'js/vue/2.6.14/vue.js', [], $this->version, false);
			wp_enqueue_script($this->plugin_name . '-vuejs');
		} else {
			wp_register_script($this->plugin_name . '-vuejs', plugin_dir_url(__FILE__) . 'js/vue/2.6.14/vue.min.js', [], $this->version, false);
			wp_enqueue_script($this->plugin_name . '-vuejs');
		}

		wp_register_script($this->plugin_name . '-vuex', plugin_dir_url(__FILE__) . 'js/vuex/3.6.2/vuex.min.js', [$this->plugin_name . '-vuejs'], $this->version, false);
		wp_enqueue_script($this->plugin_name . '-vuex');

		wp_register_script($this->plugin_name . '-axios', plugin_dir_url(__FILE__) . 'js/axios/0.26.1/axios.min.js', [$this->plugin_name . '-vuejs'], $this->version, false);
		wp_enqueue_script($this->plugin_name . '-axios');

	}

	public function init_admin()
	{

		if (empty(get_option(Qpmn_Install::ACCOUNT_VERIFIED))) {
			//setup page affected by this flag
			QP_WP_Option_Account::saveAndUpdate(false, Qpmn_Install::ACCOUNT_VERIFIED);
		}
	}

	public function add_style_attributes($html, $handle)
	{

		return $html;
	}
}
