<?php

namespace QPMN\Partner\Admin;

use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\UI\Tables\Order;
use QPMN\Partner\WC\UI\Tables\Product;
use QPMN\Partner\WC;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Qpmn_Admin_Menu
{
    const ADMIN_MAIN_SLUG = 'qpmn_options';
    const ADMIN_PRODUCT_SLUG = 'qpmn_options_products';
    const ADMIN_ORDER_SLUG = 'qpmn_options_orders';
    const ADMIN_LOG_SLUG = 'qpmn_options_logs';
    const ADMIN_AUTH_SLUG = 'qpmn_auth';

    const BLANK_MENUS = [
        'qpmn_options_welcome' => true
    ];

    const PRODUCT_FLAG = Qpmn_Install::META_IS_QPPP_PRODUCT;


    static $order;
    public $orderObj;
    public $productObj;
    public $defaultProductObj;

    public function __construct()
    {
    }

    public function admin_menus()
    {

        $this->admin_menus_init();
        $this->admin_submenus();
		// add_filter('submenu_file', [$this,'welcome_page_submenu_file']);
    }

    public function admin_menus_init()
    {
        add_menu_page(
            'QP Market Network',
            'QP Market Network',
            'manage_options',
            self::ADMIN_MAIN_SLUG,
            array($this, 'admin_setup_page_html')
        );
    }

    public function set_screen($status, $option, $value)
    {
        if ('order_per_page' == $option) {
            return $value;
        }
    }

    public function admin_submenus()
    {
        add_submenu_page(
            self::ADMIN_MAIN_SLUG,
            Qpmn_i18n::__('Setup'),
            Qpmn_i18n::__('Setup'),
            'manage_options',
            self::ADMIN_MAIN_SLUG,
            array($this, 'admin_setup_page_html')
        );

        $hook = add_submenu_page(
            'qpmn_options',
            Qpmn_i18n::__('Products'),
            Qpmn_i18n::__('Products'),
            'manage_options',
            self::ADMIN_PRODUCT_SLUG,
            array($this, 'admin_product_page_html')
        );
        add_action("load-$hook", [$this, 'product_screen_option']);

        $hook = add_submenu_page(
            self::ADMIN_MAIN_SLUG,
            Qpmn_i18n::__('Orders'),
            Qpmn_i18n::__('Orders'),
            'manage_options',
            self::ADMIN_ORDER_SLUG,
            array($this, 'admin_order_page_html')
        );
        add_action("load-$hook", [$this, 'order_screen_option']);

        add_submenu_page(
            self::ADMIN_MAIN_SLUG,
            Qpmn_i18n::__('Debug Logs'),
            Qpmn_i18n::__('Debug Logs'),
            'manage_options',
            self::ADMIN_LOG_SLUG,
            array($this, 'admin_logs_page_html')
        );

        add_submenu_page(
            null,
            Qpmn_i18n::__('Auth'),
            Qpmn_i18n::__('Auth'),
            'manage_options',
            self::ADMIN_AUTH_SLUG,
            array($this, 'admin_auth_page_html')
        );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize($input)
    {

        return sanitize_text_field($input);
    }

    public function admin_product_page_html()
    {
        include_once plugin_dir_path(__FILE__) . 'partials/qpmn-admin-menu-products.php';
    }

    public function admin_order_page_html()
    {
        include_once plugin_dir_path(__FILE__) . 'partials/qpmn-admin-menu-orders.php';
    }

    public function admin_setup_page_html()
    {
        //make sure account setup 
        WC\QP_WP_Option_Account::checkAccountSetup();
        
        include_once plugin_dir_path(__FILE__) . 'partials/qpmn-admin-menu-setup.php';
    }

    public function admin_logs_page_html()
    {
        //a bit hard to decide when should hide/dismiss the admin notices
        //i decide to reset notice when debug log page load because both notice mention to check debug logs

        //reset error notice options
        WC\Schedule\Order::resetOptions();
        WC\QPMN_WC_Cart::resetOptions();

        include_once plugin_dir_path(__FILE__) . 'partials/qpmn-admin-menu-logs.php';
    }

    public function admin_auth_page_html()
    {
        include_once plugin_dir_path(__FILE__) . 'partials/qpmn-admin-menu-auth.php';
    }

    public function product_screen_option()
    {
        $option = 'per_page';
        $args   = [
            'label'   => Qpmn_i18n::__('Products'),
            'default' => 10,
            'option'  => 'product_per_page'
        ];

        add_screen_option($option, $args);
        $this->productObj = new Product();
    }

    public function order_screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => Qpmn_i18n::__('Orders'),
            'default' => 10,
            'option'  => 'order_per_page'
        ];

        add_screen_option($option, $args);

        $this->orderObj = new Order();
    }

    /**
     * hide welcome page from menu and assign highlight menu
     *
     * @param [type] $submenu_file
     * @return void
     */
    public function welcome_page_submenu_file($submenu_file)
    {
        global $plugin_page;
        if ($plugin_page && self::BLANK_MENUS[$plugin_page]) {
            //highlight menu
            $submenu_file = self::ADMIN_MAIN_SLUG;
        }
        foreach (self::BLANK_MENUS as $submenu => $unused) {
            remove_submenu_page(self::ADMIN_MAIN_SLUG, $submenu);
        }

        return $submenu_file;
    }
}
