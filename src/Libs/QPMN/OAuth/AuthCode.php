<?php

namespace QPMN\Partner\Libs\QPMN\OAuth;

use Exception;
use QPMN\Partner\Qpmn_Install;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

if (!defined('ABSPATH')) {
	exit;
}

class AuthCode extends Base
{
	private $url = QPMN_PARTNER_API_ENDPOINT;
	private $clientId;
	private $clientSecret;
	/**
	 *
	 * @var HttpClientInterface $client
	 */
	private $client;

	private $redirectUrl = "admin.php?page=qpmn_auth";

	const NONCE_ACTION = 'QPMN\Partner\Libs\QPMN\OAuth\AuthCode';

	public function __construct($key, $secret)
	{
		$this->redirectUrl = get_admin_url() . $this->redirectUrl;
		//retry 3 times
		$client = HttpClient::create();
		$client = ScopingHttpClient::forBaseUri($client, $this->url . '/oauth/');

		if (empty($key)) {
			//fix potential multi-site issue
			$key = Qpmn_Install::QPMN_PARTNER_API_CLIENT_ID;
		}

		$this->client = $client;
		$this->clientId = $key;
		$this->clientSecret = $secret;
	}

	/**
	 * Undocumented function
	 *
	 * @param n/a
	 * @return void
	 */
	public function authorize(...$args)
	{
		try {

			$response = $this->client->request('GET', 'authorize', [
				'query' => [
					'client_id' => $this->clientId,
					'response_type' => 'code',
					'redirect_uri' => urlencode($this->redirectUrl),
					'state' => parent::getNonce(self::NONCE_ACTION)
				]
			]);
			return $response->getInfo();
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 * Undocumented function
	 *
	 * @param String $code
	 * @return array
	 */
	public function token(...$args)
	{
		try {
			$response = $this->client->request('POST', 'token', [
				'auth_basic' => [$this->clientId, $this->clientSecret],
				'body' => [
					'grant_type' => 'authorization_code',
					'code' => func_get_arg(0),
					'redirect_uri' => urlencode($this->redirectUrl),
					'client_id' => $this->clientId,
					'client_secret' => $this->clientSecret
				]
			]);
			$result = $response->toArray();
			return $result;
		} catch (Exception $e) {
			throw $e;
		}
	}
}
