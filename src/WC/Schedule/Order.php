<?php

namespace QPMN\Partner\WC\Schedule;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\ScheduleLogger;
use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\QP_WP_Option_Config;
use QPMN\Partner\WC\QPMN_WC_Order;
use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Order extends Schedule implements ScheduleInterface
{
	const DATETIME_FORMAT = 'Y-m-d H:i:sP';
    const CGP_ORDER_CRON = 'qpmn_update_cgp_order_cron_hook';

	const ORDER_FLAG = Qpmn_Install::META_IS_QPMN_ORDER;
    const CGP_ORDER_COMPLETE_STATUS = Qpmn_Install::CGP_ORDER_COMPLETE_STATUS;

	const META_QPMN_ORDER_ID     = Qpmn_Install::META_QPMN_ORDER_ID;
	const META_QPMN_ORDER_STATUS = Qpmn_Install::META_QPMN_ORDER_STATUS;

	const OPTION_SYNC_ORDER_FAILED = 'qpmn_sync_order_failed';

    public static function init()
	{
		update_option(self::OPTION_SYNC_ORDER_FAILED, false);
	}

	public static function deleteAll()
	{
		delete_option(self::OPTION_SYNC_ORDER_FAILED);
	}

	//alias of delete all
	public static function resetOptions()
	{
		self::deleteAll();
	}

    public function init_hooks()
    {
        add_action(self::CGP_ORDER_CRON, [$this, 'update_order']);
        add_action('admin_notices', [$this, 'sync_order_failed_notice']);
    }

	/**
	 * return next scedule datetime
	 *
	 * @return string|false
	 */
	public function nextScheduleTime($cron = self::CGP_ORDER_CRON, $format = self::DATETIME_FORMAT)
	{
		return parent::nextScheduleTime($cron, $format);
	}

	/**
	 * activate 
	 *
	 * @return void
	 */
    public function activate($interval = null, $cron = self::CGP_ORDER_CRON, $scheduleOptions = QP_WP_Option_Config::SCHEDULE_OPTIONS)
    {
		$interval = get_option(QP_WP_Option_Config::OPTION_SCHEDULE, $interval);
		if ($interval) {
			parent::activate(
				$interval,
				$cron,
				$scheduleOptions
			);
		}
    }

    public function deactivate($cron = self::CGP_ORDER_CRON)
    {
		parent::deactivate($cron);
    }


	/**
	 * update existing imcomplete QPMN order 
	 *
	 * @return void
	 */
    public function update_order()
    {
		/**
		 * @var Logger $logger
		 */
		$logger = (ScheduleLogger::instance())->getLogger();
		$logger->debug('Schedule update orders triggered.');

		try {
			$updatedOrders = [];
			$orderArgs = array(
				'post_type' => 'shop_order',
				'post_status' => array_keys(wc_get_order_statuses()),
				'meta_query' => array(
					array(
						'key' => self::ORDER_FLAG,
						'value' => true,
					),
					array(
						'key' => self::META_QPMN_ORDER_STATUS,
						'value' => self::CGP_ORDER_COMPLETE_STATUS,
						'compare' => '!='
					),
				),
				//no paging
				'posts_per_page' => -1,
				'fields' => 'ids',
			);
			$order_query = new WP_Query($orderArgs);
			$ids = $order_query->get_posts();

			$QPWCOrder = new QPMN_WC_Order();
			$CGPOrderIds = [];
			foreach($ids as $id) {
				$CGPOrderIds[$id] = $QPWCOrder->get_order_meta($id, self::META_QPMN_ORDER_ID);
			}

			if (!empty($CGPOrderIds)) {

				//remove empty entries
				$CGPOrderIds = array_filter($CGPOrderIds, 'strlen');

				// loop over the array of record IDs and delete them
				$updatedOrders = $QPWCOrder->bulk_get_cgp_order($CGPOrderIds);

				if (count($CGPOrderIds) != count($updatedOrders)) {
					//notice admin some order failed to sync
					update_option(self::OPTION_SYNC_ORDER_FAILED, true);
				}
			}

			return $updatedOrders;
        } catch(Exception $e) {
			update_option(self::OPTION_SYNC_ORDER_FAILED, true);
			$logger->error('Schedule update orders failed.',['exception' => $e]);
        }
    }

	public function sync_order_failed_notice()
	{
		if (get_option(self::OPTION_SYNC_ORDER_FAILED)) {
			$url = admin_url("admin.php?page=qpmn_options_logs");
			$class = 'notice notice-error';
			$message = sprintf(Qpmn_i18n::__( 'QP market nework order auto update failed. Please go to <a href="%1$s">Debug Logs</a> to check reason.'), $url);
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message ); 
		}
	}

}
