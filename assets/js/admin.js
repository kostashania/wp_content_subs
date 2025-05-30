jQuery(document).ready(function($) {
    console.log('Admin JS loaded');

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

        console.log('Processing approval for ID:', id);

        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'approve_subscription',
                subscription_id: id,
                nonce: akadimiesAdmin.nonce
            },
            success: function(response) {
                console.log('Response:', response);
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || 'Error updating subscription');
                    button.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.log('Error details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                button.prop('disabled', false);
                alert('Error updating subscription. Check console for details.');
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

        button.prop('disabled', true);

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'reject_subscription',
                subscription_id: id,
                notes: reason,
                nonce: akadimiesAdmin.nonce
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
                console.log('Error details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                button.prop('disabled', false);
                alert('Error updating subscription. Check console for details.');
            }
        });
    });

    // View subscription details
    $('.view-details').click(function() {
        const button = $(this);
        const id = button.data('id');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_subscription_details',
                subscription_id: id,
                nonce: akadimiesAdmin.nonce
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
                console.log('Error details:', {
                    status: status,
                    error: error,
                    response: xhr.responseText
                });
                alert('Error loading subscription details. Check console for details.');
            }
        });
    });
});
