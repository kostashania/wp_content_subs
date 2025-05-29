jQuery(document).ready(function($) {
    // Modal handling
    $('.modal .close').on('click', function() {
        $(this).closest('.modal').hide();
    });

    $(window).on('click', function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Edit subscription
    $('.action-button[data-action="edit"]').on('click', function() {
        const subscriptionId = $(this).data('subscription-id');
        $('#edit-subscription-id').val(subscriptionId);
        $('#edit-subscription-modal').show();
    });

    // Record payment
    $('.record-payment-button').on('click', function() {
        const subscriptionId = $(this).data('subscription-id');
        $('#payment-subscription-id').val(subscriptionId);
        $('#record-payment-modal').show();
    });

    // Handle edit subscription form submission
    $('#edit-subscription-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'update_subscription_status',
                nonce: akadimiesAdmin.nonce,
                subscription_id: $('#edit-subscription-id').val(),
                status: $('#subscription-status').val(),
                admin_notes: $('#admin-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || 'Update failed');
                }
            },
            error: function() {
                alert('Connection error');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });

    // Handle payment form submission
    $('#record-payment-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'record_manual_payment',
                nonce: akadimiesAdmin.nonce,
                subscription_id: $('#payment-subscription-id').val(),
                payment_method: $('#payment-method').val(),
                amount: $('#payment-amount').val(),
                payment_notes: $('#payment-notes').val()
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.receipt_id) {
                        alert('Payment recorded successfully. Receipt: ' + response.data.receipt_id);
                    }
                    location.reload();
                } else {
                    alert(response.data || 'Failed to record payment');
                }
            },
            error: function() {
                alert('Connection error');
            },
            complete: function() {
                submitButton.prop('disabled', false);
            }
        });
    });
});
