jQuery(document).ready(function($) {
    $('.subscribe-button').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const plan = button.data('plan');
        const price = button.data('price');

        // Disable button to prevent double submission
        button.prop('disabled', true);
        
        // Show loading state
        button.text('Processing...');

        // Make AJAX call to process subscription
        $.ajax({
            url: akadimiesAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'process_subscription',
                nonce: akadimiesAjax.nonce,
                plan: plan,
                price: price
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to payment/confirmation page
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data.message || 'Subscription failed. Please try again.');
                    button.prop('disabled', false).text('Subscribe Now');
                }
            },
            error: function() {
                alert('Connection error. Please try again.');
                button.prop('disabled', false).text('Subscribe Now');
            }
        });
    });
});
