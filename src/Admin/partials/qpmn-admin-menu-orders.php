<?php

use QPMN\Partner\Qpmn_i18n;

$nextSchedule = (new \QPMN\Partner\WC\Schedule\Order())->nextScheduleTime();


?>

<div class="wrap qpmn-admin">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    if ($nextSchedule) {
    ?>
        <div><?php Qpmn_i18n::_e('Next Update'); ?>: <?php echo esc_html($nextSchedule); ?></div>
    <?php
    }
    ?>
    <div class='qpmn-bootstrap'>
        <?php Qpmn_i18n::_e('Click') ?> <a href="<?php echo admin_url("admin.php?page=qpmn_options&step=3"); ?>"><?php Qpmn_i18n::_e('here'); ?></a> <?php Qpmn_i18n::_e('to update schedule interval') ?>.
        <div><a class="btn btn-success" href="<?php echo esc_url(QPMN_ENDPOINT); ?>/my-account/consolidate-orders"><?php Qpmn_i18n::_e('Combine your QPMN orders'); ?></a></div>
    </div>
    <div id="poststuff">
        <div id="qpmn-order-page" class="metabox-holder">
            <div id="qpmn-order-page-content">
                <div class="qpmn-order-page-wrap meta-box-sortables ui-sortable">

                    <form method="post">
                        <?php
                        $this->orderObj->prepare_items();
                        $this->orderObj->display(); ?>
                    </form>
                </div>
            </div>
        </div>
        <br class="clear">
    </div>
</div>