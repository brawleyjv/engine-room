<?php
/**
 * Test script to initialize crew data
 */

// Define constant
if (!defined('VESSEL_LOGGER')) {
    define('VESSEL_LOGGER', true);
}

require_once 'vessel_registration.php';

echo "Initializing vessel database...\n";
if (initializeVesselDatabase()) {
    echo "Database initialized successfully!\n";
    
    // Check crew data
    try {
        $db_path = __DIR__ . '/vessel_data.sqlite';
        $pdo = new PDO('sqlite:' . $db_path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM crew_members");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        
        echo "Crew members in database: " . $count . "\n";
        
        if ($count > 0) {
            $stmt = $pdo->prepare("SELECT name, position, date_on, date_off FROM crew_members ORDER BY name");
            $stmt->execute();
            $crew = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\nCurrent crew:\n";
            foreach ($crew as $member) {
                echo "- {$member['name']} ({$member['position']}) - On: {$member['date_on']}";
                if ($member['date_off']) {
                    echo " | Off: {$member['date_off']}";
                }
                echo "\n";
            }
        }
    } catch (Exception $e) {
        echo "Error checking crew data: " . $e->getMessage() . "\n";
    }
    
} else {
    echo "Failed to initialize database!\n";
}
?>
