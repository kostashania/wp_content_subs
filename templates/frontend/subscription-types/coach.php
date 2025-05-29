// File: /templates/frontend/subscription-types/coach.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-plan coach-plan">
    <div class="plan-header">
        <h2>Coach Subscription</h2>
        <div class="price">
            <?php echo AkadimiesUtilities::format_price(get_option('coach_price')); ?> / month
        </div>
    </div>

    <div class="plan-features">
        <ul>
            <li>Professional coach profile</li>
            <li>Team management tools</li>
            <li>Training program creator</li>
            <li>Performance analytics</li>
            <li>Direct messaging with players</li>
            <li>Certificate verification</li>
        </ul>
    </div>

    <div class="plan-footer">
        <button class="subscribe-button" data-plan="coach" data-price="<?php echo esc_attr(get_option('coach_price')); ?>">
            Subscribe Now
        </button>
    </div>
</div>
