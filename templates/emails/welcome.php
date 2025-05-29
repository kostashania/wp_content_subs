// File: /templates/emails/welcome.php
<?php if (!defined('ABSPATH')) exit; ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Welcome to Akadimies</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Welcome to Akadimies!</h1>
        
        <p>Dear {user_name},</p>
        
        <p>Thank you for subscribing as a {subscription_type}. Your account has been successfully created.</p>
        
        <p>You can now access your profile and start customizing it:</p>
        
        <p><a href="{profile_url}" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Access Your Profile
        </a></p>
        
        <p>If you have any questions, please don't hesitate to contact us.</p>
        
        <p>Best regards,<br>The Akadimies Team</p>
    </div>
</body>
</html>
