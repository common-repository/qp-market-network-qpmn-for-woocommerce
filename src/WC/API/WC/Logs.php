<?php

namespace QPMN\Partner\WC\API\WC;

use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\API;
use WP_REST_Response;
use WP_REST_Server;
use wpdb;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Logs implements API
{
    protected $namespace;
    protected $resource;

    public function __construct()
    {
        $this->namespace = Qpmn_Install::PLUGIN_API_NAMESPACE;
        $this->resource = 'wc/log';
    }

    public function register_routes()
    {
        $resource = $this->resource;
        register_rest_route($this->namespace, "/$resource", array(
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
     * get account info
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function get($data)
    {
        $days = $data['days'];
        if (!is_numeric($days)) {
            return new WP_REST_Response([], 400);
        }

        /**
         * @var wpdb $wpdb
         */
        global $wpdb;
        $tableName = $wpdb->prefix . Qpmn_Install::TABLE_NAME_LOG;

        $query = "SELECT log FROM ". sanitize_text_field( $tableName )." WHERE created_at BETWEEN (now() - INTERVAL %d DAY) AND now() ORDER BY created_at DESC";
        $result = $wpdb->get_results($wpdb->prepare($query, $days));

        return new WP_REST_Response($result, 200);
    }

}
