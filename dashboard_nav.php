<?php
// Dashboard Navigation Helper
// Include this in dashboard files to ensure consistent navigation

if (!defined('DASHBOARD_NAV_INCLUDED')) {
    define('DASHBOARD_NAV_INCLUDED', true);
    
    function render_dashboard_navigation($current_page = '') {
        $nav_items = [
            'office_dashboard.php' => ['🏢', 'Master Dashboard', 'Company overview and fleet management'],
            'vessel/engineroom/dashboard.php' => ['⚙️', 'Vessel Dashboard', 'Equipment status and operations'],
            'vessel/engineroom/add_log.php' => ['📝', 'Add Entry', 'Record new equipment readings'],
            'vessel/engineroom/view_logs.php' => ['📋', 'View Logs', 'Search and view all entries'],
            'manage_vessels.php' => ['🚢', 'Vessels', 'Manage fleet vessels'],
            'manage_users.php' => ['👥', 'Users', 'User management'],
            'vessel/engineroom/graph_logs.php' => ['📈', 'Reports', 'Analytics and trends']
        ];
        
        echo '<div class="nav-menu">';
        foreach ($nav_items as $page => $info) {
            $active_class = ($current_page === $page) ? ' class="active"' : '';
            echo "<a href=\"{$page}\"{$active_class}>{$info[0]} {$info[1]}</a>";
        }
        echo '</div>';
    }
    
    function get_dashboard_title($current_page = '') {
        $titles = [
            'office_dashboard.php' => '🏢 Master Dashboard',
            'vessel/engineroom/dashboard.php' => '⚙️ Vessel Dashboard',
            'vessel/engineroom/add_log.php' => '📝 Add Log Entry',
            'vessel/engineroom/view_logs.php' => '📋 View Logs',
            'manage_vessels.php' => '🚢 Manage Vessels',
            'manage_users.php' => '👥 Manage Users',
            'vessel/engineroom/graph_logs.php' => '📈 Reports & Analytics'
        ];
        
        return $titles[$current_page] ?? '🚢 Vessel Management';
    }
    
    function get_page_breadcrumb($current_page = '') {
        global $current_vessel;
        
        $breadcrumbs = ['<a href="../../office_dashboard.php">Master Dashboard</a>'];
        
        if ($current_page === 'vessel/engineroom/dashboard.php' && isset($current_vessel)) {
            $breadcrumbs[] = '<a href="dashboard.php">Vessel Dashboard</a>';
            $breadcrumbs[] = htmlspecialchars($current_vessel['VesselName']);
        } elseif ($current_page !== 'office_dashboard.php') {
            $page_names = [
                'vessel/engineroom/dashboard.php' => 'Vessel Dashboard',
                'vessel/engineroom/add_log.php' => 'Add Entry',
                'vessel/engineroom/view_logs.php' => 'View Logs',
                'manage_vessels.php' => 'Manage Vessels',
                'manage_users.php' => 'Manage Users',
                'vessel/engineroom/graph_logs.php' => 'Reports'
            ];
            
            if (isset($page_names[$current_page])) {
                $breadcrumbs[] = $page_names[$current_page];
            }
        }
        
        return implode(' → ', $breadcrumbs);
    }
    
    function show_quick_vessel_switch() {
        global $conn, $current_vessel;
        
        if (!$current_vessel) return '';
        
        // Get other vessels for quick switching
        $sql = "SELECT VesselID, VesselName FROM vessels WHERE IsActive = 1 AND VesselID != ? ORDER BY VesselName LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $current_vessel['VesselID']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo '<div class="quick-vessel-switch" style="background: rgba(255,255,255,0.1); padding: 0.5rem; border-radius: 8px; margin-bottom: 1rem;">';
            echo '<span style="color: white; font-size: 0.9rem;">Quick Switch: </span>';
            while ($vessel = $result->fetch_assoc()) {
                echo '<a href="switch_vessel.php?vessel_id=' . $vessel['VesselID'] . '&redirect=' . urlencode($_SERVER['PHP_SELF']) . '" ';
                echo 'style="color: white; text-decoration: none; padding: 0.25rem 0.5rem; margin: 0 0.25rem; background: rgba(255,255,255,0.2); border-radius: 4px; font-size: 0.8rem;">';
                echo htmlspecialchars($vessel['VesselName']) . '</a>';
            }
            echo '</div>';
        }
    }
    
    function render_subscription_status() {
        if (defined('SUBSCRIPTION_STATUS') && SUBSCRIPTION_STATUS === 'trial') {
            $trial_end = defined('TRIAL_END_DATE') ? TRIAL_END_DATE : null;
            if ($trial_end) {
                $days_remaining = ceil((strtotime($trial_end) - time()) / (60 * 60 * 24));
                $urgency_class = $days_remaining <= 3 ? 'danger' : ($days_remaining <= 7 ? 'warning' : 'info');
                
                echo "<div class=\"subscription-banner {$urgency_class}\" style=\"background: ";
                echo $days_remaining <= 3 ? '#e74c3c' : ($days_remaining <= 7 ? '#f39c12' : '#3498db');
                echo "; color: white; padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;\">";
                echo "<span>⏰ Trial: {$days_remaining} days remaining</span>";
                echo "<a href=\"subscription.php\" style=\"color: white; text-decoration: underline;\">Upgrade Now</a>";
                echo "</div>";
            }
        }
    }
}
?>
