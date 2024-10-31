<?php

namespace QPMN\Partner\Admin\Obj;

use QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\QP_WP_Option_Account;
use QPMN\Partner\WC\QP_WP_Option_Config;

class Qpmn_Admin
{

    public static function get_obj()
    {
        $secretVerified = get_option(QP_WP_Option_Account::SECRET_VERIFIED);
        $partnerVerified = get_option(QP_WP_Option_Account::PARTNER_VERIFIED);
        $authUrl = '';
        if (!$partnerVerified) {
            //get partner login url
            $clientId = get_option(QP_WP_Option_Account::CLIENT_ID);
            $verifier = QP_WP_Option_Account::getCodeVerifier();
            $authCode = new AuthCodePKCE($clientId, $verifier);
            $authUrl =  $authCode->authURL();
        }
        return array(
            'page_url' => admin_url('admin.php?page=qpmn_options'),
            'ajax_url'     => rest_url(Qpmn_Install::PLUGIN_API_NAMESPACE),
            'nonce'     => wp_create_nonce('wp_rest'),
            'loggedin'     => get_option(Qpmn_Install::ACCOUNT_VERIFIED),
            'secret_loggedin'     => (bool)$secretVerified,
            'partner_loggedin'     => (bool)$partnerVerified,
            'partner_login_url' => $authUrl,
            'partner_name' => get_option(Qpmn_Install::QPMN_PARTNER_NAME),
            'builder_domain' => Qpmn_Install::QPMN_PARTNER_BUILDER_ENDPOINT,
            'config' => [
                'nextSchedule' => (new \QPMN\Partner\WC\Schedule\Order())->nextScheduleTime(),
                'scheduleOptions' => QP_WP_Option_Config::getScheduleOptions(),
                'config' => QP_WP_Option_Config::getConfigs()
            ]
        );
    }
}
