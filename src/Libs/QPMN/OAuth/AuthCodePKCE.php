<?php

namespace QPMN\Partner\Libs\QPMN\OAuth;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Qpmn_Install;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

if (!defined('ABSPATH')) {
	exit;
}

class AuthCodePKCE extends Base
{
	private $url = QPMN_PARTNER_API_ENDPOINT;
	private $clientId;
	private $codeVerifier;
	/**
	 *
	 * @var HttpClientInterface $client
	 */
	private $client;

	private $redirectUrl = "admin.php?page=qpmn_auth";

	const NONCE_ACTION = 'QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE';

	public function __construct($key, $verifier)
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

		$this->codeVerifier = $verifier;
	}

	/**
	 *
	 * @param n/a
	 * @return void
	 */
	public function authorize(...$args)
	{
		/**
		 * @var Logger $logger
		 */
		$logger = (PluginLogger::instance())->getLogger();
		try {
			$codeChallenge = $this->generateCodeChallenge();
			$response = $this->client->request('GET', 'authorize', [
				'query' => [
					'client_id' => $this->clientId,
					'response_type' => 'code',
					'redirect_uri' => urlencode($this->redirectUrl),
					'state' => parent::getNonce(self::NONCE_ACTION),
					'code_challenge' => $codeChallenge,
					'code_challenge_method' => 'S256'
				]
			]);
			return $response->getInfo();
		} catch (Exception $e) {
            $logger->error('qpmn authorize failed because ' . $e->getMessage());
			throw $e;
		}
	}

	/**
	 *
	 * @return string 
	 */
	public function authURL()
	{
		try {

			$codeChallenge = $this->generateCodeChallenge();
			$queryString = build_query([
					'client_id' => $this->clientId,
					'response_type' => 'code',
					'redirect_uri' => urlencode($this->redirectUrl),
					'state' => parent::getNonce(self::NONCE_ACTION),
					'code_challenge' => $codeChallenge,
					'code_challenge_method' => 'S256'
			]);
			$url = $this->url .'/oauth/authorize?' . $queryString;

			return $url;
		} catch (Exception $e) {
			throw $e;
		}
	}

	/**
	 *
	 * @param String $code
	 * @return array
	 */
	public function token(...$args)
	{
		try {
			$response = $this->client->request('POST', 'token', [
				'body' => [
					'grant_type' => 'authorization_code',
					'code' => $args[0],
					'code_verifier' => $this->codeVerifier,
					'redirect_uri' => urlencode($this->redirectUrl),
					'client_id' => $this->clientId,
				]
			]);
			$result = $response->toArray(false);
			return $result;
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function refreshToken(...$args)
	{
		/**
		 * @var Logger $logger
		 */
		$logger = (PluginLogger::instance())->getLogger();
		try {
			$response = $this->client->request('POST', 'token', [
				'body' => [
					'grant_type' => 'refresh_token',
					'refresh_token' => $args[0],
					'client_id' => $this->clientId,
				]
			]);
			$result = $response->toArray(false);
			$logger->info('refresh token successful');
			return $result;
		} catch (Exception $e) {
            $logger->error('refresh qpmn token failed because ' . $e->getMessage());
			throw $e;
		}
	}

	/**
	 * This is a cryptographically random string using the characters A-Z, a-z, 0-9, 
	 * and the punctuation characters -._~ (hyphen, period, underscore, and tilde), 
	 * between 43 and 128 characters long
	 *
	 * @return string
	 */
	public static function generateVerifier()
	{
		return str_replace(array("+","-","/","="), array("","","",""), base64_encode(bin2hex(random_bytes(32))));
	}

	/**
	 * Once the app has generated the code verifier, it uses that to create the code challenge. 
	 * For devices that can perform a SHA256 hash, 
	 * the code challenge is a BASE64-URL-encoded string of the SHA256 hash of the code verifier.
	 *
	 * @return string
	 */
	public function generateCodeChallenge()
	{
		$challengeBytes = hash('sha256', $this->codeVerifier, true);
		return rtrim(strtr(base64_encode($challengeBytes), "+/", "-_"), "=");
	}
}
