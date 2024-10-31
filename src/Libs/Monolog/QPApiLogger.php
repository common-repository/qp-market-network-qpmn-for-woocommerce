<?php

namespace QPMN\Partner\Libs\Monolog;

use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\Handler\WPHandler;

class QPApiLogger extends \QPMN\Partner\Libs\Monolog\Logger
{
	/**
	 * Undocumented variable
	 *
	 * @var Logger
	 */
	private $logger;

	const CHANNEL = 'QP_API';

	public function __construct()
	{
		$this->logger = new Logger(self::CHANNEL);
		$this->logger->pushHandler(WPHandler::getInstance());
	}

	public function getLogger(): Logger
	{
		return $this->logger;
	}
}
