<?php
/**
 * Database Cleanup and Trial Management System
 * Handles expired trials and company database lifecycle
 */

require_once __DIR__ . '/../config_production.php';

class TrialManagement {
    private $conn;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
    }
    
    /**
     * Check for expired trials and handle them
     */
    public function processExpiredTrials() {
        $results = [
            'processed' => 0,
            'suspended' => 0,
            'reminded' => 0,
            'errors' => []
        ];
        
        // Find trials that expired more than 7 days ago
        $expired_query = "
            SELECT customer_id, company_name, admin_email, expires_at, status, database_name
            FROM customer_licenses 
            WHERE status = 'active' 
            AND plan = 'trial' 
            AND expires_at < DATE_SUB(NOW(), INTERVAL 7 DAY)
            ORDER BY expires_at ASC
        ";
        
        $stmt = $this->conn->prepare($expired_query);
        $stmt->execute();
        $expired_trials = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        foreach ($expired_trials as $trial) {
            try {
                $this->handleExpiredTrial($trial);
                $results['processed']++;
                
                // Determine action taken
                $days_expired = (time() - strtotime($trial['expires_at'])) / (24 * 3600);
                if ($days_expired > 30) {
                    $results['suspended']++;
                } else {
                    $results['reminded']++;
                }
                
            } catch (Exception $e) {
                $results['errors'][] = [
                    'customer_id' => $trial['customer_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
    
    /**
     * Handle individual expired trial
     */
    private function handleExpiredTrial($trial) {
        $days_expired = (time() - strtotime($trial['expires_at'])) / (24 * 3600);
        
        if ($days_expired > 30) {
            // Suspend account and backup database
            $this->suspendExpiredTrial($trial);
        } elseif ($days_expired > 14) {
            // Send final notice
            $this->sendFinalNotice($trial);
        } elseif ($days_expired > 7) {
            // Send reminder email
            $this->sendTrialExpiredReminder($trial);
        }
    }
    
    /**
     * Suspend trial and backup database
     */
    private function suspendExpiredTrial($trial) {
        // 1. Update license status
        $update_query = "UPDATE customer_licenses SET 
                        status = 'suspended_expired', 
                        suspended_at = NOW(),
                        notes = CONCAT(COALESCE(notes, ''), 'Trial expired and suspended on ', NOW())
                        WHERE customer_id = ?";
        $stmt = $this->conn->prepare($update_query);
        $stmt->bind_param('s', $trial['customer_id']);
        $stmt->execute();
        
        // 2. Create database backup
        $this->backupCustomerDatabase($trial);
        
        // 3. Archive database (don't delete immediately)
        $this->archiveCustomerDatabase($trial);
        
        // 4. Send suspension notification
        $this->sendSuspensionNotice($trial);
        
        // 5. Log the action
        $this->logTrialAction($trial['customer_id'], 'suspended_expired', [
            'reason' => 'Trial expired over 30 days ago',
            'database_backed_up' => true,
            'database_archived' => true
        ]);
    }
    
    /**
     * Create backup of customer database
     */
    private function backupCustomerDatabase($trial) {
        $database_name = $trial['database_name'] ?? $trial['customer_id'] . '_logicdoc';
        $backup_dir = '/var/backups/vessel_trials/';
        $backup_file = $backup_dir . $database_name . '_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Ensure backup directory exists
        if (!is_dir($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        // Create database dump
        $command = "mysqldump -u " . DB_USER . " -p" . DB_PASS . " " . escapeshellarg($database_name) . " > " . escapeshellarg($backup_file);
        exec($command, $output, $return_code);
        
        if ($return_code !== 0) {
            throw new Exception("Failed to backup database: " . implode("\n", $output));
        }
        
        // Compress backup
        exec("gzip " . escapeshellarg($backup_file));
        
        return $backup_file . '.gz';
    }
    
    /**
     * Archive customer database (rename with _archived suffix)
     */
    private function archiveCustomerDatabase($trial) {
        $database_name = $trial['database_name'] ?? $trial['customer_id'] . '_logicdoc';
        $archived_name = $database_name . '_archived_' . date('Ymd');
        
        // Rename database
        $rename_query = "RENAME TABLE `$database_name` TO `$archived_name`";
        $this->conn->query($rename_query);
        
        // Update license record with archived database name
        $update_query = "UPDATE customer_licenses SET 
                        archived_database_name = ?,
                        database_archived_at = NOW()
                        WHERE customer_id = ?";
        $stmt = $this->conn->prepare($update_query);
        $stmt->bind_param('ss', $archived_name, $trial['customer_id']);
        $stmt->execute();
    }
    
    /**
     * Send trial expiration reminder email
     */
    private function sendTrialExpiredReminder($trial) {
        $subject = "Your Vessel Logger Trial Has Expired - Upgrade Now";
        $message = $this->buildTrialExpiredEmail($trial, 'reminder');
        $this->sendEmail($trial['admin_email'], $subject, $message);
        
        $this->logTrialAction($trial['customer_id'], 'reminder_sent', [
            'type' => 'trial_expired',
            'days_expired' => (time() - strtotime($trial['expires_at'])) / (24 * 3600)
        ]);
    }
    
    /**
     * Send final notice before suspension
     */
    private function sendFinalNotice($trial) {
        $subject = "FINAL NOTICE: Vessel Logger Account Will Be Suspended";
        $message = $this->buildTrialExpiredEmail($trial, 'final_notice');
        $this->sendEmail($trial['admin_email'], $subject, $message);
        
        $this->logTrialAction($trial['customer_id'], 'final_notice_sent', [
            'suspension_date' => date('Y-m-d', strtotime('+16 days', strtotime($trial['expires_at'])))
        ]);
    }
    
    /**
     * Send suspension notification
     */
    private function sendSuspensionNotice($trial) {
        $subject = "Vessel Logger Account Suspended - Data Backup Available";
        $message = $this->buildSuspensionEmail($trial);
        $this->sendEmail($trial['admin_email'], $subject, $message);
    }
    
    /**
     * Build trial expired email content
     */
    private function buildTrialExpiredEmail($trial, $type) {
        $days_expired = ceil((time() - strtotime($trial['expires_at'])) / (24 * 3600));
        
        $message = "Dear " . htmlspecialchars($trial['company_name']) . " team,\n\n";
        
        if ($type === 'reminder') {
            $message .= "Your 30-day free trial of Vessel Logger expired {$days_expired} days ago. ";
            $message .= "We hope you found value in our vessel management platform!\n\n";
            $message .= "To continue using Vessel Logger and keep your data:\n";
            $message .= "• Upgrade to a paid plan starting at just $49/month\n";
            $message .= "• Keep all your logged data and vessel configurations\n";
            $message .= "• Access to all basic modules plus premium add-ons\n\n";
        } elseif ($type === 'final_notice') {
            $message .= "IMPORTANT: Your Vessel Logger account will be suspended in " . (30 - $days_expired) . " days.\n\n";
            $message .= "After suspension:\n";
            $message .= "• Your account will be deactivated\n";
            $message .= "• Data will be backed up and archived\n";
            $message .= "• Future reactivation may require data restoration fees\n\n";
            $message .= "Avoid suspension by upgrading now:\n";
        }
        
        $message .= "UPGRADE OPTIONS:\n";
        $message .= "• Basic Plan: $49/month - Perfect for small fleets\n";
        $message .= "• Professional: $149/month - Advanced analytics and features\n";
        $message .= "• Enterprise: $499/month - Unlimited vessels and priority support\n\n";
        
        $message .= "Upgrade now: https://vessellogger.com/upgrade?token=" . $trial['customer_id'] . "\n";
        $message .= "Questions? Reply to this email or call (555) 123-4567\n\n";
        $message .= "Thank you for trying Vessel Logger!\n";
        $message .= "The Vessel Logger Team";
        
        return $message;
    }
    
    /**
     * Build suspension notification email
     */
    private function buildSuspensionEmail($trial) {
        $message = "Dear " . htmlspecialchars($trial['company_name']) . " team,\n\n";
        $message .= "Your Vessel Logger trial account has been suspended due to expiration.\n\n";
        $message .= "WHAT HAPPENED:\n";
        $message .= "• Your 30-day trial expired over 30 days ago\n";
        $message .= "• Account access has been deactivated\n";
        $message .= "• All data has been safely backed up and archived\n\n";
        $message .= "REACTIVATION:\n";
        $message .= "• Upgrade to any paid plan to reactivate\n";
        $message .= "• We can restore your data from backup\n";
        $message .= "• Contact support for data restoration (fees may apply)\n\n";
        $message .= "We'd love to have you back! Contact us:\n";
        $message .= "• Email: support@vessellogger.com\n";
        $message .= "• Phone: (555) 123-4567\n\n";
        $message .= "Thank you,\n";
        $message .= "The Vessel Logger Team";
        
        return $message;
    }
    
    /**
     * Send email (placeholder - integrate with your email service)
     */
    private function sendEmail($to, $subject, $message) {
        // In production, integrate with SendGrid, Mailgun, etc.
        // For now, use PHP mail() or log to file
        
        $headers = "From: noreply@vessellogger.com\r\n";
        $headers .= "Reply-To: support@vessellogger.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
        
        // Log email instead of sending in development
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'to' => $to,
            'subject' => $subject,
            'message' => $message
        ];
        
        file_put_contents('/tmp/vessel_emails.log', json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
        
        // Uncomment for production:
        // return mail($to, $subject, $message, $headers);
        return true;
    }
    
    /**
     * Log trial management actions
     */
    private function logTrialAction($customer_id, $action, $details = []) {
        $log_query = "INSERT INTO trial_management_logs 
                     (customer_id, action, details, created_at) 
                     VALUES (?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($log_query);
        $details_json = json_encode($details);
        $stmt->bind_param('sss', $customer_id, $action, $details_json);
        $stmt->execute();
    }
    
    /**
     * Get trial management statistics
     */
    public function getTrialStats() {
        $stats = [];
        
        // Active trials
        $active_query = "SELECT COUNT(*) as count FROM customer_licenses WHERE status = 'active' AND plan = 'trial'";
        $stats['active_trials'] = $this->conn->query($active_query)->fetch_assoc()['count'];
        
        // Expired trials (not suspended yet)
        $expired_query = "SELECT COUNT(*) as count FROM customer_licenses WHERE status = 'active' AND plan = 'trial' AND expires_at < NOW()";
        $stats['expired_trials'] = $this->conn->query($expired_query)->fetch_assoc()['count'];
        
        // Suspended trials
        $suspended_query = "SELECT COUNT(*) as count FROM customer_licenses WHERE status = 'suspended_expired'";
        $stats['suspended_trials'] = $this->conn->query($suspended_query)->fetch_assoc()['count'];
        
        // Conversion rate
        $total_trials_query = "SELECT COUNT(*) as count FROM customer_licenses WHERE plan = 'trial' OR created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)";
        $converted_query = "SELECT COUNT(*) as count FROM customer_licenses WHERE plan != 'trial' AND status = 'active'";
        
        $total_trials = $this->conn->query($total_trials_query)->fetch_assoc()['count'];
        $converted = $this->conn->query($converted_query)->fetch_assoc()['count'];
        
        $stats['conversion_rate'] = $total_trials > 0 ? round(($converted / $total_trials) * 100, 2) : 0;
        
        return $stats;
    }
    
    /**
     * Manual database cleanup for very old suspended accounts
     */
    public function cleanupOldSuspendedAccounts($days_old = 365) {
        $cleanup_query = "
            SELECT customer_id, archived_database_name 
            FROM customer_licenses 
            WHERE status = 'suspended_expired' 
            AND suspended_at < DATE_SUB(NOW(), INTERVAL ? DAY)
        ";
        
        $stmt = $this->conn->prepare($cleanup_query);
        $stmt->bind_param('i', $days_old);
        $stmt->execute();
        $old_accounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $results = ['cleaned' => 0, 'errors' => []];
        
        foreach ($old_accounts as $account) {
            try {
                // Drop archived database
                if ($account['archived_database_name']) {
                    $drop_query = "DROP DATABASE IF EXISTS `" . $account['archived_database_name'] . "`";
                    $this->conn->query($drop_query);
                }
                
                // Mark as permanently deleted
                $update_query = "UPDATE customer_licenses SET 
                                status = 'permanently_deleted',
                                database_deleted_at = NOW()
                                WHERE customer_id = ?";
                $stmt = $this->conn->prepare($update_query);
                $stmt->bind_param('s', $account['customer_id']);
                $stmt->execute();
                
                $results['cleaned']++;
                
            } catch (Exception $e) {
                $results['errors'][] = [
                    'customer_id' => $account['customer_id'],
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}

// CLI script usage
if (php_sapi_name() === 'cli') {
    $trial_manager = new TrialManagement($conn);
    
    echo "Processing expired trials...\n";
    $results = $trial_manager->processExpiredTrials();
    
    echo "Results:\n";
    echo "- Processed: " . $results['processed'] . "\n";
    echo "- Suspended: " . $results['suspended'] . "\n";
    echo "- Reminded: " . $results['reminded'] . "\n";
    echo "- Errors: " . count($results['errors']) . "\n";
    
    if (!empty($results['errors'])) {
        echo "\nErrors:\n";
        foreach ($results['errors'] as $error) {
            echo "- " . $error['customer_id'] . ": " . $error['error'] . "\n";
        }
    }
    
    echo "\nTrial Statistics:\n";
    $stats = $trial_manager->getTrialStats();
    foreach ($stats as $key => $value) {
        echo "- " . ucwords(str_replace('_', ' ', $key)) . ": " . $value . "\n";
    }
}
?>
