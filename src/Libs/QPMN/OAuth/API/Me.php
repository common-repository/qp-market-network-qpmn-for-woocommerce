<?php

namespace QPMN\Partner\Libs\QPMN\OAuth\API;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

if (!defined('ABSPATH')) {
	exit;
}

class Me 
{
	private $url = QPMN_PARTNER_API_ENDPOINT;
	/**
	 *
	 * @var HttpClientInterface $client
	 */
	private $client;
	private $token;

	public function __construct($token)
	{
		//retry 3 times
		$client = HttpClient::create();
		$client = ScopingHttpClient::forBaseUri($client, $this->url . '/oauth/');

		$this->client = $client;
		$this->token = $token;
	}

	/**
	 * Undocumented function
	 *
	 * @param n/a
	 * @return void
	 */
	public function me()
	{
		try {
			$response = $this->client->request('GET', 'me', [
				'query' => [
					'access_token' => $this->token,
				]
			]);
			if ($response->getContent() === '') {
				return [];
			} else {
				return $response->toArray();
			}
		} catch (Exception $e) {
			throw $e;
		}
	}
}
