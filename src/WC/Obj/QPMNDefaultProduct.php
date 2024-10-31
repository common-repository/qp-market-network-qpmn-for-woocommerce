<?php

namespace QPMN\Partner\WC\Obj;

use Exception;
use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Qpmn_Install;

if (!defined('ABSPATH')) {
    exit;
}

class QPMNDefaultProduct extends Product
{
    private $productId = null;
    public function __construct(int $productId)
    {
        $this->productId = $productId;
    }

    public function defaultSettings(): QPMNDefaultProduct
    {

        $postId = $this->productId;

        wp_set_object_terms($postId, 'simple', 'product_type');
        //existing post meta
        wp_set_object_terms($postId, 'simple', 'product_type');

        update_post_meta($postId, '_visibility', 'visible');
        update_post_meta($postId, '_stock_status', 'instock');
        update_post_meta($postId, 'total_sales', '0');
        update_post_meta($postId, '_downloadable', 'no');
        update_post_meta($postId, '_virtual', 'no');
        update_post_meta($postId, '_regular_price', '');
        update_post_meta($postId, '_sale_price', '');
        update_post_meta($postId, '_purchase_note', '');
        update_post_meta($postId, '_featured', 'no');
        update_post_meta($postId, '_weight', '');
        update_post_meta($postId, '_length', '');
        update_post_meta($postId, '_width', '');
        update_post_meta($postId, '_height', '');
        update_post_meta($postId, '_sku', '');
        update_post_meta($postId, '_product_attributes', array());
        update_post_meta($postId, '_sale_price_dates_from', '');
        update_post_meta($postId, '_sale_price_dates_to', '');
        update_post_meta($postId, '_price', '');
        update_post_meta($postId, '_sold_individually', '');
        update_post_meta($postId, '_manage_stock', 'no'); // activate stock management
        update_post_meta($postId, '_backorders', 'no');

        return $this;
    }

    public function setImages(array $images):Product 
    {
        /**
         * @var Logger $logger
         */
        $logger = (PluginLogger::instance())->getLogger();
        $downloadedImages = [];
        foreach ($images as $url) {
            try {
                $tmpUrl = esc_url_raw($url);
                //upload images
                 $tmpMediaId = \media_sideload_image($tmpUrl, $this->productId, '', 'id');
                if (is_numeric($tmpMediaId)) {
                    $downloadedImages[] = $tmpMediaId;
                } 
            } catch (Exception $e) {
                $logger->debug('create product -download product image failed. Reason:' . $e->getMessage());
            }
        }

        if (count($downloadedImages)) {
            set_post_thumbnail($this->productId, $downloadedImages[0]);
        }

        //remove thumnail image
        array_shift($downloadedImages);

        if (count($downloadedImages)) {
            //add rest of images to gallery
            update_post_meta($this->productId, '_product_image_gallery', implode(',', $downloadedImages));
        }

        return $this;
    }

    public function setCategories(array $categories):Product
    {
        if (is_array($categories) && count($categories)) {
            wp_set_object_terms($this->productId, $categories, 'product_cat');
        }
        return $this;
    }

    public function setTags(array $tags):Product 
    {
        if (is_array($tags) && count($tags)) {
            wp_set_object_terms($this->productId, $tags, 'product_tag');
        }
        return $this;
    }

}
