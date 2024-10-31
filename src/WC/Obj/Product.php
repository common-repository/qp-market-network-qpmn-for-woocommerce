<?php

namespace QPMN\Partner\WC\Obj;

if (!defined('ABSPATH')) {
    exit;
}

require_once(ABSPATH . 'wp-admin/includes/media.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');
abstract class Product
{
    const PRODUCT_ID = 0;
    public static $NAME = '';
    public static $CATEGORIES = [];
    public static $TAGS = [];
    /**
     * create wc product
     *
     * @return void
     */
    abstract public function defaultSettings(): Product;
    abstract public function setImages(array $images):Product;
    abstract public function setCategories(array $categories):Product;
    abstract public function setTags(array $tags):Product;

}
