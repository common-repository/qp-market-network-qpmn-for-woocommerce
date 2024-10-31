<?php

namespace QPMN\Partner\WC;

use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\QPMN\Account;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QP_WP_Option_Account extends QP_WP_Option
{
    /**
     * tech debt 
     * reuse cgp login flow to serve oauth+pkce
     * 
     * login name = client id
     * CODE_VERIFIER = code verifier  
     * 
     * 
     * auto login (aka. regenerate code verifier) when 
     *  - activate plugin
     *  - logout 
     */
    const CLIENT_ID = Qpmn_Install::CLIENT_ID;
    const CODE_VERIFIER = Qpmn_Install::CODE_VERIFIER;
    const PARTNER_ID = Qpmn_Install::ACCOUNT_PARTNER_ID;
    const VERIFIED = Qpmn_Install::ACCOUNT_VERIFIED;
    const SECRET_VERIFIED = Qpmn_Install::SECRET_VERIFIED;
    const PARTNER_VERIFIED = Qpmn_Install::PARTNER_VERIFIED;
    const REFRESH_TOKEN  = Qpmn_Install::QPMN_OAUTH_REFRESH_TOKEN;
    const PARTNER_NAME  = Qpmn_Install::QPMN_PARTNER_NAME;

    const ALLOWED_OPTIONS = [
        self::CLIENT_ID, self::CODE_VERIFIER,
        self::PARTNER_ID, self::VERIFIED,
        self::SECRET_VERIFIED,
        self::PARTNER_VERIFIED,
        self::REFRESH_TOKEN,
        self::PARTNER_NAME,
    ];

    public function init_hooks()
    {
    }

    /**
     * return allowed option name
     *
     * @return array 
     */
    public function getAllowedOptions()
    {
        return self::ALLOWED_OPTIONS;
    }

    /**
     *  limitation: allowed option name for accounts 
     *
     * @param string $value
     * @param string $name
     * @param boolean $autoload
     * @return void
     */
    public static function saveAndUpdate($value, $name = null, $autoload = false)
    {
        if (in_array($name, self::ALLOWED_OPTIONS)) {
            update_option($name, $value, $autoload);
        } else {
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->debug('update account option failed becaue disallowed option provided.');

            throw new \Exception('invalid name');
        }
    }

    public static function get($name = null)
    {
        if (in_array($name, self::ALLOWED_OPTIONS)) {
            return get_option($name);
        }
        return null;
    }

    /**
     * return account info
     *
     * @return array 
     */
    public static function getLoginInfo()
    {
        $loginInfo = [];
        //make sure account setup correct
        self::checkAccountSetup();

        $loginInfo[self::CLIENT_ID]    = get_option(self::CLIENT_ID);
        $loginInfo[self::CODE_VERIFIER]      = get_option(self::CODE_VERIFIER);
        $loginInfo[self::PARTNER_ID]    = get_option(self::PARTNER_ID);
        $loginInfo[self::VERIFIED]      = get_option(self::VERIFIED);
        $loginInfo[self::SECRET_VERIFIED]   = (bool)get_option(self::SECRET_VERIFIED);
        $loginInfo[self::PARTNER_VERIFIED]  = (bool)get_option(self::PARTNER_VERIFIED);
        $loginInfo[self::REFRESH_TOKEN]  = get_option(self::REFRESH_TOKEN);
        $loginInfo[self::PARTNER_NAME]  = get_option(self::PARTNER_NAME);

        return $loginInfo;
    }

    public static function delete($name = null)
    {
        if (in_array($name, self::ALLOWED_OPTIONS)) {
            delete_option($name);
        } else {
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->debug('delete account option failed becaue disallowed option provided.');
            throw new \Exception('invalid name');
        }
    }

    public static function deleteAll()
    {
        delete_option(self::CLIENT_ID);
        delete_option(self::CODE_VERIFIER);
        delete_option(self::PARTNER_ID);
        delete_option(self::VERIFIED);
        delete_option(self::SECRET_VERIFIED);
        delete_option(self::PARTNER_VERIFIED);
        delete_option(self::REFRESH_TOKEN);
        delete_option(self::PARTNER_NAME);
    }

    public static function init()
    {
        add_option(self::CLIENT_ID, null);
        add_option(self::CODE_VERIFIER, null);
        add_option(self::PARTNER_ID, null);
        add_option(self::VERIFIED, false);
        add_option(self::SECRET_VERIFIED, false);
        add_option(self::PARTNER_VERIFIED, false);
        add_option(self::REFRESH_TOKEN, null);
        add_option(self::PARTNER_NAME, null);
    }

    public static function isVerified()
    {
        $verified = get_option(self::VERIFIED);

        return (empty($verified)) ? false : true;
    }

    public static function isCheckable()
    {
        $loginName = get_option(self::CLIENT_ID);
        $CODE_VERIFIER = get_option(self::CODE_VERIFIER);
        $partnerId = get_option(self::PARTNER_ID);

        return (empty($loginName) || empty($CODE_VERIFIER) || empty($partnerId)) ? false : true;
    }

    public static function setVerified()
    {
        self::saveAndUpdate(true, self::VERIFIED);
    }

    public static function resetQPMNAccount()
    {
        update_option(self::CLIENT_ID, null);
        update_option(self::CODE_VERIFIER, null);
        update_option(self::PARTNER_ID, null);
        update_option(self::VERIFIED, null);
        update_option(self::SECRET_VERIFIED, false);
        update_option(self::PARTNER_VERIFIED, false);
        update_option(self::REFRESH_TOKEN, false);
        update_option(self::PARTNER_NAME, false);
        QP_WP_Option_QPMN_Token::deleteAll();

        return array_merge([], self::autoLogin());
    }

    /**
     * naming conversion 
     *  email = client id
     *  CODE_VERIFIER = oauth code verifier
     */
    public static function autoLogin()
    {
        $account = new Account();
        $result = $account->login([
            'CLIENT_ID' => Qpmn_Install::QPMN_PARTNER_API_CLIENT_ID,
            'CODE_VERIFIER' => AuthCodePKCE::generateVerifier(),
            'partnerId' => ''
        ]);

        return $result->data;
    }

    public static function getCodeVerifier()
    {
        $userLoginInfo = QP_WP_Option_Account::getLoginInfo();
        $userCodeVerifier = $userLoginInfo[self::CODE_VERIFIER];
        return $userCodeVerifier;
    }

    //logic response to make sure account setup
    public static function checkAccountSetup()
    {
        $storedClientId = get_option(self::CLIENT_ID);
        if (empty($storedClientId) ||  $storedClientId!= Qpmn_Install::QPMN_PARTNER_API_CLIENT_ID) {
            //reset saved account info and auto login again
            self::resetQPMNAccount();
        }
    }
}
