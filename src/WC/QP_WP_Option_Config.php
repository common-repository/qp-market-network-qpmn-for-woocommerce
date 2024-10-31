<?php

namespace QPMN\Partner\WC;

use QPMN\Partner\Libs\Monolog\PluginLogger;
use QPMN\Partner\Qpmn_i18n;
use QPMN\Partner\Qpmn_Install;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QP_WP_Option_Config extends QP_WP_Option
{
    const OPTION_DEBUG = Qpmn_Install::CONFIG_DEBUG_MODE;
    const OPTION_SCHEDULE  = Qpmn_Install::CONFIG_ORDER_UPDATE_SCHEDULE;

    const ALLOWED_OPTIONS = [self::OPTION_DEBUG, self::OPTION_SCHEDULE];
    const SCHEDULE_OPTIONS = ['hourly', 'daily', 'weekly'];
    const SCHEDULE_DISBALE_OPTION = 'disable';

    public function init_hooks()
    {
    }

    /**
     *  limitation: allowed option name  
     *
     * @param string $value
     * @param string $name
     * @param boolean $autoload
     * @return void
     */
    public static function saveAndUpdate($value, $name = null, $autoload = false)
    {
        if (in_array($name, self::ALLOWED_OPTIONS)) {
            update_option($name, $value, $autoload);
        } else {
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->debug('update config option failed becaue disallowed option provided.');
            throw new \Exception('invalid name');
        }
    }

    public static function delete($name = null) {
        if (in_array($name, self::ALLOWED_OPTIONS)) {
            delete_option($name);
        } else {
            /**
             * @var Logger $logger
             */
            $logger = (PluginLogger::instance())->getLogger();
            $logger->debug('delete config option failed becaue disallowed option provided.');
            throw new \Exception('invalid name');
        }
    }

    public static function deleteAll()
    {
        delete_option(self::OPTION_DEBUG);
        delete_option(self::OPTION_SCHEDULE);
    }

    /**
     * notice: add_option save blank string when assign false
     *
     * @return void
     */
    public static function init()
    {
        add_option(self::OPTION_DEBUG, false);
        add_option(self::OPTION_SCHEDULE, self::SCHEDULE_DISBALE_OPTION);
    }

    /**
     * return current config  
     *
     * @return array
     */
    public static function getConfigs() 
    {
        return [
            'debug'     => parent::get(self::OPTION_DEBUG),
            'schedule'  => parent::get(self::OPTION_SCHEDULE)
        ];
    }

    public static function updateSchedule($scheudle)
    {
        self::saveAndUpdate($scheudle, self::OPTION_SCHEDULE);
    }

    //default enable debug mode
    public static function updateDebugMode($debug = 'true')
    {
        self::saveAndUpdate($debug, self::OPTION_DEBUG);
    }

    public static function isDebugMode()
    {
        return parent::get(self::OPTION_DEBUG) === 'true';
    }

    /**
     * return config schedule option with display text
     *
     * @return array
     */
    public static function getScheduleOptions()
    {
        $result = [];
        
        //hardcode for apply multiple langauge
        $result['hourly']   = Qpmn_i18n::__('hourly');
        $result['daily']    = Qpmn_i18n::__('daily');
        $result['weekly']   = Qpmn_i18n::__('weekly');
        $result['disable']  = Qpmn_i18n::__('disable');
        return $result;
    }

}
