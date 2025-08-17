<?php
/**
 * Towing Vessel Deployment Setup
 * Quick setup script to get the system ready for operational use
 */

require_once 'test_db.php';
require_once 'log_helper.php';

echo "<h2>🚢 Towing Vessel Engine Room System - Deployment Setup</h2>\n";

try {
    $pdo = getTestDatabase();
    
    // Add towing-specific log entry types
    echo "<h3>Setting up Towing Vessel Configurations...</h3>\n";
    
    // Update vessel info for towing operations
    $pdo->exec("DELETE FROM test_vessels");
    $pdo->exec("INSERT INTO test_vessels (name, company) VALUES 
        ('M/V [VESSEL NAME]', '[COMPANY NAME]')");
    
    // Add towing-specific equipment types to settings
    $towing_settings = [
        'winch_active' => '1',
        'deck_machinery_active' => '1',
        'fire_system_active' => '1',
        'ballast_system_active' => '1'
    ];
    
    foreach ($towing_settings as $key => $value) {
        $pdo->prepare("INSERT OR REPLACE INTO vessel_settings (setting_key, setting_value) VALUES (?, ?)")
             ->execute([$key, $value]);
    }
    
    // Create initial operational log entry
    createLogEntry($pdo, 'manual', null, 
        'Engine room management system deployed and operational. All systems configured for towing vessel operations.', 
        'Chief Engineer');
    
    echo "<p>✅ System configured for towing vessel operations</p>\n";
    echo "<p>✅ Initial log entry created</p>\n";
    
    // Display deployment checklist
    echo "<div style='background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; margin: 20px 0;'>\n";
    echo "<h3>🎯 Deployment Checklist for Live Operations:</h3>\n";
    echo "<ol style='line-height: 1.8;'>\n";
    echo "<li><strong>Vessel Info:</strong> Update vessel name and company in settings</li>\n";
    echo "<li><strong>Equipment Setup:</strong> Configure active engines/gearboxes for your specific vessel</li>\n";
    echo "<li><strong>Overhaul Intervals:</strong> Set realistic intervals based on manufacturer recommendations</li>\n";
    echo "<li><strong>Current Hours:</strong> Enter actual engine hours in management pages</li>\n";
    echo "<li><strong>User Training:</strong> Brief crew on log entry procedures and editing protocols</li>\n";
    echo "<li><strong>Backup Plan:</strong> Ensure regular database backups (copy .db files)</li>\n";
    echo "<li><strong>Access Control:</strong> Consider limiting edit access to senior personnel</li>\n";
    echo "</ol>\n";
    echo "</div>\n";
    
    echo "<div style='background: #d4edda; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745; margin: 20px 0;'>\n";
    echo "<h3>⚓ Towing Vessel Specific Features:</h3>\n";
    echo "<ul style='line-height: 1.8;'>\n";
    echo "<li><strong>24/7 Operations:</strong> Log entries timestamped for continuous operations</li>\n";
    echo "<li><strong>Watch System:</strong> Track entries by watch (day/night engineer attribution)</li>\n";
    echo "<li><strong>Equipment Focus:</strong> Emphasizes main engines and gearboxes critical for towing</li>\n";
    echo "<li><strong>Maintenance Tracking:</strong> Overhaul progress perfect for extended operations</li>\n";
    echo "<li><strong>Audit Trail:</strong> Full edit history for regulatory compliance</li>\n";
    echo "<li><strong>Print Ready:</strong> Generate physical log books as backup/official record</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<div style='background: #fff3cd; padding: 20px; border-radius: 8px; border-left: 4px solid #ffc107; margin: 20px 0;'>\n";
    echo "<h3>⚠️ Operational Notes:</h3>\n";
    echo "<ul style='line-height: 1.8;'>\n";
    echo "<li><strong>Data Backup:</strong> Copy the test_engine.db file regularly for backup</li>\n";
    echo "<li><strong>Multi-User:</strong> System handles multiple users - track who makes entries</li>\n";
    echo "<li><strong>Offline Capable:</strong> Works without internet connection</li>\n";
    echo "<li><strong>Mobile Friendly:</strong> Responsive design works on tablets/phones</li>\n";
    echo "</ul>\n";
    echo "</div>\n";
    
    echo "<p style='text-align: center; margin: 30px 0;'>\n";
    echo "<a href='index.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-weight: bold;'>🚀 Launch System Dashboard</a>\n";
    echo "</p>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Setup Error: " . $e->getMessage() . "</p>\n";
}
?>
