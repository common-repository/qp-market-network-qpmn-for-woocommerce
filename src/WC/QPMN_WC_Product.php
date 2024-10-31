<?php

namespace QPMN\Partner\WC;

use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QPMN_WC_Product extends QP_WC
{
    const PRODUCT_FLAG = Qpmn_Install::META_IS_QPPP_PRODUCT;

    //namespace + method + base path of rewrite rule 
    const CUSTOM_BTN_NONCE = 'QPMN\Partner\WC\QP_WC_Product::add_custom_btn-/qpmnproxy/builder';


    public function init_hooks()
    {

        //add customize btn
        add_action('woocommerce_before_add_to_cart_button', array($this, 'add_product_meta_form'));
        add_action('woocommerce_after_add_to_cart_button', array($this, 'add_custom_btn'));


        add_action('woocommerce_loop_add_to_cart_link', array(&$this, 'add_catalog_customize_button'), 10, 2);
        // add_action('woocommerce_after_shop_loop_item', array(&$this, 'add_catalog_customize_button'), 20);
        add_filter('woocommerce_cart_item_thumbnail', array($this, 'assign_builder_design_thumbnail'), 10, 2);
    }

    /**
     * add hidden input field to product page
     * store additional data to cart item
     *
     * @return void
     */
    public function add_product_meta_form()
    {
        global $post;
        $meta = get_post_meta($post->ID);

        //add current site url to support multisite 
        $siteUrl = get_site_url();

        if ($this->is_qp_product($meta)) {
            $thumbnail = '';
            $designId = '';
            //no design confign = no edit button
            $designConfig = '';
            $isCustomizationDisabled = false;
            $qpProductId = array_shift($meta[Qpmn_Install::META_QPMN_PRODUCT_ID]);

            if (!empty($_GET['cart_item_key'])) {
                $cartItemKey = sanitize_text_field($_GET['cart_item_key']);
                $cartItem = WC()->cart->get_cart_item($cartItemKey);
                $cartItemQPP = $cartItem[QPMN_WC_Cart::META_CART_GROUP] ?? [];
                $designId = $cartItemQPP[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_ID] ?? '';
                $designConfig = $cartItemQPP[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_CONFIG] ?? '';
                $thumbnail = $cartItemQPP[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_THUMBNAIL] ?? '';
            } 

            if (isset($meta[Qpmn_Install::META_QPMN_PRODUCT_DISABLE_CUSTOMIZATION])) {
                //disable metadata found
                $isCustomizationDisabled = array_shift($meta[Qpmn_Install::META_QPMN_PRODUCT_DISABLE_CUSTOMIZATION]) == 1;

                if ($isCustomizationDisabled) {
                    //assign default design ID
                    if (isset($meta[Qpmn_Install::META_QPMN_PRODUCT_DEFAULT_DESIGN_ID])) {
                        $designId = array_shift($meta[Qpmn_Install::META_QPMN_PRODUCT_DEFAULT_DESIGN_ID]);
                    }
                    //assign default design thumbnail
                    if (isset($meta[Qpmn_Install::META_QPMN_PRODUCT_DEFAULT_DESIGN_THUMBNAIL])) {
                        $thumbnail = array_shift($meta[Qpmn_Install::META_QPMN_PRODUCT_DEFAULT_DESIGN_THUMBNAIL]);
                    }
                }
            }

            //create wp nonce
            $nonce = wp_create_nonce(self::CUSTOM_BTN_NONCE);

            // $queryString = http_build_query($tmp);
            //proxy handle rest of parameter
            $builderPath = $this->get_builder_path($meta);
            //extra query string stored in metadata field
            $builderQueryArray = $this->get_builder_query_string($meta);
            $existsparams = [];

            //build builder url
            $url = $builderPath;
            $urlParts = parse_url($url);
            if (!isset($urlParts['host'])) {
                //backward compatible handle
                //url path store in product metadata and builder domain store in env
                $url = Qpmn_Install::QPMN_PARTNER_BUILDER_ENDPOINT . $builderPath;
                $urlParts = parse_url($url);
            }
            if (isset($urlParts['query'])) {
                //check exists query string found
                parse_str($urlParts['query'], $existsparams);
            } 
            $tmpQuery = array_merge([], $existsparams, $builderQueryArray);

            $iframeSrc = "";
            if (isset($urlParts['host'])) {
                //build url
                $host = $urlParts['host'];
                if (isset($urlParts['port'])) {
                    $host .= ":".$urlParts['port'];
                }
                $iframeSrc =  $urlParts['scheme'] .'://'. $host. $urlParts['path'] . '?'. http_build_query($tmpQuery);
            }
?>
            <input autocomplete="off" type="hidden" value="1" name="is_qp_product" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($post->ID); ?>" name="proxypost" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($qpProductId); ?>" name="qp_product_id" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($designId); ?>" name="qp_design_id" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($isCustomizationDisabled ? 1: 0); ?>" name="is_customization_disabled" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($designConfig); ?>" name="qp_design_config" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($thumbnail); ?>" name="qp_design_thumbnail" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_url_raw($siteUrl); ?>" name="site_url" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_attr($nonce); ?>" name="nonce" />
            <input autocomplete="off" type="hidden" value="<?php echo esc_url_raw($iframeSrc); ?>" name="qp_iframe_src" />
<?php
        }
    }

    /**
     * add custom btn to product page 
     * 1. determine create or edit URL base on hidden form data - design id
     * 2. integrate H5 builder 
     *  - load via iframe
     *  - interaction via public.js
     *
     * @return void
     */
    public function add_custom_btn()
    {
        global $post;
        $id = $post->ID;
        $meta = get_post_meta($id);
        if ($this->is_qp_product($meta)) {
            echo '<div id="qpmn-personalize-product"></div>';
        }
    }

    private function is_qp_product($meta)
    {
        $is_qp_product = isset($meta[self::PRODUCT_FLAG]) && is_array($meta[self::PRODUCT_FLAG]) 
            && array_shift($meta[self::PRODUCT_FLAG]) == true;
        return $is_qp_product;
    }

    private function get_builder_path($meta)
    {
        $path = '';
        $key = Qpmn_Install::META_QPMN_BUILDER_PATH;
        if (isset($meta[$key]) && is_array($meta[$key])) {
            $path = array_shift($meta[$key]);
        }
        return $path;
    }

    private function get_builder_query_string($meta)
    {
        $query = [];
        $key = Qpmn_Install::META_QPMN_BUILDER_TEMPLATE;
        if (isset($meta[$key]) && is_array($meta[$key])) {
            $designtemplate = array_shift($meta[$key]);
            if (!empty($designtemplate)) {
                //avoid take empty metadata 
                $query['designtemplate'] = $designtemplate;
            }
        }
        return $query;
    }

    /**
     * action hook: woocommerce_after_shop_loop_item
     * replace add_to_cart btn to customize btn, including
     *      Shop page,
     *      Product category pages,
     *      Product tag pages,
     *      Search results
     */
    public function add_catalog_customize_button()
    {
        global $product;

        $meta = get_post_meta($product->get_id());

        if ($this->is_qp_product($meta)) {

            //TODO: multiple lang
            printf(
                '<a href="%s" rel="nofollow" class="button qpmn-product-customize-btn">%s</a>',
                esc_url(get_permalink($product->get_id())),
                esc_html(Qpmn_i18n::__('Customize'))
            );
        }
    }

    /**
     * disable plugin created products
     *
     * @return void
     */
    public function disable_products()
    {
        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => self::PRODUCT_FLAG,
                    'value' => true,
                ),
            ),
        );

        $qp_product_query = new \WP_Query($args);
        $qp_products = $qp_product_query->get_posts();

        foreach ($qp_products as $p) {
            $tmp = (array)$p;
            $tmp['post_status'] = 'draft';
            wp_update_post($tmp);
        }
    }

    //cart page thumbnail
    public function assign_builder_design_thumbnail($thumbnail, $cart_item)
    {
        $qpmnData = $cart_item[QPMN_WC_Cart::META_CART_GROUP] ?? null;
        if ($qpmnData) {
            $myThumbnail = $qpmnData[QPMN_WC_Cart::META_CART_GROUP_QP_DESIGN_THUMBNAIL] ?? null;
            if (!empty($myThumbnail)) {
                $imgUrl = $myThumbnail;

                $img = '<img src="' . $imgUrl . '" class="attachment-woocommerce_thumbnail size-woocommerce_thumbnail" alt="" loading="lazy" srcset="' . $imgUrl . ' 324w,' . $imgUrl . ' 510w" sizes="(max-width: 324px) 100vw, 324px" width="324" height="324">';

                return $img;
            }
        }
        return $thumbnail;
    }
}
