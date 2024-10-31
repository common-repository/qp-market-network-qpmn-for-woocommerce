<?php

namespace QPMN\Partner\WC;

use QPMN\Partner\Qpmn_i18n;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QPMN_WC_Cart extends QP_WC
{
    const META_CART_GROUP = 'qpmn_data'; 
    const META_CART_GROUP_IS_QP_PRODUCT         = 'is_qp_product'; 
    const META_CART_GROUP_QP_PRODUCT_ID         = 'qp_product_id'; 
    const META_CART_GROUP_QP_DESIGN_ID          = 'qp_design_id'; 
    const META_CART_GROUP_QP_DESIGN_THUMBNAIL   = 'qp_design_thumbnail'; 
    const META_CART_GROUP_QP_DESIGN_CONFIG      = 'qp_design_config'; 
    const OPTION_ADD_TO_CART_PROCESS_FAILED     = 'qpmn_add_to_cart_process_failed';

    public function init_hooks()
    {
        //reset cart item link so the customized product able to loaded from the cart
        add_filter('woocommerce_add_to_cart_validation', array(&$this, 'validate_qp_product'), 10, 3);
        add_filter('woocommerce_add_cart_item_data', array(&$this, 'filter_woocommerce_add_qp_product_to_cart_item'), 50, 3);
        add_filter('woocommerce_cart_item_permalink', array(&$this, 'set_cart_item_permalink'), 100, 3);
        add_action('admin_notices', [$this, 'add_to_cart_process_failed']);
    }

    public static function resetOptions()
    {
        delete_option(self::OPTION_ADD_TO_CART_PROCESS_FAILED);
    }

    public function validate_qp_product($passed, $product_id, $quantity)
    {
        //generate required design preview URL
        if (isset($_POST['is_qp_product']) && isset($_POST['qp_design_id'])) {
            $designId = intval($_POST['qp_design_id']);
            if (empty($designId)) {
                wc_add_notice(Qpmn_i18n::__('Add to cart failed. Reason: customization required.'), 'error');
                $passed = false;
            } 
        } 
        return $passed;
    }

    //add custom field to cart item data
    public function filter_woocommerce_add_qp_product_to_cart_item($cart_item_meta, $product_id, $variant_id)
    {
        if (isset($_POST['is_qp_product'])) {
            //qpfb = qp fanny bag data
            $cart_item_meta[self::META_CART_GROUP] = array();
            //this is a qp product if parameter 'is_qp_product' exists
            $cart_item_meta[self::META_CART_GROUP][self::META_CART_GROUP_IS_QP_PRODUCT] = true;

            if (isset($_POST['qp_product_id'])) {
                $cart_item_meta[self::META_CART_GROUP][self::META_CART_GROUP_QP_PRODUCT_ID] = intval($_POST['qp_product_id']);
            }
            if (isset($_POST['qp_design_id'])) {
                $cart_item_meta[self::META_CART_GROUP][self::META_CART_GROUP_QP_DESIGN_ID] = intval($_POST['qp_design_id']);
            }
            if (isset($_POST['qp_design_thumbnail'])) {
                $cart_item_meta[self::META_CART_GROUP][self::META_CART_GROUP_QP_DESIGN_THUMBNAIL] = sanitize_text_field($_POST['qp_design_thumbnail']);
            }
            if (isset($_POST['qp_design_config'])) {
                $cart_item_meta[self::META_CART_GROUP][self::META_CART_GROUP_QP_DESIGN_CONFIG] = sanitize_text_field($_POST['qp_design_config']);
            }
        }

        return $cart_item_meta;
    }

    /**
     * one cart item per personalized produt 
     *
     * @param [type] $permalink
     * @param [type] $cart_item
     * @param [type] $cart_item_key
     * @return void
     */
    public function set_cart_item_permalink($permalink, $cart_item = null, $cart_item_key = null)
    {

        if (!empty($permalink) && $cart_item && isset($cart_item[self::META_CART_GROUP]) && !empty($cart_item[self::META_CART_GROUP])) {

            $permalink = add_query_arg(array('cart_item_key' => $cart_item_key), $permalink);
        }

        return $permalink;
    }

    public function add_to_cart_process_failed()
    {
        if (get_option(self::OPTION_ADD_TO_CART_PROCESS_FAILED)) {
            $url = admin_url("admin.php?page=qpmn_options_logs");
            $class = 'notice notice-error';
            $message = sprintf(Qpmn_i18n::__('Customer could not add QP product to shopping cart. Please go to <a href="%1$s">Debug Logs</a> to check reason.'), esc_url($url));
    
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), $message);
        }
    }
}
