// File: /templates/dashboard/components/activity-feed.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="dashboard-widget activity-feed">
    <h3><?php _e('Recent Activity', 'akadimies'); ?></h3>
    
    <div class="activity-list">
        <?php foreach ($activities as $activity): ?>
            <div class="activity-item">
                <div class="activity-icon">
                    <i class="fas fa-<?php echo esc_attr($activity->icon); ?>"></i>
                </div>
                <div class="activity-content">
                    <div class="activity-header">
                        <span class="activity-type"><?php echo esc_html($activity->type); ?></span>
                        <span class="activity-time">
                            <?php echo human_time_diff(strtotime($activity->date), current_time('timestamp')); ?> ago
                        </span>
                    </div>
                    <div class="activity-description">
                        <?php echo wp_kses_post($activity->description); ?>
                    </div>
                    <?php if (!empty($activity->link)): ?>
                        <a href="<?php echo esc_url($activity->link); ?>" class="activity-link">
                            <?php _e('View Details', 'akadimies'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($activities)): ?>
        <p class="no-activity"><?php _e('No recent activity', 'akadimies'); ?></p>
    <?php endif; ?>
</div>
