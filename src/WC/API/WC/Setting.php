<?php

namespace QPMN\Partner\WC\API\WC;

use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\API;
use QPMN\Partner\WC\QP_WP_Option_Config;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Setting implements API
{
    protected $namespace;
    protected $resource;

    public function __construct()
    {
        $this->namespace = Qpmn_Install::PLUGIN_API_NAMESPACE;
        $this->resource = 'wc/setting';
    }

    public function register_routes()
    {
        $resource = $this->resource;
        register_rest_route($this->namespace, "/$resource", array(
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'updateSettings'),
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
     * create 
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function updateSettings($data)
    {
        try {

            $scheudle = $data['schedule'];
            $debug = $data['debug'];

            $schedule = new \QPMN\Partner\WC\Schedule\Order();

            //update wc options
            QP_WP_Option_Config::updateSchedule($scheudle);
            QP_WP_Option_Config::updateDebugMode($debug);
            //update order schedule by re-activate schedule
            $schedule->deactivate();
            $schedule->activate();

            //get user name
            $response = QP_WP_Option_Config::getConfigs();
            $response['nextSchedule'] = $schedule->nextScheduleTime();
            return new WP_REST_Response($response, 200);
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
