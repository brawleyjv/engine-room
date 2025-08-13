<?php
/**
 * LogicDock Automated Notification Cron Job
 * 
 * This script should be run every 15 minutes via cron to:
 * 1. Send pending notifications to LogicDock
 * 2. Update daily statistics
 * 3. Check for expired trials
 * 4. Generate activity reports
 * 
 * Cron setup: 0,15,30,45 * * * * /usr/bin/php /path/to/your/project/logicdock_cron.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/logicdock_cron.log');

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/logicdock_tracker.php';

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

$log_file = __DIR__ . '/logs/logicdock_cron.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
    echo "[$timestamp] $message\n";
}

log_message("=== LogicDock Cron Job Started ===");

try {
    // Connect to master database
    $master_pdo = new PDO("mysql:host=$db_host;dbname=vessellogger_master", $db_user, $db_pass);
    $master_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    log_message("Connected to master database successfully");

    // Initialize tracker
    $tracker = new LogicDockTracker($master_pdo);

    // 1. Send pending notifications
    log_message("Checking for pending notifications...");
    $sent_count = $tracker->sendPendingNotifications();
    log_message("Sent $sent_count notifications to LogicDock");

    // 2. Update daily statistics
    log_message("Updating daily statistics...");
    updateDailyStatistics($master_pdo);
    log_message("Daily statistics updated");

    // 3. Check for trials expiring soon
    log_message("Checking for trials expiring soon...");
    $expiring_trials = checkExpiringTrials($master_pdo, $tracker);
    log_message("Found $expiring_trials trials expiring soon");

    // 4. Check for inactive customers
    log_message("Checking for inactive customers...");
    $inactive_customers = checkInactiveCustomers($master_pdo, $tracker);
    log_message("Found $inactive_customers inactive customers");

    // 5. Generate weekly summary (Mondays only)
    if (date('N') == 1 && date('H') == 9) { // Monday at 9 AM
        log_message("Generating weekly summary...");
        generateWeeklySummary($master_pdo, $tracker);
        log_message("Weekly summary generated and sent");
    }

    // 6. Cleanup old logs (keep last 30 days)
    cleanupOldLogs($master_pdo);

} catch (Exception $e) {
    log_message("ERROR: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    
    // Send error notification to LogicDock
    try {
        $tracker = new LogicDockTracker($master_pdo);
        $tracker->trackEvent('SYSTEM', 'cron_error', [
            'error_message' => $e->getMessage(),
            'script' => 'logicdock_cron.php',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $inner_e) {
        log_message("Failed to send error notification: " . $inner_e->getMessage());
    }
}

log_message("=== LogicDock Cron Job Completed ===\n");

function updateDailyStatistics($pdo) {
    $today = date('Y-m-d');
    
    // Get customer statistics
    $customer_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_customers,
            SUM(CASE WHEN plan = 'trial' AND status = 'active' AND expires_at > NOW() THEN 1 ELSE 0 END) as active_trials,
            SUM(CASE WHEN plan != 'trial' AND status = 'active' THEN 1 ELSE 0 END) as active_subscriptions,
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as new_trials_today,
            SUM(total_log_entries) as total_log_entries_all_time
        FROM customer_licenses
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Get revenue statistics
    $revenue_stats = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN DATE(processed_at) = CURDATE() THEN amount ELSE 0 END), 0) as daily_revenue,
            COALESCE(SUM(CASE WHEN transaction_type = 'subscription' AND processed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END), 0) as monthly_recurring_revenue
        FROM revenue_tracking
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Get activity count for today
    $activity_count = $pdo->query("
        SELECT COUNT(*) as count 
        FROM customer_activity_logs 
        WHERE DATE(created_at) = CURDATE()
    ")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
    
    // Calculate trial conversion rate (last 30 days)
    $conversion_stats = $pdo->query("
        SELECT 
            COUNT(CASE WHEN plan = 'trial' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as trials_started,
            COUNT(CASE WHEN plan != 'trial' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as conversions
        FROM customer_licenses
    ")->fetch(PDO::FETCH_ASSOC);
    
    $trial_conversions_today = 0;
    if ($conversion_stats['trials_started'] > 0) {
        $trial_conversions_today = round(($conversion_stats['conversions'] / $conversion_stats['trials_started']) * 100, 2);
    }
    
    // Insert or update daily statistics
    $sql = "INSERT INTO daily_statistics 
            (stat_date, total_customers, active_trials, active_subscriptions, new_trials_today, trial_conversions_today, daily_revenue, monthly_recurring_revenue, database_activity_count, total_log_entries_today)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            total_customers = VALUES(total_customers),
            active_trials = VALUES(active_trials),
            active_subscriptions = VALUES(active_subscriptions),
            new_trials_today = VALUES(new_trials_today),
            trial_conversions_today = VALUES(trial_conversions_today),
            daily_revenue = VALUES(daily_revenue),
            monthly_recurring_revenue = VALUES(monthly_recurring_revenue),
            database_activity_count = VALUES(database_activity_count),
            total_log_entries_today = VALUES(total_log_entries_today)";
    
    $pdo->prepare($sql)->execute([
        $today,
        $customer_stats['total_customers'],
        $customer_stats['active_trials'],
        $customer_stats['active_subscriptions'],
        $customer_stats['new_trials_today'],
        $trial_conversions_today,
        $revenue_stats['daily_revenue'],
        $revenue_stats['monthly_recurring_revenue'],
        $activity_count,
        $customer_stats['total_log_entries_all_time']
    ]);
}

function checkExpiringTrials($pdo, $tracker) {
    // Find trials expiring in 3 days
    $sql = "SELECT customer_id, company_name, admin_email, expires_at, database_name
            FROM customer_licenses 
            WHERE plan = 'trial' 
            AND status = 'active'
            AND expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 3 DAY)
            AND customer_id NOT IN (
                SELECT customer_id FROM logicdock_tracking 
                WHERE event_type = 'trial_expiring_soon' 
                AND DATE(created_at) = CURDATE()
            )";
    
    $expiring_trials = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($expiring_trials as $trial) {
        $days_remaining = ceil((strtotime($trial['expires_at']) - time()) / (60 * 60 * 24));
        
        $tracker->trackEvent($trial['customer_id'], 'trial_expiring_soon', [
            'company_name' => $trial['company_name'],
            'admin_email' => $trial['admin_email'],
            'days_remaining' => $days_remaining,
            'expires_at' => $trial['expires_at'],
            'database_name' => $trial['database_name']
        ]);
    }
    
    return count($expiring_trials);
}

function checkInactiveCustomers($pdo, $tracker) {
    // Find customers who haven't logged in for 7 days
    $sql = "SELECT customer_id, company_name, admin_email, last_activity_at, database_name
            FROM customer_licenses 
            WHERE status = 'active'
            AND (last_activity_at IS NULL OR last_activity_at < DATE_SUB(NOW(), INTERVAL 7 DAY))
            AND created_at < DATE_SUB(NOW(), INTERVAL 3 DAY)
            AND customer_id NOT IN (
                SELECT customer_id FROM logicdock_tracking 
                WHERE event_type = 'customer_inactive' 
                AND DATE(created_at) = CURDATE()
            )";
    
    $inactive_customers = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($inactive_customers as $customer) {
        $days_inactive = $customer['last_activity_at'] 
            ? ceil((time() - strtotime($customer['last_activity_at'])) / (60 * 60 * 24))
            : 'Never logged in';
        
        $tracker->trackEvent($customer['customer_id'], 'customer_inactive', [
            'company_name' => $customer['company_name'],
            'admin_email' => $customer['admin_email'],
            'days_inactive' => $days_inactive,
            'last_activity_at' => $customer['last_activity_at'],
            'database_name' => $customer['database_name']
        ]);
    }
    
    return count($inactive_customers);
}

function generateWeeklySummary($pdo, $tracker) {
    // Get weekly statistics
    $weekly_stats = $pdo->query("
        SELECT 
            COUNT(*) as total_customers,
            SUM(CASE WHEN plan = 'trial' AND status = 'active' THEN 1 ELSE 0 END) as active_trials,
            SUM(CASE WHEN plan != 'trial' AND status = 'active' THEN 1 ELSE 0 END) as paying_customers,
            SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as new_signups_week,
            AVG(total_log_entries) as avg_log_entries,
            SUM(total_logins) as total_logins_all_time
        FROM customer_licenses
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Get revenue for the week
    $weekly_revenue = $pdo->query("
        SELECT 
            SUM(amount) as weekly_revenue,
            COUNT(*) as transaction_count
        FROM revenue_tracking 
        WHERE processed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Get top active customers
    $top_customers = $pdo->query("
        SELECT company_name, total_log_entries, total_logins
        FROM customer_licenses 
        WHERE status = 'active'
        ORDER BY (total_log_entries + total_logins) DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $tracker->trackEvent('SYSTEM', 'weekly_summary', [
        'week_ending' => date('Y-m-d'),
        'statistics' => $weekly_stats,
        'revenue' => $weekly_revenue,
        'top_customers' => $top_customers
    ]);
}

function cleanupOldLogs($pdo) {
    // Remove activity logs older than 90 days
    $pdo->exec("DELETE FROM customer_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    
    // Remove old tracking records older than 180 days
    $pdo->exec("DELETE FROM logicdock_tracking WHERE created_at < DATE_SUB(NOW(), INTERVAL 180 DAY)");
    
    // Remove old API response logs older than 30 days
    $pdo->exec("DELETE FROM api_response_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Clean up old daily statistics (keep 1 year)
    $pdo->exec("DELETE FROM daily_statistics WHERE stat_date < DATE_SUB(CURDATE(), INTERVAL 365 DAY)");
}
?>
