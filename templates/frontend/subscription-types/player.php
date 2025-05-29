// File: /templates/frontend/subscription-types/player.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-plan player-plan">
    <div class="plan-header">
        <h2>Player Subscription</h2>
        <div class="price">
            <?php echo AkadimiesUtilities::format_price(get_option('player_price')); ?> / month
        </div>
    </div>

    <div class="plan-features">
        <ul>
            <li>Personal profile page</li>
            <li>Performance statistics tracking</li>
            <li>Team connection features</li>
            <li>Match history</li>
            <li>Skill assessment tools</li>
        </ul>
    </div>

    <div class="plan-footer">
        <button class="subscribe-button" data-plan="player" data-price="<?php echo esc_attr(get_option('player_price')); ?>">
            Subscribe Now
        </button>
    </div>
</div>
