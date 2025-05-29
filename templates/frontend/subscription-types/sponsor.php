// File: /templates/frontend/subscription-types/sponsor.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-plan sponsor-plan">
    <div class="plan-header">
        <h2>Sponsor Subscription</h2>
        <div class="price">
            <?php echo AkadimiesUtilities::format_price(get_option('sponsor_price')); ?> / month
        </div>
    </div>

    <div class="plan-features">
        <ul>
            <li>Brand profile page</li>
            <li>Advertisement placement</li>
            <li>Sponsorship opportunities</li>
            <li>Event promotion</li>
            <li>Analytics dashboard</li>
            <li>Direct contact with teams</li>
        </ul>
    </div>

    <div class="plan-footer">
        <button class="subscribe-button" data-plan="sponsor" data-price="<?php echo esc_attr(get_option('sponsor_price')); ?>">
            Subscribe Now
        </button>
    </div>
</div>
