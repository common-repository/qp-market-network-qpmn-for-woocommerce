<?php

use QPMN\Partner\Qpmn_i18n;

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div id="qppp-cgp-order-meta-box" class="wrap qpmn-admin">
    <?php
    //load content
    include_once plugin_dir_path(__FILE__) . 'qpmn-admin-order-meta-box-content.php';
    ?>
    <div id="qpmn-cgp-order-meta-box-update">
        <span class="spinner"></span>
    <?php
    if (current_user_can('manage_options')) {
        if ($data['displayCreateOrder'] ?? false) {
    ?>
            <a class="button button-primary button-large" data-order-id="<?php echo esc_attr($data['orderId']); ?>" id="qppp-create-cgp-order"><?php Qpmn_i18n::_e('Create Order'); ?></a>
            <a style="display: none;" class="button button-primary button-large" data-order-id="<?php echo esc_attr($data['orderId']); ?>" id="qppp-get-cgp-order"><?php Qpmn_i18n::_e('Update Order'); ?></a>
        <?php
        } else {
        ?>
            <a class="button button-primary button-large" data-order-id="<?php echo esc_attr($data['orderId']); ?>" id="qppp-get-cgp-order"><?php Qpmn_i18n::_e('Update Order'); ?></a>
    <?php
        }
    }
    ?>
    </div>
</div>