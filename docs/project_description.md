Project Description: Akadimies Subscription Manager
Overview
The Akadimies Subscription Manager is a WordPress plugin designed to manage memberships for players, coaches, and sponsors on akadimies.eu. It provides a complete subscription system with PayPal integration, user profile management, and administrative controls.

Core Features
Subscription Management

Role-based subscriptions (Player, Coach, Sponsor)
PayPal payment integration
Automated subscription handling
Email notifications
Profile System

Custom profile pages for each member
Role-specific fields
Social media integration
Media upload capabilities
Administrative Features

Price management for each role
User subscription overview
Subscription status tracking
Revenue reporting
File Structure
code


akadimies-subscription/
├── assets/
│   ├── css/
│   │   ├── admin-style.css
│   │   ├── subscription-form.css
│   │   └── profile-style.css
│   ├── js/
│   │   ├── admin.js
│   │   ├── subscription.js
│   │   └── profile-editor.js
│   └── images/
│       └── icons/
├── includes/
│   ├── class-akadimies-subscription.php
│   ├── class-akadimies-profiles.php
│   ├── class-akadimies-payments.php
│   ├── class-akadimies-notifications.php
│   └── class-akadimies-admin.php
├── templates/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── settings.php
│   │   └── user-list.php
│   ├── emails/
│   │   ├── welcome.php
│   │   ├── renewal-reminder.php
│   │   └── expiration-notice.php
│   ├── frontend/
│   │   ├── subscription-form.php
│   │   ├── profile-edit.php
│   │   └── profile-view.php
│   └── shortcodes/
│       └── subscription-button.php
├── languages/
│   ├── akadimies-en_US.po
│   └── akadimies-en_US.mo
├── akadimies-subscription.php
├── uninstall.php
└── readme.txt
File Purposes
Core Files
akadimies-subscription.php

Main plugin file
Initializes the plugin
Defines constants
Loads dependencies
uninstall.php

Cleanup when plugin is uninstalled
Removes database tables
Deletes plugin options
Include Files
class-akadimies-subscription.php

Core subscription logic
User role management
Subscription status handling
class-akadimies-profiles.php

Profile creation and management
Custom post type registration
Profile field definitions
class-akadimies-payments.php

PayPal integration
Payment processing
Transaction logging
class-akadimies-notifications.php

Email notification system
Reminder scheduling
Communication templates
class-akadimies-admin.php

Admin interface
Settings management
User overview
Template Files
Admin Templates

dashboard.php: Main admin interface
settings.php: Plugin configuration
user-list.php: Subscription management
Email Templates

welcome.php: New user welcome
renewal-reminder.php: Subscription renewal
expiration-notice.php: Expiration alerts
Frontend Templates

subscription-form.php: Registration form
profile-edit.php: Profile editor
profile-view.php: Public profile display
Asset Files
CSS Files

Admin styling
Frontend forms
Profile layouts
Responsive design
JavaScript Files

Form handling
PayPal integration
AJAX requests
UI interactions
Database Structure
Main Subscription Table
sql


wp_akadimies_subscriptions
- id (Primary Key)
- user_id
- subscription_type
- status
- start_date
- end_date
- payment_id
- amount
WordPress Options
code


akadimies_player_price
akadimies_coach_price
akadimies_sponsor_price
akadimies_paypal_settings
Installation Requirements
WordPress 5.0+
PHP 7.4+
MySQL 5.6+
SSL Certificate (for PayPal)
Setup Process
Upload plugin files
Activate plugin
Configure PayPal credentials
Set subscription prices
Create necessary pages
Configure email settings