<?php

namespace QPMN\Partner\Libs\QPMN\OAuth\API\Partner;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\HttpClient\Obj\QPMNOrder;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

if (!defined('ABSPATH')) {
	exit;
}

class Product 
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
		$client = ScopingHttpClient::forBaseUri($client, $this->url . '/wp-json/qpmn-api/v1/');

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
	public function getAll()
	{
		try {
			$response = $this->client->request('GET', 'product');
			return $response->toArray(false);
		} catch (Exception $e) {
            $this->logger->debug(
                'get product list failed because ' . $e->getMessage(),
                [
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
	public function get(int $productId)
	{
		try {
			$response = $this->client->request('GET', 'product/'.$productId);
			return $response->toArray(false);
		} catch (Exception $e) {
            $this->logger->debug(
                'get product info failed because ' . $e->getMessage(),
                [
                    'productId' => $productId,
                    'exception' => $e
                ]
            );
			throw $e;
		}
	}

}
