<?php

namespace QPMN\Partner\WC\API\QPMN;

use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\API;
use QPMN\Partner\WC\QP_WP_Option_Account;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Account implements API
{
    protected $namespace;
    protected $resource;

    const TOKEN = Qpmn_Install::ACCOUNT_TOKEN;
    const REMAINING_SECONDS = Qpmn_Install::ACCOUNT_TOKEN_REMAINING_SECONDS;

    const USER_VERIFIED = Qpmn_Install::ACCOUNT_VERIFIED;
    const CLIENT_ID = Qpmn_Install::CLIENT_ID;
    const CODE_VERIFIER = Qpmn_Install::CODE_VERIFIER;
    const PARTNER_ID = Qpmn_Install::ACCOUNT_PARTNER_ID;
    const SECRET_VERIFIED = Qpmn_Install::SECRET_VERIFIED;
    const PARTNER_VERIFIED = Qpmn_Install::PARTNER_VERIFIED;

    public function __construct()
    {
        $this->namespace = Qpmn_Install::PLUGIN_API_NAMESPACE;
        $this->resource = 'qpmn/account';
    }

    public function register_routes()
    {
        $resource = $this->resource;
        // register_rest_route($this->namespace, "/$resource/login", array(
        //     array(
        //         'methods' => WP_REST_Server::CREATABLE,
        //         'callback' => array($this, 'login'),
        //         'permission_callback' => function (\WP_REST_Request $request) {
        //             return current_user_can('manage_options');
        //         }
        //     )
        // ));
        register_rest_route($this->namespace, "/$resource/logout", array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'logout'),
                'permission_callback' => function (\WP_REST_Request $request) {
                    return current_user_can('manage_options');
                }
            )
        ));
    }

    /**
     * get account info
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function get($data)
    {
        return new WP_REST_Response(['data' => []], 200);
    }

    /**
     * login via api key
     * 
     * clone from cgp api for store api key and secrets
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function login($data)
    {
        try {
            $clientID = $data['CLIENT_ID'];
            $codeVerifier = $data['CODE_VERIFIER'];
            $userPartnerId = $data['partnerId'];
            //test api key
            $auth = new AuthCodePKCE($clientID, $codeVerifier);
            $loginURL = $auth->authURL();
            //update expired at 8hrs - remaining seconds
            $tokenExpiredIn = 28800 - self::REMAINING_SECONDS;

            set_transient(self::TOKEN, 'mocking token', $tokenExpiredIn);

            //store user info
            QP_WP_Option_Account::saveAndUpdate($clientID, self::CLIENT_ID);
            //store encrypted pw
            QP_WP_Option_Account::saveAndUpdate($codeVerifier, self::CODE_VERIFIER);
            QP_WP_Option_Account::saveAndUpdate($userPartnerId, self::PARTNER_ID);
            //verified
            QP_WP_Option_Account::saveAndUpdate(true, self::USER_VERIFIED);
            QP_WP_Option_Account::saveAndUpdate(true, self::SECRET_VERIFIED);

            //get user name
            $userLoginInfo = QP_WP_Option_Account::getLoginInfo();
            $response = [
                'client_id' => $userLoginInfo[self::CLIENT_ID],
                'verified' => (bool)$userLoginInfo[self::USER_VERIFIED],
                'secret_verified' => (bool)$userLoginInfo[self::SECRET_VERIFIED],
                'partner_verified' => (bool)$userLoginInfo[self::PARTNER_VERIFIED],
                'loginURL' => $loginURL
            ];
            return new WP_REST_Response($response, 200);
        } catch (\Exception $e) {
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->error('Login Failed because '. $e->getMessage());
            return new WP_REST_Response([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * logout 
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function logout($data)
    {
        $autoLoginData = QP_WP_Option_Account::resetQPMNAccount();
        $data = QP_WP_Option_Account::getLoginInfo();
        return new WP_REST_Response([
            'client_id' => $data[self::CLIENT_ID],
            'verified' => $data[self::USER_VERIFIED],
            'secret_verified' => $data[self::SECRET_VERIFIED],
            'partner_verified' => $data[self::PARTNER_VERIFIED],
            'partner_login_url' => $autoLoginData['loginURL']
        ], 200);
    }
}
