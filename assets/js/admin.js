// File: /assets/js/admin.js
jQuery(document).ready(function($) {
    // Dashboard charts
    if ($('#subscription-chart').length) {
        new Chart($('#subscription-chart'), {
            type: 'line',
            data: subscriptionData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Bulk actions
    $('#bulk-action-selector-top').on('change', function() {
        const action = $(this).val();
        if (action === 'delete') {
            if (!confirm('Are you sure you want to delete the selected subscriptions?')) {
                $(this).val('-1');
            }
        }
    });

    // Settings page
    $('.color-picker').wpColorPicker();
});
