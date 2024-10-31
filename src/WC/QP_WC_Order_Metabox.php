<?php

namespace QPMN\Partner\WC;

use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QP_WC_Order_Metabox extends QP_WC
{
    const META_IS_QPMN_ORDER = Qpmn_Install::META_IS_QPMN_ORDER;

    const META_QPMN_ORDER_LAST_SYNC_AT  = Qpmn_Install::META_QPMN_ORDER_LAST_SYNC_AT;
    const META_QPMN_ORDER_ID            = Qpmn_Install::META_QPMN_ORDER_ID;
    const META_QPMN_ORDER_NUMBER            = Qpmn_Install::META_QPMN_ORDER_NUMBER;
    const META_QPMN_ORDER_STATUS        = Qpmn_Install::META_QPMN_ORDER_STATUS;
    const META_QPMN_ORDER_DATETIME      = Qpmn_Install::META_QPMN_ORDER_DATETIME;
    const META_QPMN_ORDER_ERROR_MSG     = Qpmn_Install::META_QPMN_ORDER_ERROR_MSG;

    const META_QPMN_ORDER_SUBTOTAL      = Qpmn_Install::META_QPMN_ORDER_SUBTOTAL;
    const META_QPMN_ORDER_SHIPPING      = Qpmn_Install::META_QPMN_ORDER_SHIPPING;
    const META_QPMN_ORDER_TOTAL         = Qpmn_Install::META_QPMN_ORDER_TOTAL;

    public function init_hooks()
    {
        add_action('add_meta_boxes', array($this, 'cgp_order_meta_box'), 10, 2);
    }

    public function cgp_order_meta_box($type, $post)
    {
        if ($this->is_cgp_product_found($post->ID)) {
            add_meta_box(
                'qpmn_cgp_order_info',
                Qpmn_i18n::__('QPMN order'),
                array($this, 'cgp_order_meta_box_callback'),
                'shop_order',
                'side',
                'high',
                $post
            );
        }
    }

    public function cpg_order_meta_box_params($post)
    {
        $data = [];
        //prepare rendering data
        $orderId        = $post->ID;
        $cgpOrderData   = $this->get_cgp_order_meta($orderId);
        $lastSyncAt     = $cgpOrderData['last_sync'];

        $data['orderId'] = $orderId;
        $data['CGPOrderID'] = $cgpOrderData['id'];
        $data['CGPOrderNumber'] = $cgpOrderData['order_number'];
        $data['CGPOrderStatus'] = $cgpOrderData['status'];
        $data['CGPOrderSubtotal'] = $cgpOrderData['subtotal'];
        $data['CGPOrderShipping'] = $cgpOrderData['shipping'];
        $data['CGPOrderTotal'] = $cgpOrderData['total'];
        if ($lastSyncAt) {
            $lastSyncDateTime = new \DateTime();
            $lastSyncDateTime->setTimestamp($lastSyncAt);
            $lastSyncDateTime = $lastSyncDateTime->format('Y-m-d H:i:s');
        } else {
            $lastSyncDateTime = Qpmn_i18n::__('N/A');
        }

        $data['lastSyncDateTime'] = $lastSyncDateTime;

        $data['displayCreateOrder'] = empty($cgpOrderData['id']);

        return $data;
    }

    public function cgp_order_meta_box_callback($post)
    {
        $data = $this->cpg_order_meta_box_params($post);
        //render meta box
        include_once QPMN_PLUGIN_ROOT . 'src/Admin/partials/qpmn-admin-order-meta-box.php';
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


    public static function get_order_meta($orderId, $key, $single = true)
    {
        return get_post_meta($orderId, $key, $single);
    }


    public function get_cgp_order_meta($orderId)
    {
        //get order meta
        $orderMeta      = get_post_meta($orderId);
        $id             = $orderMeta[self::META_QPMN_ORDER_ID] ?? null;
        $orderNumber    = $orderMeta[self::META_QPMN_ORDER_NUMBER] ?? null;
        $status         = $orderMeta[self::META_QPMN_ORDER_STATUS] ?? null;
        $lastSync       = $orderMeta[self::META_QPMN_ORDER_LAST_SYNC_AT] ?? null;
        $orderDatetime  = $orderMeta[self::META_QPMN_ORDER_DATETIME] ?? null;
        $subtotal       = $orderMeta[self::META_QPMN_ORDER_SUBTOTAL] ?? null;
        $shipping       = $orderMeta[self::META_QPMN_ORDER_SHIPPING] ?? null;
        $total          = $orderMeta[self::META_QPMN_ORDER_TOTAL] ?? null;

        return [
            'id'            => is_array($id) ? array_shift($id) : null,
            'order_number'  => is_array($orderNumber) ? array_shift($orderNumber) : null,
            'status'        => is_array($status) ? array_shift($status) : null,
            'last_sync'     => is_array($lastSync) ? array_shift($lastSync) : null,
            'order_datetime'=> is_array($orderDatetime) ? array_shift($orderDatetime) : null,
            'subtotal'      => is_array($subtotal) ? array_shift($subtotal) : null,
            'shipping'      => is_array($shipping) ? array_shift($shipping) : null,
            'total'         => is_array($total) ? array_shift($total) : null,
        ];
    }
}
