<?php

namespace QPMN\Partner\WC;

use WC_Admin_Notices;

if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

class QP_WC_Notices extends QP_WC
{
    public function init_hooks()
    {
        
    }

    public static function admin_notice($name, $msg)
    {
        $adminNotice = new WC_Admin_Notices();
        $adminNotice->add_custom_notice($name,$msg);
        // $adminNotice->output_custom_notices();
    }
}
