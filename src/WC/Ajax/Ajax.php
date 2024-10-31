<?php

namespace QPMN\Partner\WC\Ajax;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

interface Ajax
{
    public function init_hooks();
}
