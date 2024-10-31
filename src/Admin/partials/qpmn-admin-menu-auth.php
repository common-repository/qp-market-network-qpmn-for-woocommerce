<?php

/**
 * hidden submenu to handle oauth callback
 * - use submenu capability to handle security (admin only) 
 * 
 */

use QPMN\Partner\Libs\QPMN\OAuth\API\Me;
use QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE;
use QPMN\Partner\WC\API\QPMN\Account;
use QPMN\Partner\WC\QP_WP_Option_Account;
use QPMN\Partner\WC\QP_WP_Option_QPMN_Token;

try {

	if (isset($_GET['code'])) {

		$code = sanitize_text_field($_GET['code']);
		$state = sanitize_text_field($_GET['state']);
		//verify $state
		print_r("- verifying state code<br>");
		$isValidedRequeset = AuthCodePKCE::verifyNonce($state, AuthCodePKCE::NONCE_ACTION);
		if ($isValidedRequeset) {
			print_r("- state code verified<br>");

			$userLoginInfo = QP_WP_Option_Account::getLoginInfo();
			print_r("- lookup auth information<br>");

			$clientId = $userLoginInfo[Account::CLIENT_ID];
			$verifier = QP_WP_Option_Account::getCodeVerifier();
			print_r("- preparing oauth code verifier<br>");

			$authCodePKCE = new AuthCodePKCE($clientId, $verifier);
			print_r("- request auth token<br>");

			//store access token as transient option
			//store access token scope and type
			//store refresh token as transient option
			$tokens = $authCodePKCE->token($code);
			if (isset($tokens['error'])) {
				print_r("- request auth token failed<br>");
				throw new Exception($tokens['error_description'], 403);
			}
			print_r("- request auth token successful<br>");
			try {
				print_r("- request login info<br>");
				//get me
				$meObj = new Me($tokens['access_token']);
				$me = $meObj->me();
				if (is_array($me) && isset($me['display_name'])) {
					QP_WP_Option_Account::saveAndUpdate($me['display_name'], QP_WP_Option_Account::PARTNER_NAME);
				}
			} catch (Exception $e) {
				print_r("- skipped - could not find login info<br>");
				//no display name
			}
			//save tokens 
			QP_WP_Option_QPMN_Token::setTokens($tokens);
			print_r('- store token<br>');
			print_r('- redirecting to setup page<br>');

			wp_redirect('admin.php?page=qpmn_options');
		} else {
			print_r("- state code verification failed<br>");
			//request not init from us 
		}
	}
} catch (Exception $e) {
	print_r($e->getMessage() ."<br>");
}
