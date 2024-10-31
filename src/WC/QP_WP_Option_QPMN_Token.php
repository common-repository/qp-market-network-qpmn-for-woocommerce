<?php

namespace QPMN\Partner\WC;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE;
use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\QPMN\Account;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QP_WP_Option_QPMN_Token extends QP_WP_Option
{
    const TOKEN = Qpmn_Install::QPMN_OAUTH_ACCESS_TOKEN;
    const REMAINING_SECONDS = Qpmn_Install::ACCOUNT_TOKEN_REMAINING_SECONDS;
    const REFRESH_TOKEN = Qpmn_Install::QPMN_OAUTH_REFRESH_TOKEN;

    const USER_VERIFIED = Qpmn_Install::ACCOUNT_VERIFIED;
    const CLIENT_ID = Qpmn_Install::CLIENT_ID;
    const CODE_VERIFIER = Qpmn_Install::CODE_VERIFIER;
    const PARTNER_ID = Qpmn_Install::ACCOUNT_PARTNER_ID;
    const OPTION_TOKEN_VERIFICATION_FAILED = 'qpmn_token_verification_failed';

    const PARTNER_VERIFIED = Qpmn_Install::PARTNER_VERIFIED;


    public function init_hooks()
    {
        add_action('admin_notices', array($this, 'token_verification_failed'));
    }

    public static function saveAndUpdate($value, $name = null, $autoload = false)
    {
        //not need to implement
    }

    /**
     * get or refresh token
     *
     * @param [type] $name
     * @return string
     */
    public static function get($name = null)
    {
        /**
         * @var Logger $logger
         */
        $logger = (PluginLogger::instance())->getLogger();
        if (self::isExpired()) {
            try {
                $partnerVerified = get_option(self::PARTNER_VERIFIED);
                $refreshToken = get_option(self::REFRESH_TOKEN);
                if (!$partnerVerified) {
                    throw new Exception('QPMN connection issue found.');
                }
                if (empty($refreshToken)) {
                    //refresh token not found going to reset account info
                    QP_WP_Option_Account::resetQPMNAccount();
                    throw new Exception('refresh token not found.');
                }
                $userLoginInfo = QP_WP_Option_Account::getLoginInfo();
                $clientId = $userLoginInfo[Account::CLIENT_ID];
                $verifier = QP_WP_Option_Account::getCodeVerifier();

                $authCodePKCE = new AuthCodePKCE($clientId, $verifier);
                $tokens = $authCodePKCE->refreshToken($refreshToken);

                if (is_array($tokens) && isset($tokens['error_description'])) {
                    //oauth error 
                    throw new Exception ($tokens['error_description']);
                }
                //save tokens 
                QP_WP_Option_QPMN_Token::setTokens($tokens);
            } catch (Exception $e) {
                $logger->debug('QPMN refresh token failed (and going to reset saved account info) because '. $e->getMessage());
                //display notice
                update_option(self::OPTION_TOKEN_VERIFICATION_FAILED, true);
            }
        }
        return get_transient(self::TOKEN);
    }

    public static function delete()
    {
        delete_transient(self::TOKEN);
    }

    /**
     * consider token expired if 
     *  - token expired timestamp not found
     *  - or expire less than 1 hour
     *
     * @return boolean
     */
    public static function isExpired($name = null)
    {
        $token = get_transient(self::TOKEN);

        //token not exists
        return empty($token);
    }

    public static function deleteAll()
    {
        delete_transient(self::TOKEN);
        delete_option(self::OPTION_TOKEN_VERIFICATION_FAILED);
        delete_option(self::REFRESH_TOKEN);
    }

    public static function init()
    {
        update_option(self::OPTION_TOKEN_VERIFICATION_FAILED, false);
    }

    public static function setTokens(array $tokens)
    {
		$token = $tokens['access_token'];
		$refreshToken = $tokens['refresh_token'];
		$expiredIn = $tokens['expires_in'] ?? 0;
        //save refresh token every time
		QP_WP_Option_Account::saveAndUpdate($refreshToken, self::REFRESH_TOKEN);
		QP_WP_Option_Account::saveAndUpdate(true, self::PARTNER_VERIFIED);

		$tokenExpiredIn = $expiredIn - self::REMAINING_SECONDS;

		if ($tokenExpiredIn <= 0) {
			//use user default expired in
			$tokenExpiredIn = $expiredIn;
		}

		set_transient(self::TOKEN, $token, $tokenExpiredIn);
		//reset notice flag 
		update_option(QP_WP_Option_QPMN_Token::OPTION_TOKEN_VERIFICATION_FAILED, false);
    }

    /**
     * display token verification failed error message in admin page
     *
     * @return void
     */
    public static function token_verification_failed()
    {
        if (get_option(self::OPTION_TOKEN_VERIFICATION_FAILED)) {
            $url = admin_url("admin.php?page=qpmn_options&step=1");
            $class = 'notice notice-error';
            $message = sprintf(Qpmn_i18n::__('QP market nework account verification failed. Please go to <a href="%1$s">setup page</a> to login.'), esc_url($url));
    
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);

        }
    }

}
