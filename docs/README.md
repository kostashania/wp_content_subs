// File: /docs/README.md
# Akadimies Subscription Manager

## Overview
The Akadimies Subscription Manager is a WordPress plugin that manages memberships for players, coaches, and sponsors. It provides PayPal integration, profile management, and administrative controls.

## Installation
1. Upload the plugin files to `/wp-content/plugins/akadimies-subscription`
2. Activate the plugin through WordPress admin
3. Configure PayPal credentials in Settings
4. Set subscription prices for each role

## Configuration
```ini
# PayPal Configuration
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_SECRET=your_secret_key
PAYPAL_SANDBOX=true/false

# Email Configuration
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USERNAME=your_username
SMTP_PASSWORD=your_password
