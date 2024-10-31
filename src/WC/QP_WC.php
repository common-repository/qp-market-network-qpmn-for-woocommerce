<?php
namespace QPMN\Partner\WC;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

abstract class QP_WC
{
    abstract function init_hooks();
}
