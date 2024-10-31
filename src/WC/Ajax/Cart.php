<?php

namespace QPMN\Partner\WC\Ajax;

use Exception;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\WC\Ajax\Ajax;
use QPMN\Partner\WC\QPMN_WC_Cart;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class Cart implements Ajax
{
    public function init_hooks()
    {
        $actionFuncName = 'update_cart_item_design';
        add_action("wp_ajax_nopriv_{$actionFuncName}", array($this, $actionFuncName));
        add_action("wp_ajax_{$actionFuncName}", array($this, $actionFuncName));
    }

    public function update_cart_item_design()
    {
        try {
            $cartItemKey = sanitize_text_field($_POST['cart_item_key']);
            //permission check
            $isValid = check_ajax_referer('QPMN\API\WC\Ajax\Cart::update_cart' . $cartItemKey);
            if (!$isValid) {
                wp_send_json([
                    'msg' => 'invalid'
                ], 401);
            } else {
                $designId = intval($_POST['design_id']);
                $designConfig = sanitize_text_field($_POST['design_config']);
                $designThumbnail = esc_url($_POST['design_thumbnail']);

                if (empty($designId) || empty($designConfig)) {
                    //incomplete parameters
                    wp_send_json([
                        'msg' => 'parameters missing'
                    ], 400);
                }

                $cartItem = WC()->cart->get_cart_item($cartItemKey);
                $cartItemQPP = $cartItem[QPMN_WC_Cart::META_CART_GROUP] ?? [];

                if (empty($cartItemQPP)) {
                    //this cart item not belongs to QPMN product
                    wp_send_json([
                        'msg' => 'invalid product'
                    ], 401);
                }

                if (!empty($cartItem)) {
                    //cart item found  
                    //update builder id, config and template
                    $cartItemQPP[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_ID] = $designId;
                    $cartItemQPP[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_CONFIG] = $designConfig;
                    $cartItemQPP[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_THUMBNAIL] = $designThumbnail;

                    //update cart item
                    $cartItem[QPMN_WC_Cart::META_CART_GROUP] = $cartItemQPP;
                    //update shopping cart
                    $cartContent = WC()->cart->cart_contents;
                    $cartContent[$cartItemKey] = $cartItem;
                    WC()->cart->set_cart_contents($cartContent);

                    //save updated cart item data. like db commit 
                    WC()->cart->set_session();

                    wp_send_json([
                        'msg' => 'success'
                    ]);
                } else {
                    wp_send_json([
                        'msg' => 'item not found'
                    ], 401);
                }
            }
        } catch (Exception $e) {
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->error('a customer could not update their design because ' . $e->getMessage());
            wp_send_json([
                'msg' => $e->getMessage()
            ], 500);
        }
    }
}
