<?php

namespace QPMN\Partner;

use QPMN\Partner\WC;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Qpmn_Install
{

    const UPDATE_VERSIONS = array('1.0.1');
    //plugin settings
    const PLUGIN_NAME           = 'qp-market-network';
    //plugin version including option, db table changes
    const VERSION_NAME          = 'QPMN_VERSION';
    //db schema change version
    const DB_VERSION_NAME       = 'QPMN_DB_VERSION';
    //hide account login field if account verified
    const ACCOUNT_VERIFIED      = '_qpmn_account_verified'; //type = boolean

    const PLUGIN_API_NAMESPACE = 'qpmn-api/v1';
    //protected key for data encryption
    const PLUGIN_PROTECTED_KEY  = '_qpmn_protected_key';


    //account settings
    const CLIENT_ID          = '_qpmn_oauth_client_id';
    const CODE_VERIFIER            = '_qpmn_oauth_code_verifier'; //encrypted
    const ACCOUNT_PARTNER_ID    = '_qpmn_woo_cgp_partner_id';
    //API related options
    const ACCOUNT_TOKEN         = '_qpmn_account_token';
    //1hr
    const ACCOUNT_TOKEN_REMAINING_SECONDS = 3600;

    //Product metadata
    const META_IS_QPPP_PRODUCT          = '_is_qpmn_product';
    const META_QPMN_PRODUCT_ID          = '_qpmn_product_id';
    //flag for allow partner create product without customization feature
    const META_QPMN_PRODUCT_DISABLE_CUSTOMIZATION   = '_qpmn_product_disable_customization';
    const META_QPMN_PRODUCT_DEFAULT_DESIGN_ID   = '_qpmn_product_default_design_id';
    const META_QPMN_PRODUCT_DEFAULT_DESIGN_THUMBNAIL   = '_qpmn_product_default_design_thumbnail';

    const META_QPMN_DESIGN_ID           = '_qpmn_design_id';
    const META_QPMN_DESIGN_CONFIG       = '_qpmn_design_config';
    const META_QPMN_DESIGN_THUMBNAIL    = '_qpmn_design_thumbnail';
    const META_QPMN_BUILDER_PATH        = '_qpmn_builder_path';
    const META_QPMN_BUILDER_TEMPLATE    = '_qpmn_builder_template';
    //flag to decide when to display config btn 
    const META_QPMN_IS_CONFIGURABLE     = '_qpmn_is_configurable';

    const META_QPMN_BUILDER_MODEL_EDIT_URL      = '_qpmn_builder_model_edit_url';
    const META_QPMN_BUILDER_DESIGN_PREVIEW_URL  = '_qpmn_builder_design_preview_url';

    //order 
    const META_IS_QPMN_ORDER            = '_is_qpmn_order';
    const META_QPMN_ORDER_ID            = '_qpmn_wc_order_order_id';
    const META_QPMN_ORDER_NUMBER        = '_qpmn_wc_order_order_number';
    const META_QPMN_ORDER_STATUS        = '_qpmn_wc_order_order_status';
    const META_QPMN_ORDER_SUBTOTAL      = '_qpmn_wc_order_order_subtotal';
    const META_QPMN_ORDER_SHIPPING      = '_qpmn_wc_order_order_shipping';
    const META_QPMN_ORDER_TOTAL         = '_qpmn_wc_order_order_order_total';
    const META_QPMN_ORDER_DATETIME      = '_qpmn_wc_order_datetime';
    const META_QPMN_ORDER_ERROR_MSG     = '_qpmn_wc_order_error_msg';
    const META_QPMN_ORDER_LAST_SYNC_AT  = '_qpmn_wc_order_last_sync_at';

    const CGP_ORDER_COMPLETE_STATUS = 'Completed';
    const CGP_ORDER_PAYMENT_STATUS = 'Await payment';

    //config to modified in setup page
    const CONFIG_DEBUG_MODE             = '_qpmn_config_debug';
    const CONFIG_ORDER_UPDATE_SCHEDULE  = '_qpmn_config_order_update_schedule';

    //qpmn order metadata
    const QPMN_PARTNER_STORE_URL = 'qpmn_partner_store_url';
    const QPMN_PARTNER_ORDER_ID = 'qpmn_partner_order_id';
    const QPMN_PARTNER_DESIGN_ID = 'qpmn_partner_design_id';
    const QPMN_PARTNER_ORDER_CURRENCY = 'qpmn_partner_order_currency';
    const QPMN_PARTNER_RESALE_PRICE = 'qpmn_partner_resale_price';
    const QPMN_PARTNER_ORDER_ITEM_ID = 'qpmn_partner_order_item_id';
    const QPMN_PARTNER_ORDER_ITEM_QTY = 'qpmn_partner_order_item_qty';

    const TABLE_NAME_LOG = 'qpmn_logs';

    const QPMN_PARTNER_API_ENDPOINT = QPMN_PARTNER_API_ENDPOINT;
    const QPMN_PARTNER_API_CLIENT_ID = QPMN_PARTNER_API_CLIENT_ID;
    const QPMN_PARTNER_BUILDER_ENDPOINT = QPMN_PARTNER_BUILDER_ENDPOINT;
    const SECRET_VERIFIED = '_qpmn_auth_secret_verified';
    const PARTNER_VERIFIED = '_qpmn_auth_partner_verified';
    const QPMN_OAUTH_ACCESS_TOKEN = '_qpmn_oauth_access_token';
    const QPMN_OAUTH_REFRESH_TOKEN = '_qpmn_oauth_refresh_token';
    const QPMN_PARTNER_NAME = '_qpmn_partner_name';


    //plugin upgrade if plugin version not match
    function check_version()
    {

        if (is_admin() && get_option(self::VERSION_NAME) != QPMN_VERSION) {
            $this->upgrade();
        }
    }
    /**
     * used for hooks 'plugins_loaded' for munally install
     */
    function check_db_version()
    {
        if (get_option(self::DB_VERSION_NAME) != QPMN_DB_VERSION) {
            $this->install();
        }
    }

    function uninstall()
    {

        $this->drop_tables();
        $this->remove_options();
        //disable created products
    }

    function install()
    {
        $this->db_check();
        $this->create_options();
    }

    function deactivate()
    {
        //make plugin deactivated
        //disable created product
        $product = new WC\QPMN_WC_Product();
        $product->disable_products();

        //schedule
        $orderSchedule = new WC\Schedule\Order();
        $orderSchedule->deactivate();

		//default options
		//disable log cleansing
		(new WC\Schedule\Logs())->deactivate();

		//deactivate builder model scheduler
        // $productScheduler = new \QPMN\Partner\WC\Schedule\Product();
        // $productScheduler->deactiviateRemoveBuilderModel();
        // $productScheduler->deactiviateUpdateBuilderModelMetadata();

		//disable debug mode 
		(new WC\QP_WP_Option_Config())->updateDebugMode(false);

        flush_rewrite_rules();
    }

    function upgrade()
    {

        $current_version = get_option(self::VERSION_NAME);

        foreach (self::UPDATE_VERSIONS as $update_version) {

            if (version_compare($current_version, $update_version, '<')) {
                $this->do_upgrade($update_version);
            }
        }

    }

    /**
     * upgrade including
     *  - table column changes
     *  - plugin option changes
     * 
     */
    function do_upgrade($to_version)
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        /**
         * @var \wpdb $wpdb
         */
        global $wpdb;
        //TODO: seperate upgrade to individual class
        if ($to_version === '1.0.1') {
            $tableName = $wpdb->prefix . self::TABLE_NAME_LOG;
            //add log context column to record exception trace
            $sql = "ALTER TABLE " . sanitize_text_field( $tableName ). " ADD context TEXT NULL AFTER `log`;";
            $result = $wpdb->query($sql);
        }
    }

    /**
     *  update table schema
     */
    function db_check()
    {

        global $wpdb;

        $installed_ver = get_option(self::DB_VERSION_NAME);

        if ($installed_ver != QPMN_DB_VERSION) {

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta($this->get_table_schema());

            $this->upgrade();
            //update db version
            update_option(self::DB_VERSION_NAME, QPMN_DB_VERSION);
        }
    }

    function create_options()
    {
        if (get_option(self::VERSION_NAME) === false) {
            //new install, assign init version
            update_option(self::VERSION_NAME, QPMN_VERSION);
        }

        //init options
        WC\QP_WP_Option_Account::init();
        WC\QP_WP_Option_QPMN_Token::init();
        WC\QP_WP_Option_Config::init();
        WC\Schedule\Order::init();
    }

    /**
     * table descriptions:
     *     qpmn_logs: plugin logging, implemented with monolog
     *
     */
    function get_table_schema()
    {
        /**
         * @var \wpdb $wpdb
         */
        global $wpdb;
        $collate = '';

        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }

        $tableName = $wpdb->prefix . self::TABLE_NAME_LOG;

        $tables = "CREATE TABLE " . sanitize_text_field( $tableName )." (
			id BIGINT UNSIGNED NOT NULL auto_increment,
			log text,
			created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL ,
            PRIMARY KEY (id)
		) $collate;";

        return $tables;
    }

    /**
     * Drop tables.
     *
     * @return void
     */
    function drop_tables()
    {
        global $wpdb;

        $tables = $this->get_tables();

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
    }

    /**
     * qppp = qp personalized product 
     */
    function get_tables()
    {
        global $wpdb;

        return array(
            "{$wpdb->prefix}".self::TABLE_NAME_LOG,
        );
    }

    function remove_options()
    {
        delete_option(self::VERSION_NAME);
        delete_option(self::DB_VERSION_NAME);

        WC\QP_WP_Option_Account::deleteAll();

        WC\QP_WP_Option_QPMN_Token::deleteAll();

        WC\QP_WP_Option_Protected_Key::deleteAll();

        WC\QP_WP_Option_Config::deleteAll();

        WC\Schedule\Order::deleteAll();
        //TODO: rename delete all to reset options for better naming 
        WC\QPMN_WC_Cart::resetOptions();
    }
}
