<?php

namespace QPMN\Partner\WC;

use Exception;
use Monolog\Logger;
use QPMN\Partner\Libs\HttpClient\Obj\OrderItem;
use QPMN\Partner\Libs\HttpClient\Obj\QPMNOrder;
use QPMN\Partner\Libs\HttpClient\Obj\QPMNOrderItem;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Libs\Monolog\QPApiLogger;
use QPMN\Partner\Libs\QPMN\OAuth\API\Partner\Order as PartnerOrder;
use QPMN\Partner\Pub\Qpmn;
use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;
use Symfony\Contracts\HttpClient\ResponseInterface;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QPMN_WC_Order extends QP_WC
{
    const CLIENT_ID = Qpmn_Install::CLIENT_ID;
    const CODE_VERIFIER = Qpmn_Install::CODE_VERIFIER;
    const META_IS_QPMN_ORDER = Qpmn_Install::META_IS_QPMN_ORDER;
    const META_IS_QPPP_PRODUCT = Qpmn_Install::META_IS_QPPP_PRODUCT;
    const META_QPMN_PRODUCT_ID = Qpmn_Install::META_QPMN_PRODUCT_ID;
    const META_QPP_DESIGN_ID = Qpmn_Install::META_QPMN_DESIGN_ID;
    const META_QPP_DESIGN_CONFIG = Qpmn_Install::META_QPMN_DESIGN_CONFIG;
    const META_QPP_DESIGN_THUMBNAIL = Qpmn_Install::META_QPMN_DESIGN_THUMBNAIL;

    const META_QPMN_BUILDER_DESIGN_PREVIEW_URL = Qpmn_Install::META_QPMN_BUILDER_DESIGN_PREVIEW_URL;

    const META_QPMN_ORDER_LAST_SYNC_AT  = Qpmn_Install::META_QPMN_ORDER_LAST_SYNC_AT;
    const META_QPMN_ORDER_ID            = Qpmn_Install::META_QPMN_ORDER_ID;
    const META_QPMN_ORDER_NUMBER        = Qpmn_Install::META_QPMN_ORDER_NUMBER;
    const META_QPMN_ORDER_STATUS        = Qpmn_Install::META_QPMN_ORDER_STATUS;
    const META_QPMN_ORDER_DATETIME      = Qpmn_Install::META_QPMN_ORDER_DATETIME;
    const META_QPMN_ORDER_ERROR_MSG     = Qpmn_Install::META_QPMN_ORDER_ERROR_MSG;

    const META_QPMN_ORDER_SUBTOTAL      = Qpmn_Install::META_QPMN_ORDER_SUBTOTAL;
    const META_QPMN_ORDER_SHIPPING      = Qpmn_Install::META_QPMN_ORDER_SHIPPING;
    const META_QPMN_ORDER_TOTAL         = Qpmn_Install::META_QPMN_ORDER_TOTAL;

    public function init_hooks()
    {
        add_filter('woocommerce_hidden_order_itemmeta', array($this, 'order_itemmeta_hidden_meta'));
        add_filter('woocommerce_order_item_display_meta_value', array($this, 'change_order_item_meta_value'), 20, 3);
        add_filter('woocommerce_order_item_display_meta_key', array($this, 'change_order_item_meta_title'), 20, 3);
        add_action('woocommerce_new_order_item', array($this, 'adding_custom_data_to_order_item_meta'), 10, 3);
        // add_action('woocommerce_payment_complete', array($this, 'payment_complete'), 10, 1);
        // add_action('woocommerce_order_status_changed', array($this, 'status_changed'), 10, 3);
    }

    public function adding_custom_data_to_order_item_meta($item_id, $item, $order_id)
    {
        $qp_data = isset($item->legacy_values[QPMN_WC_Cart::META_CART_GROUP]) ? $item->legacy_values[QPMN_WC_Cart::META_CART_GROUP] : null;
        if (!is_null($qp_data)) {
            if (isset($qp_data[QPMN_WC_Cart::META_CART_GROUP_IS_QP_PRODUCT])) {
                if ($qp_data[QPMN_WC_Cart::META_CART_GROUP_IS_QP_PRODUCT]) {
                    //mark to order level to indicate this is QPMN order also
                    self::set_order_meta($order_id, self::META_IS_QPMN_ORDER, true);
                }
                wc_add_order_item_meta($item_id, self::META_IS_QPPP_PRODUCT, $qp_data[QPMN_WC_Cart::META_CART_GROUP_IS_QP_PRODUCT]);
            }

            if (isset($qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_PRODUCT_ID])) {
                wc_add_order_item_meta($item_id, self::META_QPMN_PRODUCT_ID, $qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_PRODUCT_ID]);
            }
            if (isset($qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_ID])) {
                $designId = $qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_ID];
                wc_add_order_item_meta($item_id, self::META_QPP_DESIGN_ID, $designId);
            }
            if (isset($qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_CONFIG])) {
                $designConfig = $qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_CONFIG];
                wc_add_order_item_meta($item_id, self::META_QPP_DESIGN_CONFIG, $designConfig);
            }
            if (isset($qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_THUMBNAIL])) {
                wc_add_order_item_meta($item_id, self::META_QPP_DESIGN_THUMBNAIL, $qp_data[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_THUMBNAIL]);
            }
        }
    }

    /**
     * change order item meta value
     *
     * @param [type] $value
     * @param [type] $meta
     * @param WC_Order_Item_Product $item
     * @return void
     */
    public function change_order_item_meta_value($value, $meta, $item)
    {

        if (self::META_QPP_DESIGN_ID === $meta->key) {
            if (!empty($value)) {
                $url = $item->get_meta(self::META_QPP_DESIGN_THUMBNAIL);
                $btnText = Qpmn_i18n::__('Preview');

                $eleId = "qpmn-order-item-design-container-{$value}";
                $value = "
                    <div class='qpmn-bootstrap'>
                        <div id='{$eleId}' class='qpmn-order-item-design-container' ></div>
                    </div>
                ";
            }
        }

        return $value;
    }

    public function change_order_item_meta_title($key, $meta, $item)
    {
        if (self::META_QPP_DESIGN_ID === $meta->key) {
            if (!empty($key)) {
                $key = Qpmn_i18n::__('Design');
            }
        }

        return $key;
    }

    /**
     * hide item meta in order edit page
     */
    public function order_itemmeta_hidden_meta($meta_keys)
    {
        array_push($meta_keys, self::META_IS_QPPP_PRODUCT);
        array_push($meta_keys, self::META_QPMN_PRODUCT_ID);
        array_push($meta_keys, self::META_QPP_DESIGN_THUMBNAIL);
        array_push($meta_keys, self::META_QPMN_BUILDER_DESIGN_PREVIEW_URL);
        array_push($meta_keys, self::META_QPMN_ORDER_DATETIME);
        array_push($meta_keys, self::META_QPP_DESIGN_CONFIG);
        return $meta_keys;
    }

    //disabled feature
    public function status_changed($orderId, $oldStatus, $newStatus)
    {
        if ($newStatus === 'completed' && $oldStatus !== $newStatus) {
            //no auto create support
            // $this->create_cgp_order($orderId);
        }
    }

    /**
     *  return if order contain cgp product
     *
     * @param [type] $orderId
     * @return boolean
     */
    public function is_cgp_product_found($orderId)
    {
        return !empty(self::get_order_meta($orderId, self::META_IS_QPMN_ORDER));
    }

    /**
     * return if cgp order created
     *
     * @param [type] $orderId
     * @return boolean
     */
    public function is_cgp_order($orderId)
    {
        return !empty(self::get_order_meta($orderId, self::META_QPMN_ORDER_ID));
    }

    public static function set_order_meta($orderId, $key, $value)
    {
        update_post_meta($orderId, $key, $value);
    }

    public static function get_order_meta($orderId, $key, $single = true)
    {
        return get_post_meta($orderId, $key, $single);
    }

    /**
     * return cgp order info from order meta
     *
     * @return array
     */
    public function get_cgp_order($orderId)
    {
        /**
         * @var Logger $logger
         */
        // $logger = (PluginLogger::instance())->getLogger();
        $CGPOrderId = self::get_order_meta($orderId, self::META_QPMN_ORDER_ID);
        if ($CGPOrderId) {
            $token = QP_WP_Option_QPMN_Token::get();
            if (!empty($token)) {
                //auto logout if no token 
                //no need to throw exception for this request

                $partnerOrder = new PartnerOrder($token);
                $result = $partnerOrder->get(intval($CGPOrderId));

                if (!empty($result)) {
                    if (isset($result['data'])) {
                        if (isset($result['data']['order'])) {
                            //qpmn api response handle 
                            $result = $result['data']['order'];
                        }
                    }
                    $this->get_order_response_handler($orderId, $result);
                }
            }
        }
        //get order meta
        return $this->get_cgp_order_meta($orderId);
    }

    /**
     * return qpmn order info from order meta
     * @param array<qpmnOrderId> $orderIds
     *
     * @return array
     */
    public function bulk_get_cgp_order($orderIds)
    {
        /**
         * @var Logger $logger
         */
        $logger = (PluginLogger::instance())->getLogger();
        $orders = [];
        $token = QP_WP_Option_QPMN_Token::get();
        if (!empty($token)) {
            //auto logout if no token 
            //no need to throw exception for this request

            $partnerOrder = new PartnerOrder($token);
            $responses = $partnerOrder->bulkGet($orderIds);

            foreach ($responses as $orderId => $response) {
                /**
                 * @var ResponseInterface $response
                 */
                try {
                    $result = $response->toArray();
                    if (!empty($result)) {
                        if (isset($result['data'])) {
                            if (isset($result['data']['order'])) {
                                //qpmn api response handle 
                                $result = $result['data']['order'];
                            }
                        }
                        $this->get_order_response_handler($orderId, $result);
                    }
                    if (!isset($result['code'])) {
                        //get latest order meta data
                        $orders[] = $this->get_cgp_order_meta($orderId);
                    }
                } catch (Exception $e) {
                    $logger->error('update order info failed', ['exception' => $e]);
                }
            }
        }
        return $orders;
    }

    public function get_cgp_order_meta($orderId)
    {
        //get order meta
        $orderMeta = get_post_meta($orderId);

        return [
            'id'        => array_shift($orderMeta[self::META_QPMN_ORDER_ID]),
            'status'    => array_shift($orderMeta[self::META_QPMN_ORDER_STATUS]),
            'last_sync' => array_shift($orderMeta[self::META_QPMN_ORDER_LAST_SYNC_AT]),
            'order_datetime'    => array_shift($orderMeta[self::META_QPMN_ORDER_DATETIME]),
        ];
    }

    public function create_cgp_order($orderId)
    {
        /**
         * @var Logger $logger
         */
        $logger = (PluginLogger::instance())->getLogger();


        $token = QP_WP_Option_QPMN_Token::get();
        $payload = $this->create_cgp_order_payload($orderId);
        if (empty($token)) {
            //token not found, access token verify failed
            $logger->debug('Create order failed because QPMN api key verification failed.');
            $message = Qpmn_i18n::__('Please connect to QPMN.');
            $result = ['code' => 400, 'message' => $message];
        } else {

            $partnerOrder = new PartnerOrder($token);

            $result = $partnerOrder->create($payload);
        }


        if (!empty($result)) {
            if (isset($result['code']) && isset($result['data'])) {
                //assumption property code means error found
                if ($result['code'] == 200) {
                    $result = $result['data'];
                    if (isset($result['order'])) {
                        //qpmn api response handle 
                        $result = $result['order'];
                    }
                }
            } 
            $this->create_cgp_order_response_handler($orderId, $payload, $result);
        }

        return $result;
    }

    public function bulk_create_cgp_order($ids)
    {
        /**
         * @var Logger $logger
         */
        $logger = (PluginLogger::instance())->getLogger();

        $responses = [];
        $token = QP_WP_Option_QPMN_Token::get();
        $partnerOrder = new PartnerOrder($token);

        // loop over the array of record IDs and delete them
        foreach ($ids as $id) {
            if (is_numeric($id)) {
                $payload = $this->create_cgp_order_payload($id);

                if (empty($token)) {
                    //token not found, access token verify failed
                    $logger->debug('Create order failed because QPMN api key verification failed.');
                    $message = Qpmn_i18n::__('Please connect to QPMN.');
                    $result = ['code' => 400, 'message' => $message];

                    $this->create_cgp_order_response_handler($id, $payload, $result);
                    break;
                }

                if ($payload) {
                    //concurrent requests
                    $responses[$id] = $partnerOrder->bulkCreate($payload);
                }
            }
        }

        foreach($responses as $orderId => $resp) {
            try {
                $result = $resp->toArray(false);
                if (!empty($result)) {
                    if (isset($result['code']) && isset($result['data'])) {
                        //assumption property code means error found
                        if ($result['code'] == 200) {
                            $result = $result['data'];
                            if (isset($result['order'])) {
                                //qpmn api response handle 
                                $result = $result['order'];
                            }
                        }
                    } 
                    $this->create_cgp_order_response_handler($orderId, $payload, $result);
                }
            } catch (Exception $e) {
                $logger->error('create order info failed', ['exception' => $e]);
            }
        }
    }

    public function create_cgp_order_payload($orderId)
    {
        /**
         * @var WC_Order $order
         */
        $order = wc_get_order($orderId);
        $currency = $order->get_currency();
        $orderItems = [];

        /**
         * @var Logger $logger
         */
        $logger = (PluginLogger::instance())->getLogger();

        $QPMNOrderId = self::get_order_meta($orderId, self::META_QPMN_ORDER_ID);
        if (!empty($QPMNOrderId)) {
            //CGP order exists
            $logger->debug('Create Order failed because order already exists.', ['QPMN orderId' => $QPMNOrderId]);
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ERROR_MSG, 'Create Order failed because order already exists.');
            return null;
        }

        $orderItems = $this->prepare_cgp_order_items_request_data($order);

        if (empty($orderItems)) {
            // update_post_meta($orderId, 'QP Comment', 'normal order');
            //no qp product found, continue as normal order
            $logger->debug('Create order failed because no QP product found.');
            throw new \Exception(Qpmn_i18n::__('No order item found'));
        }

        //mark qp product in this order
        self::set_order_meta($orderId, self::META_IS_QPMN_ORDER, true);
        self::set_order_meta($orderId, self::META_QPMN_ORDER_LAST_SYNC_AT, null);
        self::set_order_meta($orderId, self::META_QPMN_ORDER_ID, null);
        self::set_order_meta($orderId, self::META_QPMN_ORDER_STATUS, null);
        self::set_order_meta($orderId, self::META_QPMN_ORDER_DATETIME, null);

        $orderData = $order->get_data();

        $newOrder = new QPMNOrder();
        $newOrder->shippingAddress = $orderData['shipping'];
        $newOrder->billingAddress = $orderData['billing'];
        $newOrder->orderItems = $orderItems;
        $newOrder->metaData = [
            [
                'key' => Qpmn_Install::QPMN_PARTNER_STORE_URL,
                'value' => site_url()
            ],
            [
                'key' => Qpmn_Install::QPMN_PARTNER_ORDER_ID,
                'value' => $orderId
            ],
            [
                'key' => Qpmn_Install::QPMN_PARTNER_ORDER_CURRENCY,
                'value' => $currency
            ],
        ];


        return $newOrder;
    }

    /**
     * @param WC_Order $order
     *
     * @return array<OrderItem> 
     */
    private function prepare_cgp_order_items_request_data($order)
    {
        $WCOrderItems = $order->get_items();
        $orderItems = [];
        foreach ($WCOrderItems as $k => $d) {
            /**
             * @var WC_Order_Item $d
             */
            $itemId = $d->get_id();
            $itemData = $d->get_data();
            $itemDesignID = wc_get_order_item_meta($itemId, self::META_QPP_DESIGN_ID, true);
            $qpmnProductID = wc_get_order_item_meta($itemId, self::META_QPMN_PRODUCT_ID, true);
            $isQPP = wc_get_order_item_meta($itemId, self::META_IS_QPPP_PRODUCT, true);
            if ($isQPP) {
                /**
                 * @var WC_Order_Item_Product $d
                 */
                $product = $d->get_product();
                $resalePrice = $product->get_price();
                //qpp product found
                if ($itemDesignID) {
                    //a valid item should contain design ID
                    //append to item list
                    $item = new QPMNOrderItem();
                    $item->productId = $qpmnProductID;
                    $item->quantity = $itemData['quantity'];
                    $item->metaData = [
                        [
                            'key' => Qpmn_Install::QPMN_PARTNER_RESALE_PRICE,
                            'value' => $resalePrice
                        ],
                        [
                            'key' => Qpmn_Install::QPMN_PARTNER_ORDER_ITEM_ID,
                            'value' => $itemId
                        ],
                        [
                            'key' => Qpmn_Install::QPMN_PARTNER_ORDER_ITEM_QTY,
                            'value' =>  $item->quantity
                        ],
                        [
                            'key' => Qpmn_Install::QPMN_PARTNER_DESIGN_ID,
                            'value' => $itemDesignID
                        ],
                    ];
                    $orderItems[] = $item;
                }
            }
        }

        return $orderItems;
    }

    /**
     *  function
     *
     * @param WC_Order $order
     * @param string $note
     * @return void
     */
    public static function add_order_note($order, $note)
    {
        $order->add_order_note($note);
    }

    private function get_order_response_handler($orderId, $result)
    {
        /**
         * @var WC_Order $order
         */
        $order = wc_get_order($orderId);
        /**
         * @var Logger $logger
         */
        $logger = (QPApiLogger::instance())->getLogger();

        if (empty($result)) {
            //display woocommerce notice
            QP_WC_Notices::admin_notice('get_order_response_handler', sprintf(Qpmn_i18n::__('Update QPMN order failed.')));
            $logger->error("Order# $orderId update QPMN order failed.");
        } else if (isset($result['code'])) {
            //display woocommerce notice
            QP_WC_Notices::admin_notice('get_order_response_handler', sprintf(Qpmn_i18n::__('Update QPMN order failed. error message:%1$s'), sanitize_text_field($result['message'])));
            $logger->error("Order# $orderId update QPMN order failed because " . sanitize_text_field($result['message']));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ERROR_MSG, sanitize_text_field($result['message']));
        } else {
            $newStatus = sanitize_text_field($result['status']);
            $oldStatus = self::get_order_meta($orderId, self::META_QPMN_ORDER_STATUS);
            if ($oldStatus != $newStatus) {
                $notice = sprintf(Qpmn_i18n::__('QPMN order status changed: <br>%1$s >>> %2$s'), esc_html($oldStatus), esc_html($newStatus));
                self::add_order_note($order, $notice);
            }

            self::set_order_meta($orderId, self::META_QPMN_ORDER_LAST_SYNC_AT, time());
            //CGP order id
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ID, sanitize_text_field($result['id']));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_NUMBER, sanitize_text_field($result['number']));

            self::set_order_meta($orderId, self::META_QPMN_ORDER_STATUS, sanitize_text_field($result['status']));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ERROR_MSG, null);
        }
    }

    /**
     * Undocumented function
     *
     * @param $orderId
     * @param \QPMN\Partner\Libs\HttpClient\Obj\Order $newOrder
     * @param Array $result
     * @return void
     */
    private function create_cgp_order_response_handler($orderId, $newOrder, $result)
    {
        /**
         * @var WC_Order $order
         */
        $order = wc_get_order($orderId);

        if (isset($result['code'])) {
            self::add_order_note($order, sprintf(Qpmn_i18n::__('Create QPMN order failed. <br>error# %1$s <br>error message: %2$s'), sanitize_text_field($result['code']), sanitize_text_field($result['message'])));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ERROR_MSG, sanitize_text_field($result['message']));
        } else {
            self::add_order_note($order, sprintf(Qpmn_i18n::__('QPMN order create successful. <br>order# %s'), sanitize_text_field($result['number'])));

            //CGP order id
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ID, sanitize_text_field($result['id']));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_NUMBER, sanitize_text_field($result['number']));

            self::set_order_meta($orderId, self::META_QPMN_ORDER_LAST_SYNC_AT, null);
            self::set_order_meta($orderId, self::META_QPMN_ORDER_STATUS, sanitize_text_field($result['status']));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_DATETIME, sanitize_text_field($result['date_created_gmt']));
            self::set_order_meta($orderId, self::META_QPMN_ORDER_ERROR_MSG, sanitize_text_field($result['message']) ?? null);
        }
    }
}
