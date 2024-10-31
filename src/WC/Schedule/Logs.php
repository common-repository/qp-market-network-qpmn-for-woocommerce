<?php

namespace QPMN\Partner\WC\Schedule;

use DateInterval;
use DateTime;
use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\ScheduleLogger;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\QP_WP_Option_Config;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Logs extends Schedule implements ScheduleInterface
{
	const DATETIME_FORMAT = 'Y-m-d H:i:sP';
    const CRON = 'qpmn_debug_log_cleanup_cron_hook';
	const INTERVAL = 'daily';

	const ORDER_FLAG = \QPMN\Partner\Qpmn_Install::META_IS_QPMN_ORDER;

    public static function init()
	{
	}

	public static function deleteAll()
	{
	}

	//alias of delete all
	public static function resetOptions()
	{
		self::deleteAll();
	}

    public function init_hooks()
    {
        add_action(self::CRON, [$this, 'delete_logs']);
    }

	/**
	 * return next scedule datetime
	 *
	 * @return string|false
	 */
	public function nextScheduleTime($cron = self::CRON, $format = self::DATETIME_FORMAT)
	{
		return parent::nextScheduleTime($cron, $format);
	}

	/**
	 * activate 
	 *
	 * @return void
	 */
    public function activate($interval = self::INTERVAL, $cron = self::CRON, $scheduleOptions = QP_WP_Option_Config::SCHEDULE_OPTIONS)
    {
		parent::activate(
			$interval,
			$cron,
			$scheduleOptions
		);
    }

    public function deactivate($cron = self::CRON)
    {
		parent::deactivate($cron);
    }

	/**
	 * update existing imcomplete QPMN order 
	 *
	 * @return void
	 */
    public function cleaning_log()
    {
		/**
		 * @var wpdb $wpdb
		 */
		global $wpdb;
		/**
		 * @var Logger $logger
		 */
		$logger = (ScheduleLogger::instance())->getLogger();
		$logger->debug('Plugin logs cleansing task trigger.');

		try {
        	$tableName = $wpdb->prefix . Qpmn_Install::TABLE_NAME_LOG;
			$now = new DateTime();
			//hardcode to 30days because maximum 30 days logs display in UI
			$now->sub(DateInterval::createFromDateString("30 days"));
			$days = $now->format('Y-m-d'); 
			$sql = "DELETE FROM ". sanitize_text_field($tableName) ." WHERE created_at < %s;";
			$sql = $wpdb->prepare($sql, $days);
			$result = $wpdb->query($sql);

			return $result;
        } catch(Exception $e) {
			$logger->error('cleaning Log failed.',['exception' => $e]);
			return false;
        }
    }
}
