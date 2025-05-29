jQuery(document).ready(function($) {
    // Modal handling
    $('.close').click(function() {
        $(this).closest('.modal').hide();
    });

    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Approve subscription
    $('.approve-subscription').click(function() {
        if (!confirm('Are you sure you want to approve this subscription?')) {
            return;
        }

        const button = $(this);
        const id = button.data('id');
        const nonce = button.data('nonce');

        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_subscription_status',
                subscription_id: id,
                status: 'active',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error updating subscription');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert('Connection error');
                button.prop('disabled', false);
            }
        });
    });

    // Reject subscription
    $('.reject-subscription').click(function() {
        const reason = prompt('Please enter rejection reason:');
        if (reason === null) {
            return;
        }

        const button = $(this);
        const id = button.data('id');
        const nonce = button.data('nonce');

        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_subscription_status',
                subscription_id: id,
                status: 'rejected',
                notes: reason,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error updating subscription');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert('Connection error');
                button.prop('disabled', false);
            }
        });
    });

    // Cancel subscription
    $('.cancel-subscription').click(function() {
        if (!confirm('Are you sure you want to cancel this subscription?')) {
            return;
        }

        const button = $(this);
        const id = button.data('id');
        const nonce = button.data('nonce');

        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'update_subscription_status',
                subscription_id: id,
                status: 'cancelled',
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error updating subscription');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert('Connection error');
                button.prop('disabled', false);
            }
        });
    });

    // View subscription details
    $('.view-details').click(function() {
        const id = $(this).data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_subscription_details',
                subscription_id: id
            },
            success: function(response) {
                if (response.success) {
                    $('#subscription-details-content').html(response.data.html);
                    $('#subscription-details-modal').show();
                } else {
                    alert('Error loading subscription details');
                }
            },
            error: function() {
                alert('Connection error');
            }
        });
    });
});
