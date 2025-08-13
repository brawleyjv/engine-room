<?php
/**
 * LogicDock Customer Activity Tracking System
 * Monitors trials, subscriptions, and customer database activity
 */

class LogicDockTracker {
    private $conn;
    private $logicdock_api_url;
    private $api_key;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->logicdock_api_url = 'https://api.logicdock.org/customer-events';
        $this->api_key = 'ld_api_key_your_secret_key_here';
    }
    
    /**
     * Track trial start event
     */
    public function trackTrialStarted($customer_data) {
        $event_data = [
            'event_type' => 'trial_started',
            'timestamp' => date('c'),
            'customer_id' => $customer_data['customer_id'],
            'company_name' => $customer_data['company_name'],
            'database_name' => $customer_data['database_name'],
            'database_prefix' => $customer_data['database_prefix'],
            'admin_email' => $customer_data['admin_email'],
            'company_type' => $customer_data['company_type'] ?? 'unknown',
            'selected_modules' => $customer_data['selected_modules'] ?? [],
            'trial_expires_at' => $customer_data['trial_expires_at'],
            'signup_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'installation_completed_at' => date('c')
        ];
        
        // Log to local database
        $this->logCustomerEvent($event_data);
        
        // Send to LogicDock API
        $this->sendToLogicDock($event_data);
        
        // Send internal notification
        $this->sendInternalNotification($event_data);
        
        return $event_data;
    }
    
    /**
     * Track subscription conversion
     */
    public function trackSubscriptionStarted($customer_id, $subscription_data) {
        // Get customer info
        $customer_query = "SELECT * FROM customer_licenses WHERE customer_id = ?";
        $stmt = $this->conn->prepare($customer_query);
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if (!$customer) {
            throw new Exception("Customer not found: " . $customer_id);
        }
        
        $event_data = [
            'event_type' => 'subscription_started',
            'timestamp' => date('c'),
            'customer_id' => $customer_id,
            'company_name' => $customer['company_name'],
            'database_name' => $customer['database_name'],
            'database_prefix' => $this->extractPrefix($customer['database_name']),
            'admin_email' => $customer['admin_email'],
            'plan' => $subscription_data['plan'],
            'monthly_amount' => $subscription_data['amount'],
            'payment_method' => $subscription_data['payment_method'] ?? 'stripe',
            'subscription_id' => $subscription_data['subscription_id'],
            'trial_converted' => $customer['plan'] === 'trial',
            'trial_duration_days' => $this->calculateTrialDuration($customer['created_at']),
            'selected_modules' => $this->getCustomerModules($customer_id),
            'conversion_source' => $subscription_data['source'] ?? 'direct'
        ];
        
        // Log to local database
        $this->logCustomerEvent($event_data);
        
        // Send to LogicDock API
        $this->sendToLogicDock($event_data);
        
        // Send internal notification
        $this->sendInternalNotification($event_data);
        
        return $event_data;
    }
    
    /**
     * Track database activity and usage
     */
    public function trackDatabaseActivity($customer_id, $activity_type, $details = []) {
        $customer_query = "SELECT * FROM customer_licenses WHERE customer_id = ?";
        $stmt = $this->conn->prepare($customer_query);
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        
        if (!$customer) return false;
        
        $event_data = [
            'event_type' => 'database_activity',
            'timestamp' => date('c'),
            'customer_id' => $customer_id,
            'company_name' => $customer['company_name'],
            'database_name' => $customer['database_name'],
            'activity_type' => $activity_type, // 'log_entry', 'user_login', 'vessel_added', etc.
            'details' => $details,
            'customer_status' => $customer['status'],
            'customer_plan' => $customer['plan']
        ];
        
        // Log to activity tracking table
        $this->logDatabaseActivity($event_data);
        
        // Send periodic summaries to LogicDock (not every activity)
        if ($this->shouldSendActivityUpdate($customer_id, $activity_type)) {
            $this->sendToLogicDock($event_data);
        }
        
        return $event_data;
    }
    
    /**
     * Get comprehensive customer dashboard for LogicDock
     */
    public function getCustomerDashboard() {
        $dashboard = [
            'timestamp' => date('c'),
            'total_customers' => $this->getTotalCustomers(),
            'active_trials' => $this->getActiveTrials(),
            'active_subscriptions' => $this->getActiveSubscriptions(),
            'trial_conversions' => $this->getTrialConversions(),
            'revenue_summary' => $this->getRevenueSummary(),
            'database_usage' => $this->getDatabaseUsage(),
            'recent_activities' => $this->getRecentActivities(50),
            'customer_list' => $this->getCustomerList()
        ];
        
        return $dashboard;
    }
    
    /**
     * Get detailed customer list with database info
     */
    private function getCustomerList() {
        $query = "
            SELECT 
                cl.customer_id,
                cl.company_name,
                cl.database_name,
                SUBSTRING_INDEX(cl.database_name, '_', 1) as database_prefix,
                cl.admin_email,
                cl.plan,
                cl.status,
                cl.created_at,
                cl.expires_at,
                cl.vessel_limit,
                cl.user_limit,
                COUNT(cm.module_code) as active_modules,
                SUM(md.monthly_price) as monthly_revenue,
                DATEDIFF(cl.expires_at, NOW()) as days_remaining
            FROM customer_licenses cl
            LEFT JOIN customer_modules cm ON cl.customer_id = cm.customer_id AND cm.status = 'active'
            LEFT JOIN module_definitions md ON cm.module_code = md.module_code
            GROUP BY cl.customer_id
            ORDER BY cl.created_at DESC
        ";
        
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Log customer events to local database
     */
    private function logCustomerEvent($event_data) {
        $log_query = "INSERT INTO logicdock_tracking 
                     (customer_id, event_type, event_data, created_at) 
                     VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($log_query);
        $event_json = json_encode($event_data);
        $stmt->bind_param('sss', 
            $event_data['customer_id'], 
            $event_data['event_type'], 
            $event_json
        );
        $stmt->execute();
    }
    
    /**
     * Log database activity
     */
    private function logDatabaseActivity($event_data) {
        $activity_query = "INSERT INTO customer_activity_logs 
                          (customer_id, activity_type, activity_data, created_at) 
                          VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($activity_query);
        $activity_json = json_encode($event_data);
        $stmt->bind_param('sss', 
            $event_data['customer_id'], 
            $event_data['activity_type'], 
            $activity_json
        );
        $stmt->execute();
    }
    
    /**
     * Send data to LogicDock API
     */
    private function sendToLogicDock($data) {
        $payload = [
            'api_key' => $this->api_key,
            'source' => 'vessel_logger_saas',
            'data' => $data
        ];
        
        $ch = curl_init($this->logicdock_api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->api_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log API responses
        $this->logApiResponse($data['event_type'], $http_code, $response);
        
        return $http_code === 200;
    }
    
    /**
     * Send internal notifications (email, Slack, etc.)
     */
    private function sendInternalNotification($event_data) {
        $notification_email = 'notifications@logicdock.com';
        
        switch ($event_data['event_type']) {
            case 'trial_started':
                $subject = "🆕 New Trial Started: " . $event_data['company_name'];
                $message = $this->buildTrialStartedNotification($event_data);
                break;
                
            case 'subscription_started':
                $subject = "💰 New Subscription: " . $event_data['company_name'];
                $message = $this->buildSubscriptionStartedNotification($event_data);
                break;
                
            default:
                return; // Don't send notifications for other events
        }
        
        // Send email notification
        $this->sendNotificationEmail($notification_email, $subject, $message);
        
        // Send to Slack if configured
        $this->sendSlackNotification($event_data);
    }
    
    /**
     * Build trial started notification
     */
    private function buildTrialStartedNotification($data) {
        $message = "NEW TRIAL STARTED\n\n";
        $message .= "Company: " . $data['company_name'] . "\n";
        $message .= "Database: " . $data['database_name'] . "\n";
        $message .= "Prefix: " . $data['database_prefix'] . "\n";
        $message .= "Admin: " . $data['admin_email'] . "\n";
        $message .= "Type: " . $data['company_type'] . "\n";
        $message .= "Modules: " . implode(', ', $data['selected_modules']) . "\n";
        $message .= "Expires: " . $data['trial_expires_at'] . "\n";
        $message .= "IP: " . $data['signup_ip'] . "\n\n";
        $message .= "Customer ID: " . $data['customer_id'] . "\n";
        $message .= "Signed up: " . $data['timestamp'];
        
        return $message;
    }
    
    /**
     * Build subscription started notification
     */
    private function buildSubscriptionStartedNotification($data) {
        $message = "NEW SUBSCRIPTION! 🎉\n\n";
        $message .= "Company: " . $data['company_name'] . "\n";
        $message .= "Database: " . $data['database_name'] . "\n";
        $message .= "Plan: " . strtoupper($data['plan']) . "\n";
        $message .= "Amount: $" . number_format($data['monthly_amount'], 2) . "/month\n";
        $message .= "Payment: " . $data['payment_method'] . "\n";
        $message .= "Modules: " . implode(', ', $data['selected_modules']) . "\n\n";
        
        if ($data['trial_converted']) {
            $message .= "✅ TRIAL CONVERTED after " . $data['trial_duration_days'] . " days\n";
        } else {
            $message .= "Direct signup (no trial)\n";
        }
        
        $message .= "\nSubscription ID: " . $data['subscription_id'] . "\n";
        $message .= "Customer ID: " . $data['customer_id'];
        
        return $message;
    }
    
    /**
     * Send Slack notification
     */
    private function sendSlackNotification($event_data) {
        $slack_webhook = 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK';
        
        if (empty($slack_webhook) || $slack_webhook === 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK') {
            return; // Slack not configured
        }
        
        $color = $event_data['event_type'] === 'subscription_started' ? 'good' : '#36a64f';
        $icon = $event_data['event_type'] === 'subscription_started' ? '💰' : '🆕';
        
        $payload = [
            'text' => $icon . ' Vessel Logger SaaS Activity',
            'attachments' => [[
                'color' => $color,
                'fields' => [
                    [
                        'title' => 'Company',
                        'value' => $event_data['company_name'],
                        'short' => true
                    ],
                    [
                        'title' => 'Database',
                        'value' => $event_data['database_name'],
                        'short' => true
                    ],
                    [
                        'title' => 'Event',
                        'value' => ucwords(str_replace('_', ' ', $event_data['event_type'])),
                        'short' => true
                    ],
                    [
                        'title' => 'Customer ID',
                        'value' => $event_data['customer_id'],
                        'short' => true
                    ]
                ]
            ]]
        ];
        
        $ch = curl_init($slack_webhook);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Helper methods for statistics
    private function getTotalCustomers() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM customer_licenses");
        return $result->fetch_assoc()['count'];
    }
    
    private function getActiveTrials() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM customer_licenses WHERE plan = 'trial' AND status = 'active'");
        return $result->fetch_assoc()['count'];
    }
    
    private function getActiveSubscriptions() {
        $result = $this->conn->query("SELECT COUNT(*) as count FROM customer_licenses WHERE plan != 'trial' AND status = 'active'");
        return $result->fetch_assoc()['count'];
    }
    
    private function getTrialConversions() {
        // Get conversions in the last 30 days
        $query = "SELECT COUNT(*) as count FROM customer_licenses 
                 WHERE plan != 'trial' AND status = 'active' 
                 AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = $this->conn->query($query);
        return $result->fetch_assoc()['count'];
    }
    
    private function getRevenueSummary() {
        $query = "
            SELECT 
                SUM(CASE WHEN cl.plan = 'basic' THEN 49 WHEN cl.plan = 'professional' THEN 149 WHEN cl.plan = 'enterprise' THEN 499 ELSE 0 END) as base_revenue,
                SUM(md.monthly_price) as module_revenue
            FROM customer_licenses cl
            LEFT JOIN customer_modules cm ON cl.customer_id = cm.customer_id AND cm.status = 'active'
            LEFT JOIN module_definitions md ON cm.module_code = md.module_code
            WHERE cl.status = 'active' AND cl.plan != 'trial'
        ";
        $result = $this->conn->query($query);
        $revenue = $result->fetch_assoc();
        
        return [
            'base_monthly' => $revenue['base_revenue'] ?? 0,
            'modules_monthly' => $revenue['module_revenue'] ?? 0,
            'total_monthly' => ($revenue['base_revenue'] ?? 0) + ($revenue['module_revenue'] ?? 0)
        ];
    }
    
    private function getDatabaseUsage() {
        $query = "
            SELECT 
                database_name,
                COUNT(*) as activity_count
            FROM customer_activity_logs cal
            JOIN customer_licenses cl ON cal.customer_id = cl.customer_id
            WHERE cal.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY database_name
            ORDER BY activity_count DESC
            LIMIT 10
        ";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    private function getRecentActivities($limit = 20) {
        $query = "SELECT * FROM logicdock_tracking ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    private function extractPrefix($database_name) {
        return explode('_', $database_name)[0];
    }
    
    private function calculateTrialDuration($created_at) {
        return floor((time() - strtotime($created_at)) / (24 * 3600));
    }
    
    private function getCustomerModules($customer_id) {
        $query = "SELECT module_code FROM customer_modules WHERE customer_id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[] = $row['module_code'];
        }
        return $modules;
    }
    
    private function shouldSendActivityUpdate($customer_id, $activity_type) {
        // Send activity updates hourly for active customers, daily for others
        $last_sent_query = "SELECT MAX(created_at) as last_sent FROM logicdock_tracking 
                           WHERE customer_id = ? AND event_type = 'database_activity'";
        $stmt = $this->conn->prepare($last_sent_query);
        $stmt->bind_param('s', $customer_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result['last_sent']) return true;
        
        $hours_since = (time() - strtotime($result['last_sent'])) / 3600;
        return $hours_since >= 1; // Send every hour
    }
    
    private function sendNotificationEmail($to, $subject, $message) {
        $headers = "From: alerts@vessellogger.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // In production, use a proper email service
        mail($to, $subject, $message, $headers);
    }
    
    private function logApiResponse($event_type, $http_code, $response) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event_type' => $event_type,
            'http_code' => $http_code,
            'response' => $response
        ];
        file_put_contents('/tmp/logicdock_api.log', json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>
