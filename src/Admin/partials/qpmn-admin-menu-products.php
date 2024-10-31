<?php

use QPMN\Partner\Qpmn_i18n;

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<div class="wrap qpmn-admin qpmn-bootstrap">
    <div class="container">
        <h1><?php Qpmn_i18n::_e('Created products')?></h1>
        <div><?php Qpmn_i18n::_e('Click') ?> <a href="<?php echo admin_url("admin.php?page=qpmn_options&step=2"); ?>"><?php Qpmn_i18n::_e('here'); ?></a> <?php Qpmn_i18n::_e('to create default product') ?>.</div>
        <div id="poststuff">
            <div id="post-body" class="metabox-holder">
                <div id="post-body-content">
                    <div class="meta-box-sortables ui-sortable">
                        <form method="post">
                            <?php
                            $this->productObj->prepare_items();
                            $this->productObj->display(); ?>
                        </form>
                    </div>
                </div>
            </div>
            <br class="clear">
        </div>
    </div>
</div>