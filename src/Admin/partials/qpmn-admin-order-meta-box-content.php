<?php 
use QPMN\Partner\Qpmn_i18n;
$na = Qpmn_i18n::__('N/A');
?>
<div id="qppp-cgp-order-meta-box-content">
    <p><?php Qpmn_i18n::_e('Order') ?>#: <?php echo esc_html($data['CGPOrderNumber'] ?? $na); ?></p>
    <p><?php Qpmn_i18n::_e('Status') ?>: <?php echo esc_html($data['CGPOrderStatus'] ?? $na); ?></p>
    <p><?php Qpmn_i18n::_e('Subtotal') ?>: <?php echo esc_html($data['CGPOrderSubtotal'] ?? $na); ?></p>
    <p><?php Qpmn_i18n::_e('Shipping') ?>: <?php echo esc_html($data['CGPOrderShipping'] ?? $na); ?></p>
    <p><?php Qpmn_i18n::_e('Total') ?>: <?php echo esc_html($data['CGPOrderTotal'] ?? $na); ?></p>
    <p><?php Qpmn_i18n::_e('Last Sync at') ?> : <?php echo  esc_html($data['lastSyncDateTime'] ?? $na); ?></p>
</div>