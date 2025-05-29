// File: /assets/js/profile-editor.js
jQuery(document).ready(function($) {
    // Profile image upload preview
    $('#profile_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('.preview-image').attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Form validation
    $('#profile-edit-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = new FormData(this);
        
        $.ajax({
            url: akadimiesAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true);
                form.append('<div class="loading-spinner"></div>');
            },
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'Profile updated successfully');
                } else {
                    showNotification('error', response.data.message);
                }
            },
            error: function() {
                showNotification('error', 'An error occurred. Please try again.');
            },
            complete: function() {
                form.find('button[type="submit"]').prop('disabled', false);
                form.find('.loading-spinner').remove();
            }
        });
    });

    function showNotification(type, message) {
        const notification = $('<div>', {
            class: `notification notification-${type}`,
            text: message
        });

        $('body').append(notification);
        
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
