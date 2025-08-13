# LogicDock Customer Tracking System

## Overview

The LogicDock tracking system provides comprehensive monitoring and analytics for your Vessel Logger SaaS platform. It tracks customer trials, subscriptions, database activity, and provides real-time insights to help you grow your business.

## Key Features

### 🎯 Customer Lifecycle Tracking
- **Trial Management**: Track trial starts, conversions, and expirations
- **Subscription Events**: Monitor subscription starts, upgrades, cancellations
- **Database Activity**: Track customer usage and engagement
- **Payment Processing**: Monitor revenue and failed payments

### 📊 Real-Time Analytics
- **Dashboard**: Beautiful, real-time dashboard with key metrics
- **Activity Monitoring**: Track user logins, log entries, and feature usage
- **Revenue Tracking**: Monitor daily/monthly revenue and trends
- **Customer Health**: Identify inactive customers and at-risk accounts

### 🔔 Automated Notifications
- **API Integration**: Send events to LogicDock API endpoints
- **Email Alerts**: Automated email notifications for key events
- **Slack Integration**: Real-time notifications to your Slack channels
- **Webhook Support**: Custom webhook endpoints for third-party integrations

### 🤖 Automation
- **Cron Jobs**: Automated data collection and notification sending
- **Trial Cleanup**: Automatic cleanup of expired trial accounts
- **Statistics Generation**: Daily/weekly/monthly statistical reports
- **Health Monitoring**: Automated monitoring of system health

## Installation

### 1. Run the Setup Script

Visit your website and run the setup script:
```
https://yoursite.com/setup_logicdock_tracking.php
```

This will:
- Create all necessary database tables
- Set up module definitions
- Create a test customer
- Generate initial statistics

### 2. Configure API Credentials

Edit `logicdock_tracker.php` and update your credentials:

```php
private $logicdock_api_url = 'https://api.logicdock.com/vessel-logger';
private $logicdock_api_key = 'your-api-key-here';
private $slack_webhook_url = 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK';
private $notification_email = 'alerts@yourcompany.com';
```

### 3. Set Up Cron Job

Add this cron job to run every 15 minutes:
```bash
*/15 * * * * /usr/bin/php /path/to/your/project/logicdock_cron.php
```

### 4. Access the Dashboard

Visit the LogicDock dashboard:
```
https://yoursite.com/logicdock_dashboard.php?access_key=logicdock_admin_2024
```

## Database Schema

### Core Tables

#### `customer_licenses`
Stores customer information and subscription details:
- `customer_id` - Unique customer identifier (e.g., CBL001)
- `company_name` - Customer company name
- `admin_email` - Primary contact email
- `plan` - Current plan (trial, basic, professional, enterprise)
- `status` - Account status (active, suspended, canceled)
- `database_name` - Customer's database name
- `database_prefix` - Database prefix for isolation
- `created_at` - Account creation date
- `expires_at` - Trial/subscription expiration
- `last_activity_at` - Last customer activity
- `total_logins` - Total login count
- `total_log_entries` - Total log entries created

#### `logicdock_tracking`
Tracks all customer events:
- `customer_id` - Reference to customer
- `event_type` - Type of event (trial_started, subscription_started, etc.)
- `event_data` - JSON data with event details
- `sent_to_logicdock` - Whether notification was sent
- `created_at` - Event timestamp

#### `customer_activity_logs`
Detailed activity tracking:
- `customer_id` - Reference to customer
- `activity_type` - Type of activity (user_login, log_entry_created, etc.)
- `activity_data` - JSON data with activity details
- `ip_address` - User's IP address
- `user_agent` - Browser user agent
- `created_at` - Activity timestamp

#### `revenue_tracking`
Financial transaction tracking:
- `customer_id` - Reference to customer
- `transaction_type` - Type (subscription, module, upgrade, refund)
- `amount` - Transaction amount
- `currency` - Currency code (USD, EUR, etc.)
- `payment_method` - Payment method used
- `transaction_id` - External transaction ID
- `processed_at` - Transaction timestamp

#### `daily_statistics`
Daily aggregated statistics:
- `stat_date` - Date of statistics
- `total_customers` - Total customer count
- `active_trials` - Active trial count
- `active_subscriptions` - Paying customer count
- `new_trials_today` - New trials started today
- `daily_revenue` - Revenue for the day
- `monthly_recurring_revenue` - Current MRR

### Module Management

#### `module_definitions`
Available modules and pricing:
- `module_code` - Unique module identifier
- `module_name` - Display name
- `category` - basic or premium
- `monthly_price` - Module price per month
- `description` - Module description

#### `customer_modules`
Customer module subscriptions:
- `customer_id` - Reference to customer
- `module_code` - Reference to module
- `status` - Module status (active, suspended)
- `activated_at` - When module was activated

## Event Types

### Customer Lifecycle Events
- `trial_started` - Customer starts trial
- `trial_expiring_soon` - Trial expires in 3 days
- `subscription_started` - Customer upgrades to paid plan
- `subscription_canceled` - Customer cancels subscription
- `subscription_upgraded` - Customer upgrades plan
- `payment_failed` - Payment processing failed
- `account_suspended` - Account suspended for non-payment

### Activity Events
- `user_login` - Customer user logs in
- `log_entry_created` - New log entry added
- `vessel_added` - New vessel added to account
- `user_added` - New user added to account
- `report_generated` - Report generated
- `data_export` - Data exported
- `settings_changed` - Account settings modified
- `module_activated` - Premium module activated

