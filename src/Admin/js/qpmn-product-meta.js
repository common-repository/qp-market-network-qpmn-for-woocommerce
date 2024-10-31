(function($) {
    'use strict';

    $(document).ready(function() {
        $('#qppp-cgp-order-meta-box').on('click', '#qppp-create-cgp-order:not(.disabled)', function(e) {
            var ele = $('#qppp-cgp-order-meta-box #qpmn-cgp-order-meta-box-update .spinner');
            ele.css('visibility', 'visible');
            $(this).addClass('disabled');
            $.ajax({
                type: "POST",
                url: qpmn_admin_post_obj.ajax_url + '/cgp/order',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', qpmn_admin_post_obj.nonce)
                },
                data: {
                    orderId: $('#qppp-create-cgp-order').data('orderId')
                },
                success: function(data) {
                    $('#qppp-cgp-order-meta-box-content').replaceWith(data.html);
                    $('#qppp-create-cgp-order').hide();
                    $('#qppp-get-cgp-order').show();
                    ele.css('visibility', 'hidden');
                    $(this).addClass('disabled');
                },
                error: function() {
                    ele.css('visibility', 'hidden');
                    $(this).addClass('disabled');
                }
            });

            return false;
        });

        $('#qppp-cgp-order-meta-box').on('click', '#qppp-get-cgp-order:not(.disabled)', function(e) {
            var ele = $('#qppp-cgp-order-meta-box #qpmn-cgp-order-meta-box-update .spinner');
            ele.css('visibility', 'visible');
            $(this).addClass('disabled');
            var orderId = $('#qppp-get-cgp-order').data('orderId');
            $.ajax({
                type: "GET",
                url: qpmn_admin_post_obj.ajax_url + '/cgp/order/' + orderId,
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', qpmn_admin_post_obj.nonce)
                },
                success: function(data) {
                    ele.css('visibility', 'hidden');
                    $(this).removeClass('disabled');
                    location.reload();
                },
                error: function() {
                    ele.css('visibility', 'hidden');
                    $(this).removeClass('disabled');
                    location.reload();
                }
            });
            return false;
        });

    });

})(jQuery);