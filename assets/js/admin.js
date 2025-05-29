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
        const nonce = akadimiesAdmin.nonce;

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
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                alert('Connection error: ' + error);
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
        const nonce = akadimiesAdmin.nonce;

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
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                alert('Connection error: ' + error);
                button.prop('disabled', false);
            }
        });
    });

    // View subscription details
    $('.view-details').click(function() {
        const button = $(this);
        const id = button.data('id');
        const nonce = akadimiesAdmin.nonce;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_subscription_details',
                subscription_id: id,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#subscription-details-content').html(response.data.html);
                    $('#subscription-details-modal').show();
                } else {
                    alert('Error loading subscription details: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', status, error);
                alert('Connection error: ' + error);
            }
        });
    });
});
