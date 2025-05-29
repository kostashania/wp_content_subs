<?php if (!defined('ABSPATH')) exit; ?>

<div class="subscription-plan player-plan">
    <div class="plan-header">
        <h2><?php _e('Player Subscription', 'akadimies'); ?></h2>
        <div class="price">
            <?php echo esc_html(get_option('akadimies_player_price', '29.99')); ?> € / <?php _e('month', 'akadimies'); ?>
        </div>
    </div>

    <div class="plan-features">
        <ul>
            <li><?php _e('Personal profile page', 'akadimies'); ?></li>
            <li><?php _e('Performance statistics tracking', 'akadimies'); ?></li>
            <li><?php _e('Team connection features', 'akadimies'); ?></li>
            <li><?php _e('Match history', 'akadimies'); ?></li>
            <li><?php _e('Skill assessment tools', 'akadimies'); ?></li>
        </ul>
    </div>

    <div class="plan-footer">
        <button type="button" class="subscribe-button" 
                data-plan="player" 
                data-price="<?php echo esc_attr(get_option('akadimies_player_price', '29.99')); ?>">
            <?php _e('Subscribe Now', 'akadimies'); ?>
        </button>
    </div>
</div>
