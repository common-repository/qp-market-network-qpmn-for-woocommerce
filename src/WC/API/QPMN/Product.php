<?php

namespace QPMN\Partner\WC\API\QPMN;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Libs\QPMN\OAuth\API\Partner\Product as PartnerProduct;
use QPMN\Partner\Libs\QPMN\OAuth\AuthCodePKCE;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\API;
use QPMN\Partner\WC\QP_WP_Option_Account;
use QPMN\Partner\WC\QP_WP_Option_QPMN_Token;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Product implements API
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
        $this->resource = 'qpmn/product';
    }

    public function register_routes()
    {
        $resource = $this->resource;
        register_rest_route($this->namespace, "/$resource", array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'getAll'),
                'permission_callback' => function (\WP_REST_Request $request) {
                    return current_user_can('manage_options');
                }
            )
        ));
        register_rest_route($this->namespace, "/$resource/(?P<id>\d+)", array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get'),
                'permission_callback' => function (\WP_REST_Request $request) {
                    return current_user_can('manage_options');
                }
            )
        ));
    }

    /**
     * get product info
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function get($data)
    {
        // return new WP_REST_Response(['data' => []], 200);
        try {
            $productId = intval($data['id']);
            $token = QP_WP_Option_QPMN_Token::get();

            if (!$productId) {
                throw new Exception('Invalid product id provided.');
            }

            if (empty($token)) {
                throw new Exception('Connection issue found. please check your QPMN connection.');
            }

            $partnerProduct = new PartnerProduct($token);
            $products = $partnerProduct->get($productId);

            if (is_array($products) && isset($products['code'])) {
                $response = [
                    'data' => $products['data'],
                    'message' => $products['message']
                ];
                $response = new WP_REST_Response($response, $products['code']);
                $response->set_headers(array('Cache-Control' => 'max-age=3600'));
            } else {
                $response = new WP_REST_Response([
                    'message' => 'QPMN api issue found, could not fetch products.'
                ], 500);
            } 
            return $response;
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
     *
     * get product list
     * 
     * @param array $data
     * @return WP_REST_Response
     */
    public function getAll()
    {
        try {
            $token = QP_WP_Option_QPMN_Token::get();
            if (empty($token)) {
                throw new Exception('Connection issue found. please check your QPMN connection.');
            }
            $partnerProduct = new PartnerProduct($token);
            $products = $partnerProduct->getAll();

            if (is_array($products) && isset($products['code'])) {
                $response = [
                    'data' => $products['data'],
                    'message' => $products['message']
                ];
                $response = new WP_REST_Response($response, $products['code']);
                $response->set_headers(array('Cache-Control' => 'max-age=3600'));
            } else {
                $response = new WP_REST_Response([
                    'message' => 'QPMN api issue found, could not fetch products.'
                ], 500);
            } 
            return $response;
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
}
