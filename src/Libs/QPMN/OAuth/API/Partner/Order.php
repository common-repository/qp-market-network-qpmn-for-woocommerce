<?php

namespace QPMN\Partner\Libs\QPMN\OAuth\API\Partner;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\HttpClient\Obj\QPMNOrder;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

if (!defined('ABSPATH')) {
	exit;
}

class Order
{
	private $url = QPMN_PARTNER_API_ENDPOINT;
	/**
	 *
	 * @var HttpClientInterface $client
	 */
	private $client;
	/**
	 * @var Logger $logger
	 */
	private $logger;

	public function __construct($token)
	{
		//retry 3 times
		$client = HttpClient::create([
			'auth_bearer' => $token
		]);
		$client = ScopingHttpClient::forBaseUri($client, $this->url . '/wp-json/qpmn-api/v1/partner/');

		$this->client = $client;
		/**
		 * @var Logger $logger
		 */
		$this->logger = (PluginLogger::instance())->getLogger();
	}

	/**
	 * Undocumented function
	 *
	 * @param QPMNOrder $order
	 * @return void
	 */
	public function create($order)
	{
		try {
			$response = $this->client->request('POST', 'order', [
				'body' => $order->CGPData()

			]);
			return $response->toArray(false);
		} catch (Exception $e) {
			$this->logger->debug(
				'create order failed because ' . $e->getMessage(),
				[
					'payload' => $order->CGPData(),
					'response' => $response->getContent(false),
					'exception' => $e
				]
			);
			throw $e;
		}
	}

	/**
	 * return response for concurrent requesets
	 *
	 * @param QPMNOrder $order
	 * @return Response
	 */
	public function bulkCreate($order)
	{
		try {
			$response = $this->client->request('POST', 'order', [
				'body' => $order->CGPData()

			]);
			return $response;
		} catch (Exception $e) {
			$this->logger->debug(
				'create order failed because ' . $e->getMessage(),
				[
					'payload' => $order->CGPData(),
					'response' => $response->getContent(false),
					'exception' => $e
				]
			);
			throw $e;
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param int $order
	 * @return void
	 */
	public function get(int $orderId)
	{
		try {
			$response = $this->client->request('GET', 'order/' . $orderId);
			return $response->toArray(false);
		} catch (Exception $e) {
			$this->logger->debug(
				'create order failed because ' . $e->getMessage(),
				[
					'orderID' => $orderId,
					'exception' => $e
				]
			);
			throw $e;
		}
	}

	/**
	 *  concurrent get orders
	 *
	 * @param int $order
	 * @return array
	 */
	public function bulkGet(array $orderIds)
	{
		try {
			$result = [];
			foreach ($orderIds as $orderId => $qpmnOrderId) {
				$result[$orderId] = $this->client->request('GET', 'order/' . $qpmnOrderId);
			}
			return $result;
		} catch (TransportExceptionInterface $e) {
			$this->logger->debug(
				$e->getMessage(),
				['exception' => $e]
			);
		} catch (Exception $e) {
			$this->logger->debug(
				'Bulk get orders failed because ' . $e->getMessage(),
				[
					'OrderId' => $orderId,
					'exception' => $e
				]
			);
		}
	}
}
