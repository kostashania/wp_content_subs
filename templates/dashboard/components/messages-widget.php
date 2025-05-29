// File: /templates/dashboard/components/messages-widget.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="dashboard-widget messages-widget">
    <h3>Recent Messages</h3>
    
    <?php if (!empty($messages)): ?>
        <div class="messages-list">
            <?php foreach ($messages as $message): ?>
                <div class="message-item <?php echo $message->is_read ? 'read' : 'unread'; ?>">
                    <div class="message-header">
                        <img src="<?php echo get_avatar_url($message->sender_id); ?>" 
                             alt="<?php echo esc_attr($message->sender_name); ?>" 
                             class="sender-avatar">
                        <div class="message-meta">
                            <span class="sender-name"><?php echo esc_html($message->sender_name); ?></span>
                            <span class="message-date"><?php echo human_time_diff(strtotime($message->date)); ?> ago</span>
                        </div>
                    </div>
                    <div class="message-preview">
                        <?php echo wp_trim_words($message->content, 10); ?>
                    </div>
                    <a href="<?php echo esc_url($message->url); ?>" class="message-link">
                        View Message
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="<?php echo esc_url($inbox_url); ?>" class="view-all-link">
            View All Messages
        </a>
    <?php else: ?>
        <p class="no-messages">No recent messages</p>
    <?php endif; ?>
</div>