### System Events
- `database_activity` - General database activity
- `customer_inactive` - Customer hasn't logged in for 7+ days
- `weekly_summary` - Weekly statistics summary
- `cron_error` - Automated task error

## API Integration

### LogicDock API Endpoints

The system can send data to various LogicDock endpoints:

```php
// Customer events
POST /api/customers/events
{
    "customer_id": "CBL001",
    "event_type": "trial_started",
    "company_name": "Canal Barge Line",
    "database_name": "CBL_vessellogger",
    "timestamp": "2024-01-15T10:30:00Z"
}

// Revenue events
POST /api/revenue/transactions
{
    "customer_id": "CBL001",
    "amount": 149.00,
    "currency": "USD",
    "transaction_type": "subscription",
    "timestamp": "2024-01-15T10:30:00Z"
}

// Activity summaries
POST /api/activity/summary
{
    "date": "2024-01-15",
    "total_customers": 45,
    "active_trials": 12,
    "paying_customers": 33,
    "daily_revenue": 1248.50
}
```

### Webhook Configuration

You can configure webhooks to send data to external systems:

```php
private $webhook_endpoints = [
    'customer_events' => 'https://yourapp.com/webhooks/customer-events',
    'revenue_events' => 'https://yourapp.com/webhooks/revenue',
    'daily_summary' => 'https://yourapp.com/webhooks/daily-summary'
];
```

## Notification Examples

### Slack Notifications

```json
{
    "channel": "#vessel-logger-alerts",
    "username": "LogicDock Bot",
    "icon_emoji": ":ship:",
    "text": "🎉 New Trial Started!",
    "attachments": [
        {
            "color": "good",
            "fields": [
                {
                    "title": "Company",
                    "value": "Canal Barge Line",
                    "short": true
                },
                {
                    "title": "Database",
                    "value": "CBL_vessellogger",
                    "short": true
                }
            ]
        }
    ]
}
```

### Email Notifications

- **Trial Started**: Welcome email with setup instructions
- **Trial Expiring**: Reminder to upgrade with discount code
- **Subscription Started**: Thank you and onboarding information
- **Payment Failed**: Payment retry instructions
- **Account Suspended**: Account restoration options

## Dashboard Features

### Real-Time Metrics
- Total customers
- Active trials vs paying customers
- Daily signups
- Revenue trends
- Customer activity levels

### Customer Management
- Customer list with status
- Activity history
- Revenue per customer
- Trial conversion tracking
- Database usage statistics

### Business Intelligence
- Trial-to-paid conversion rates
- Customer lifetime value
- Churn analysis
- Revenue forecasting
- Feature usage analytics

## Monitoring and Alerts

### Health Checks
- Database connectivity
- API endpoint availability
- Cron job execution status
- Notification delivery success

### Automated Alerts
- Failed payments
- Expired trials not converting
- Inactive customers
- System errors
- Unusual activity patterns

## Security

### Access Control
- Dashboard requires special access key
- Database credentials secured
- API keys encrypted
- Activity logging for audit trails

### Data Privacy
- Customer data anonymization options
- GDPR compliance features
- Data retention policies
- Secure data transmission

## Customization

### Adding New Event Types

1. Add the event type to the `logicdock_tracking` table enum:
```sql
ALTER TABLE logicdock_tracking 
MODIFY COLUMN event_type ENUM(..., 'your_new_event');
```

2. Create tracking code:
```php
$tracker->trackEvent($customer_id, 'your_new_event', [
    'custom_data' => 'value'
]);
```

3. Add notification handling in `LogicDockTracker::sendNotification()`

### Custom Modules

Add new modules to the system:
```sql
INSERT INTO module_definitions 
(module_code, module_name, category, monthly_price, description) 
VALUES ('weather_integration', 'Weather Integration', 'premium', 14.99, 'Real-time weather data integration');
```

### Custom Analytics

Create custom views for specific analytics:
```sql
CREATE VIEW customer_engagement_score AS
SELECT 
    customer_id,
    company_name,
    (total_logins * 2 + total_log_entries * 0.1) as engagement_score
FROM customer_licenses;
```

## Troubleshooting

### Common Issues

1. **Notifications not sending**: Check API credentials and network connectivity
2. **Cron job not running**: Verify cron configuration and PHP path
3. **Dashboard not loading**: Check access key and database connection
4. **Missing customer data**: Verify installer is tracking customers properly

### Debug Mode

Enable debug logging:
```php
$tracker = new LogicDockTracker($pdo, true); // Enable debug mode
```

Check log files:
```bash
tail -f logs/logicdock_cron.log
tail -f logs/logicdock_debug.log
```

### Database Maintenance

Regular maintenance tasks:
```sql
-- Check table sizes
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'vessellogger_master';

-- Optimize tables
OPTIMIZE TABLE logicdock_tracking, customer_activity_logs;

-- Check for orphaned records
SELECT * FROM logicdock_tracking 
WHERE customer_id NOT IN (SELECT customer_id FROM customer_licenses);
```

## Support

For technical support or feature requests:
- Email: support@logicdock.com
- Documentation: https://docs.logicdock.com
- API Reference: https://api.logicdock.com/docs

## License

This LogicDock tracking system is proprietary software. All rights reserved.
