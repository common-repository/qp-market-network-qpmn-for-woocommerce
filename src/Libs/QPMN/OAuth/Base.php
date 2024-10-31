<?php

namespace QPMN\Partner\Libs\QPMN\OAuth;

use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\ScopingHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

if (!defined('ABSPATH')) {
	exit;
}

abstract class Base
{

	static public function getNonce($nonceAction)
	{
		return wp_create_nonce($nonceAction);
	}

	static public function verifyNonce($nonce, $nonceAction)
	{
		return wp_verify_nonce($nonce, $nonceAction);
	}

	abstract function authorize(...$args);

	abstract function token(...$args);
}
