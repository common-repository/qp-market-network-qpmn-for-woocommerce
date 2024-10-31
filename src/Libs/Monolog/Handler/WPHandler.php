<?php
namespace QPMN\Partner\Libs\Monolog\Handler;

use Monolog\Formatter\LineFormatter;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\QP_WP_Option_Config;
use wpdb;

class WPHandler extends AbstractProcessingHandler
{
	//singleton obj
	private static $instance = null;

	private $initialized = false;
	/**
	 * WP db object
	 *
	 * @var wpdb
	 */
	private $wpdb;
	private $tableName;

	const TABLE_NAME = Qpmn_Install::TABLE_NAME_LOG;
	
	public function __construct($wpdb, $level = Logger::DEBUG, bool $bubble = true)
	{
		$this->wpdb = $wpdb;
		parent::__construct($level, $bubble);
	}

	public function write(array $record): void
	{
		if (!$this->initialized) {
			$this->initialize();
		}

		//log when debug mode enabled
		if (QP_WP_Option_Config::isDebugMode()) {

			$values = [
				$record['formatted'],
				$record['datetime']->jsonSerialize(),
			];
			$sql = "INSERT INTO " .sanitize_text_field( $this->tableName ). " (log, created_at)  VALUES (%s,%s)";
			$query = $this->wpdb->prepare($sql, $values);
			$this->wpdb->query($query);
		}
	}

	public function initialize()
	{
		$this->tableName = $this->wpdb->prefix . self::TABLE_NAME;

		$this->initialized = true;
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
			global $wpdb;
			$handler = new WPHandler($wpdb);
			$lineFormatter = new LineFormatter(LineFormatter::SIMPLE_FORMAT, LineFormatter::SIMPLE_DATE);
			$lineFormatter->includeStacktraces(true);
			$handler->setFormatter($lineFormatter);

			self::$instance = $handler;
		}

		return self::$instance;
	}
}