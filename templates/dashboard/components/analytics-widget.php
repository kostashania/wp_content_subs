// File: /templates/dashboard/components/analytics-widget.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="dashboard-widget analytics-widget">
    <h3>Profile Analytics</h3>
    <div class="analytics-grid">
        <div class="stat-box">
            <span class="stat-label">Views This Month</span>
            <span class="stat-value"><?php echo $analytics['monthly_views']; ?></span>
            <span class="stat-trend <?php echo $analytics['view_trend'] > 0 ? 'positive' : 'negative'; ?>">
                <?php echo $analytics['view_trend']; ?>%
            </span>
        </div>
        
        <div class="stat-box">
            <span class="stat-label">Profile Interactions</span>
            <span class="stat-value"><?php echo $analytics['interactions']; ?></span>
        </div>

        <canvas id="viewsChart" width="400" height="200"></canvas>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('viewsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($analytics['dates']); ?>,
            datasets: [{
                label: 'Daily Views',
                data: <?php echo json_encode($analytics['daily_views']); ?>,
                borderColor: '#4CAF50',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>
