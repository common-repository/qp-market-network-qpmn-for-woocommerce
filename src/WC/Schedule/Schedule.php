<?php
namespace QPMN\Partner\WC\Schedule;

use DateTime;
use QPMN\Partner\Libs\Monolog\ScheduleLogger;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Schedule
{
	const DATETIME_FORMAT = 'Y-m-d H:i:sP';
	/**
	 * return next scedule datetime
	 *
	 * @return string|false
	 */
	public function nextScheduleTime($cron, $format = self::DATETIME_FORMAT)
	{
		$timestamp = wp_next_scheduled($cron);
		if ($timestamp) {
			$dateTime = new DateTime();
			$dateTime->setTimezone(wp_timezone());
			$dateTime->setTimestamp($timestamp);

			return $dateTime->format($format);
		} else {
			return $timestamp;
		}
	}

	/**
	 * activate 
	 *
	 * @return void
	 */
    public function activate($interval, $cron, $scheduleOptions)
    {
		/**
		 * @var Logger $logger
		 */
		$logger = (ScheduleLogger::instance())->getLogger();

		$nextSchedule = wp_next_scheduled($cron);
		if ($nextSchedule) {
			$logger->debug("activate schedule $cron failed because existing schedule found.");
		}

		//option exists and no active schedule found
		if (!$nextSchedule && in_array($interval, $scheduleOptions)){
			//supported scheudle option found
			wp_schedule_event(time(), $interval, $cron);
			$logger->debug("activate schedule $cron successful");
		} else {
			$logger->debug("activate schedule $cron failed");
		}
    }

    public function deactivate($cron)
    {
		/**
		 * @var Logger $logger
		 */
		$logger = (ScheduleLogger::instance())->getLogger();
		$logger->info("deactive schedule $cron");

		wp_clear_scheduled_hook($cron);
    }

}
