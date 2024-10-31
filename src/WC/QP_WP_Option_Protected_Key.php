<?php
namespace QPMN\Partner\WC;

use QPMN\Partner\Qpmn_Install;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QP_WP_Option_Protected_Key extends QP_WP_Option
{
    const PROTECTED_KEY = Qpmn_Install::PLUGIN_PROTECTED_KEY;

    public function init_hooks()
    {
    }
    /**
     * store protected key during plugin activation
     *
     * @param string $name
     * @param string $value
     * @param boolean $autoload
     * @return void
     */
    public static function saveAndUpdate($value, $name = null, $autoload = false) {
        update_option(self::PROTECTED_KEY, $value, $autoload);
    }

    public static function get($name = null) {
        return get_option(self::PROTECTED_KEY);
    }

    public static function delete($name = null) {
        delete_option(self::PROTECTED_KEY);
    }

    public static function deleteAll()
    {
        delete_option(self::PROTECTED_KEY);
    }

    public static function init() 
    {
        add_option(self::PROTECTED_KEY);
    }
}
