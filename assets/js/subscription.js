jQuery(document).ready(function($) {
    console.log('Subscription JS loaded'); // Debug line

    $('.subscribe-button').on('click', function(e) {
        e.preventDefault();
        console.log('Button clicked'); // Debug line
        
        const button = $(this);
        const plan = button.data('plan');
        const price = button.data('price');

        console.log('Plan:', plan, 'Price:', price); // Debug line

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
                console.log('Success response:', response); // Debug line
                if (response.success) {
                    // Redirect to payment/confirmation page
                    window.location.href = response.data.redirect;
                } else {
                    alert(response.data.message || 'Subscription failed. Please try again.');
                    button.prop('disabled', false).text('Subscribe Now');
                }
            },
            error: function(xhr, status, error) {
                console.log('Error:', error); // Debug line
                alert('Connection error. Please try again.');
                button.prop('disabled', false).text('Subscribe Now');
            }
        });
    });
});
