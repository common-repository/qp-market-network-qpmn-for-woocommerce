<?php

namespace QPMN\Partner\Libs\Monolog;

use Exception;

if (!defined('ABSPATH')) {
    exit;
}

class Logger 
{
    private static $instance = array();

    public static function instance()
    {
        $caller = get_called_class();

        if (!isset(self::$instance[$caller])) {
            self::$instance[$caller] = new static;
        }

        return self::$instance[$caller];
    }

    public function __wakeup()
    {
        throw new Exception('can not unserialize singleton');
    }

    protected function __construct()
    {
    }

    protected function __clone()
    {
    }
}
