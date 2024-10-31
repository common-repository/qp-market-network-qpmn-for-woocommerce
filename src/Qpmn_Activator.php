<?php
namespace QPMN\Partner;

use QPMN\Partner\WC\QP_WP_Option_Account;
use QPMN\Partner\WC\Schedule\Order;
use QPMN\Partner\WC\Schedule\Logs;
use QPMN\Partner\WC\QP_WP_Option_Config;

class Qpmn_Activator {
	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		if(version_compare(PHP_VERSION, '7.4.0', '<')) {

			deactivate_plugins(QPMN_PLUGIN_ROOT_PHP); // Deactivate plugin
			wp_die("Sorry, but you can't run this plugin, it requires PHP 7.4.0 or higher.");
			return;

		}

		$plugin = new Qpmn();
		if (!$plugin->wooCheck()) {
			deactivate_plugins(QPMN_PLUGIN_ROOT_PHP); // Deactivate plugin
			wp_die("Sorry, but you can't run this plugin, it requires WooCommerce installed.");
			return;
		}

		(new Qpmn_Install())->install();

        //schedule
        (new Order())->activate();
		//default options
		//enable log cleansing
		(new Logs())->activate();
		//remove builder model scheduler (default hourly)
        // $productScheduler = new \QPMN\Partner\WC\Schedule\Product();
		// $productScheduler->activiateRemoveBuilderModel();
		// $productScheduler->activiateUpdateBuilderModelMetadata();

		//enable debug mode 
		(new QP_WP_Option_Config())->updateDebugMode();

		//oauth pkce - auto login
		QP_WP_Option_Account::autoLogin();

		flush_rewrite_rules();
	}


}
