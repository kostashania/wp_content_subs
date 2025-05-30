jQuery(document).ready(function($) {
    console.log('Admin JS loaded');

    // Tab handling
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });

    // Modal handling
    $('.close').click(function() {
        $(this).closest('.modal').hide();
    });

    $(window).click(function(event) {
        if ($(event.target).hasClass('modal')) {
            $('.modal').hide();
        }
    });

    // Subscription Type Management
    $('.add-new-type').click(function() {
        $('#type_id').val('');
        $('#subscription-type-form')[0].reset();
        $('#subscription-type-modal').show();
    });

    $('.edit-type').click(function() {
        const typeId = $(this).data('id');
        
        $.ajax({
            url: akadimiesAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_subscription_type',
                type_id: typeId,
                nonce: akadimiesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    const type = response.data;
                    $('#type_id').val(typeId);
                    $('#type_name').val(type.name);
                    $('#type_price').val(type.price);
                    $('#type_duration').val(type.duration);
                    $('#type_description').val(type.description);
                    $('input[name="active"]').prop('checked', type.active);
                    $('#subscription-type-modal').show();
                } else {
                    alert(response.data.message || akadimiesAdmin.strings.error);
                }
            },
            error: function() {
                alert(akadimiesAdmin.strings.error);
            }
        });
    });

    $('.delete-type').click(function() {
        if (!confirm(akadimiesAdmin.strings.confirmDelete)) {
            return;
        }

        const button = $(this);
        const typeId = button.data('id');
        
        button.prop('disabled', true);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'delete_subscription_type',
                type_id: typeId,
                nonce: akadimiesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    button.closest('tr').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || akadimiesAdmin.strings.error);
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert(akadimiesAdmin.strings.error);
                button.prop('disabled', false);
            }
        });
    });

    $('.activate-type, .deactivate-type').click(function() {
        const button = $(this);
        const typeId = button.data('id');
        const activate = button.hasClass('activate-type');

        if (!activate && !confirm(akadimiesAdmin.strings.confirmDeactivate)) {
            return;
        }

        button.prop('disabled', true);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
            type: 'POST',
            data: {
                action: 'toggle_subscription_type',
                type_id: typeId,
                active: activate ? 1 : 0,
                nonce: akadimiesAdmin.nonce
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || akadimiesAdmin.strings.error);
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert(akadimiesAdmin.strings.error);
                button.prop('disabled', false);
            }
        });
    });

    $('#subscription-type-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitButton = form.find('button[type="submit"]');
        submitButton.prop('disabled', true);

        const formData = new FormData(this);
        formData.append('action', 'save_subscription_type');
        formData.append('nonce', akadimiesAdmin.nonce);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message || akadimiesAdmin.strings.error);
                    submitButton.prop('disabled', false);
                }
            },
            error: function() {
                alert(akadimiesAdmin.strings.error);
                submitButton.prop('disabled', false);
            }
        });
    });

    // Subscription Management
    $('.approve-subscription').click(function() {
        if (!confirm('Are you sure you want to approve this subscription?')) {
            return;
        }

        const button = $(this);
        const id = button.data('id');

        console.log('Processing approval for ID:', id);

        button.prop('disabled', true);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
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

    $('.reject-subscription').click(function() {
        const reason = prompt('Please enter rejection reason:');
        if (reason === null) {
            return;
        }

        const button = $(this);
        const id = button.data('id');

        button.prop('disabled', true);

        $.ajax({
            url: akadimiesAdmin.ajaxurl,
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

    $('.view-details').click(function() {
        const button = $(this);
        const id = button.data('id');
        
        $.ajax({
            url: akadimiesAdmin.ajaxurl,
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
