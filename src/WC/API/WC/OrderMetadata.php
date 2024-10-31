<?php

namespace QPMN\Partner\WC\API\WC;

use Exception;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\API;
use WP_REST_Response;
use WP_REST_Server;

class OrderMetadata implements API{
    protected $namespace;
    protected $resource;

    public function __construct()
    {
        $this->namespace = Qpmn_Install::PLUGIN_API_NAMESPACE;
        $this->resource = 'wc/ordermeta';
    }

    public function register_routes()
    {
        $resource = $this->resource;
        register_rest_route($this->namespace, "/$resource", array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update'),
                'permission_callback' => function (\WP_REST_Request $request) {
                    $user = get_userdata( get_current_user_id() );
                    return current_user_can('manage_options');
                }
            )
        ));
    }

    /**
     * $req WP_REST_Request
     */
    public function update($req)
    {
        try {
            $response = ['code' => 200, 'message' => 'successful', 'data' => []];
            $orderId = sanitize_text_field($req['orderId'] ?? '');
            $itemId = sanitize_text_field($req['itemId'] ?? '');
            $designId = sanitize_text_field($req['designId'] ?? '');
            $designConfig = sanitize_text_field($req['designConfig'] ?? '');
            $thumbnail = sanitize_text_field($req['thumbnail'] ?? '');

            if (empty($orderId) || empty($itemId) || empty($designId) || empty($designConfig) || empty($thumbnail)) {
                $response['code'] = 400;
                $response['message'] = 'invalid request data';
                return new WP_REST_Response($response, 200);
            }

            $order = wc_get_order($orderId);
            $qpmnOrderId = $order->get_meta(Qpmn_Install::META_QPMN_ORDER_ID); 

            if (!empty($qpmnOrderId)) {
                $response['code'] = 400;
                $response['message'] = 'sorry, You can not update design after order synced to QPMN.';
                return new WP_REST_Response($response, 200);
            }

            foreach($order->get_items() as $item) {
                if ($item->get_id() == $itemId) {
                    $item->update_meta_data(Qpmn_Install::META_QPMN_DESIGN_ID, $designId);
                    $item->update_meta_data(Qpmn_Install::META_QPMN_DESIGN_CONFIG, $designConfig);
                    $item->update_meta_data(Qpmn_Install::META_QPMN_DESIGN_THUMBNAIL, $thumbnail);
                    $item->save(); 

                    break;
                }
            }
            // $post = get_post();
            // $response = $post;
            return new WP_REST_Response($response, 200);
        } catch (Exception $e) {
            $response = ['code' => 500, 'message' => $e->getMessage(), 'data' => []];
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->error('Login Failed because '. $e->getMessage());
            return new WP_REST_Response($response, 200);
        }
    }
}