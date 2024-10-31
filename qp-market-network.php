<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              na
 * @since             1.0.0
 * @package           QPMN
 *
 * @wordpress-plugin
 * Plugin Name:       QP Market Network (QPMN) for WooCommerce
 * Plugin URI:        https://www.qpmarketnetwork.com/download-center/
 * Description:       QP Market Network (QPMN) is backed by Q P Group, a publicly listed company which has more than 35 years’ experience as a leading printing company with special focus on drop shipping and Next-generation Print-On-Demand (POD) services.
 * Version:           1.4.5
 * Author:            QP Group
 * Author URI:        https://www.qpmarketnetwork.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       qp-market-network
 * Domain Path:       /src/Languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// include the Composer autoload file
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

if( !defined('QPMN_ENV') ) {
	define('QPMN_ENV', sanitize_text_field($_ENV['QPMN_ENV']));
}

if( !defined('QPMN_PARTNER_API_CLIENT_ID') ) {
	define('QPMN_PARTNER_API_CLIENT_ID', sanitize_text_field($_ENV['QPMN_PARTNER_API_CLIENT_ID']));
}
if( !defined('QPMN_ENDPOINT') ) {
	define('QPMN_ENDPOINT', esc_url_raw($_ENV['QPMN_ENDPOINT']));
}
if( !defined('QPMN_PARTNER_API_ENDPOINT') ) {
	define('QPMN_PARTNER_API_ENDPOINT', esc_url_raw($_ENV['QPMN_PARTNER_API_ENDPOINT']));
}
if( !defined('QPMN_PARTNER_BUILDER_ENDPOINT') ) {
	define('QPMN_PARTNER_BUILDER_ENDPOINT', esc_url_raw($_ENV['QPMN_PARTNER_BUILDER_ENDPOINT']));
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'QPMN_VERSION', sanitize_text_field($_ENV['QPMN_VERSION']) );
define( 'QPMN_DB_VERSION', sanitize_text_field($_ENV['QPMN_DB_VERSION'] ));

if( !defined('QPMN_PLUGIN_ROOT') ) {
	define('QPMN_PLUGIN_ROOT', plugin_dir_path( __FILE__ ));
}

if( !defined('QPMN_PLUGIN_ROOT_PHP') ) {
    define( 'QPMN_PLUGIN_ROOT_PHP', dirname(__FILE__).'/'.basename(__FILE__)  );
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-qpmn-activator.php
 */
function activate_qpmn() {
	\QPMN\Partner\Qpmn_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-qpmn-deactivator.php
 */
function deactivate_qpmn() {
	\QPMN\Partner\Qpmn_Deactivator::deactivate();
}

function uninstall_qpmn() {
	(new \QPMN\Partner\Qpmn_Install())->uninstall();
}
 
register_activation_hook( __FILE__, 'activate_qpmn' );
register_deactivation_hook( __FILE__, 'deactivate_qpmn' );
register_uninstall_hook(__FILE__, 'uninstall_qpmn');


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_qpmn() {

	$plugin = new \QPMN\Partner\Qpmn();
	$plugin->run();

}
run_qpmn();
