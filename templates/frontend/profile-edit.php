// File: /templates/frontend/profile-edit.php
<?php if (!defined('ABSPATH')) exit; ?>

<div class="profile-edit-container">
    <form id="profile-edit-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('profile_edit', 'profile_nonce'); ?>
        
        <div class="profile-section">
            <h3>Basic Information</h3>
            
            <div class="field-group">
                <label for="profile_image">Profile Image</label>
                <div class="image-upload-wrapper">
                    <img src="<?php echo esc_url($profile_image); ?>" class="preview-image">
                    <input type="file" name="profile_image" id="profile_image" accept="image/*">
                </div>
            </div>

            <div class="field-group">
                <label for="display_name">Display Name</label>
                <input type="text" name="display_name" id="display_name" 
                       value="<?php echo esc_attr($user->display_name); ?>" required>
            </div>

            <?php if ($subscription_type === 'player'): ?>
                <div class="field-group">
                    <label for="position">Position</label>
                    <input type="text" name="position" id="position" 
                           value="<?php echo esc_attr($profile_data['position']); ?>">
                </div>
                
                <div class="field-group">
                    <label for="age">Age</label>
                    <input type="number" name="age" id="age" 
                           value="<?php echo esc_attr($profile_data['age']); ?>">
                </div>
            <?php endif; ?>
        </div>

        <div class="profile-section">
            <h3>Social Media</h3>
            
            <div class="field-group">
                <label for="facebook">Facebook Profile</label>
                <input type="url" name="social[facebook]" id="facebook" 
                       value="<?php echo esc_url($profile_data['social']['facebook']); ?>">
            </div>

            <div class="field-group">
                <label for="twitter">Twitter Profile</label>
                <input type="url" name="social[twitter]" id="twitter" 
                       value="<?php echo esc_url($profile_data['social']['twitter']); ?>">
            </div>

            <div class="field-group">
                <label for="instagram">Instagram Profile</label>
                <input type="url" name="social[instagram]" id="instagram" 
                       value="<?php echo esc_url($profile_data['social']['instagram']); ?>">
            </div>
        </div>

        <button type="submit" class="submit-button">Save Changes</button>
    </form>
</div>
