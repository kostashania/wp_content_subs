// File: /templates/emails/renewal-reminder.php
<?php if (!defined('ABSPATH')) exit; ?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Renewal Reminder</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Subscription Renewal Reminder</h1>
        
        <p>Dear {user_name},</p>
        
        <p>Your subscription will expire on {expiry_date}. To ensure uninterrupted access to your profile, 
           please renew your subscription.</p>
        
        <p><a href="{renewal_url}" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
            Renew Now
        </a></p>
        
        <p>Best regards,<br>The Akadimies Team</p>
    </div>
</body>
</html>
