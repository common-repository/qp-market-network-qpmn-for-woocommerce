<?php

namespace QPMN\Partner\WC\API\WC;

use QPMN\Partner\Qpmn_Install;
use QPMN\Partner\WC\API\API;
use QPMN\Partner\WC\Obj\QPMNDefaultProduct;
use WP_REST_Response;
use WP_REST_Server;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QPMNProduct implements API
{
    protected $namespace;
    protected $resource;

    public function __construct()
    {
        $this->namespace = Qpmn_Install::PLUGIN_API_NAMESPACE;
        $this->resource = 'wc/product';
    }

    public function register_routes()
    {
        $resource = $this->resource;
        register_rest_route($this->namespace, "/$resource", array(
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create'),
                'permission_callback' => function (\WP_REST_Request $request) {
                    return current_user_can('manage_options');
                }
            )
        ));
    }

    /**
     * create 
     *
     * @param array $data
     * @return WP_REST_Response
     */
    public function create($data)
    {
        try {
            $id = sanitize_key($data['id']);
            $productName    = sanitize_text_field($data['productName']);
            $cats = is_array($data['categories'])? $data['categories'] : [];
            $tags = is_array($data['tags'])? $data['tags'] : [];
            $images = is_array($data['images'])? $data['images'] : [];
            $shortDesc = wp_kses_post($data['short_description'] ?? '');
            $desc = wp_kses_post($data['description'] ?? '');
            $templateId = sanitize_text_field($data['template'] ?? '');
            $designId   = sanitize_text_field($data['designId'] ?? '');
            $disableCustomization   = rest_sanitize_boolean($data['disableCustomization'] ?? false);
            $builderUrl    = esc_url_raw($data['builder']);
            $templateThumb    = esc_url_raw($data['templateThumbnail']);

            $customizationDisabledMetadataFlag = $disableCustomization ? 1 : 0;

            $productId = wp_insert_post(
                array(
                    'post_title' => $productName,
                    'post_type' => 'product',
                    'post_content' => $desc,
                    'post_excerpt' => $shortDesc,
                    'post_status' => 'draft',
                )
            );

            //custom post meta
            add_post_meta($productId, Qpmn_Install::META_IS_QPPP_PRODUCT, true, true);
            add_post_meta($productId, Qpmn_Install::META_QPMN_PRODUCT_ID, $id, true);
            add_post_meta($productId, Qpmn_Install::META_QPMN_BUILDER_PATH, $builderUrl, true);
            add_post_meta($productId, Qpmn_Install::META_QPMN_BUILDER_TEMPLATE, $templateId, true);


            //metadata for disable customization button
            //allow partner selling their own design without customization
            add_post_meta($productId, Qpmn_Install::META_QPMN_PRODUCT_DISABLE_CUSTOMIZATION, $customizationDisabledMetadataFlag, true);
            add_post_meta($productId, Qpmn_Install::META_QPMN_PRODUCT_DEFAULT_DESIGN_ID, $designId, true);
            add_post_meta($productId, Qpmn_Install::META_QPMN_PRODUCT_DEFAULT_DESIGN_THUMBNAIL, $templateThumb, true);
        
            $product = new QPMNDefaultProduct($productId);
            if (empty($templateId)) {
                //normal product
                $product->defaultSettings()->setImages($images)->setCategories($cats)->setTags($tags);
            } else {
                //create template product
                $product->defaultSettings()->setImages([$templateThumb])->setCategories($cats)->setTags($tags);
            }

            //get user name
            $response = [
                'productId' => $productId
            ];

            return new WP_REST_Response($response, 200);
        } catch (\Exception $e) {
            return new WP_REST_Response([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
