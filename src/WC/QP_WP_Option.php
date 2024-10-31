<?php
namespace QPMN\Partner\WC;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

abstract class QP_WP_Option 
{
    abstract public function init_hooks();

    abstract public static function saveAndUpdate($value, $name = null, $autoload = false) ;

    abstract public static function delete(); 

    /**
     * use for related options clean up 
     * e.g. plugin uninstall
     *
     * @return void
     */
    abstract static function deleteAll();

    /**
     * 
     * use for init options
     * e.g plugin activate
     *
     * @return void
     */
    abstract static function init();

    /**
     * return false if empty
     *
     * @param [type] $name
     * @return void
     */
    public static function get($name = null)
    {
        $result = get_option($name);
        return !empty($result)? $result : false;
    }


}
