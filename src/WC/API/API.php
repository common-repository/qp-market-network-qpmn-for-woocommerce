<?php

namespace QPMN\Partner\WC\API;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

interface API
{
    public function __construct();
    public function register_routes();
}
