// File: /templates/dashboard/user-dashboard.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="user-dashboard">
    <div class="dashboard-header">
        <h1>Welcome, <?php echo esc_html($user->display_name); ?></h1>
        <div class="subscription-status <?php echo esc_attr($subscription->status); ?>">
            <?php echo AkadimiesUtilities::get_subscription_status_label($subscription->status); ?>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="dashboard-card subscription-info">
            <h3>Subscription Details</h3>
            <ul>
                <li>Type: <?php echo esc_html(ucfirst($subscription->subscription_type)); ?></li>
                <li>Start Date: <?php echo date('F j, Y', strtotime($subscription->start_date)); ?></li>
                <li>Next Payment: <?php echo date('F j, Y', strtotime($subscription->next_payment_date)); ?></li>
                <li>Amount: <?php echo AkadimiesUtilities::format_price($subscription->amount); ?></li>
            </ul>
            <?php if ($subscription->status === 'active'): ?>
                <button class="button cancel-subscription">Cancel Subscription</button>
            <?php endif; ?>
        </div>

        <div class="dashboard-card profile-preview">
            <h3>Profile Preview</h3>
            <div class="profile-summary">
                <img src="<?php echo esc_url(get_avatar_url($user->ID)); ?>" alt="Profile Image">
                <div class="profile-stats">
                    <p>Profile Views: <?php echo $profile_stats['views']; ?></p>
                    <p>Profile Completion: <?php echo $profile_stats['completion']; ?>%</p>
                </div>
            </div>
            <a href="<?php echo esc_url($profile_url); ?>" class="button">Edit Profile</a>
        </div>
    </div>
</div>
