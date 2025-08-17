<?php
/**
 * Create Sample Log Entries
 * Populate the log book with realistic marine engine room entries
 */

require_once 'test_db.php';
require_once 'log_helper.php';

try {
    $pdo = getTestDatabase();
    
    echo "<h2>Creating Sample Log Entries</h2>\n";
    
    // Today's entries
    $today = date('Y-m-d');
    
    // Sample entries throughout the day
    $sample_entries = [
        ['06:00:00', 'manual', null, 'Daily engine room inspection commenced. All systems operational.', 'Chief Engineer'],
        ['06:15:00', 'engine', 'port_main', 'Engine readings recorded - RPM: 1800, Oil Pressure: 45 PSI, Water Temp: 180°F, Total Hours: 15850.2', 'Marine Engineer'],
        ['06:15:00', 'engine', 'starboard_main', 'Engine readings recorded - RPM: 1795, Oil Pressure: 47 PSI, Water Temp: 178°F, Total Hours: 15845.8', 'Marine Engineer'],
        ['06:30:00', 'gearbox', 'port_main', 'Gearbox readings recorded - Oil Pressure: 28 PSI, Oil Temp: 165°F, Operating Hours: 12450.5', 'Marine Engineer'],
        ['06:30:00', 'gearbox', 'starboard_main', 'Gearbox readings recorded - Oil Pressure: 30 PSI, Oil Temp: 162°F, Operating Hours: 12448.2', 'Marine Engineer'],
        ['07:00:00', 'generator', 'port_gen', 'Generator readings recorded - Voltage: 480V, Frequency: 60.1 Hz, Load: 45%, Hours: 8750.3', 'Marine Engineer'],
        ['08:30:00', 'fluid', 'fuel', 'Fuel tank sounding taken - Port: 8,450 gallons, Starboard: 8,320 gallons', 'Marine Engineer'],
        ['10:15:00', 'maintenance', 'port_main', 'Engine maintenance - Changed fuel filter, inspected air intake', 'Chief Engineer'],
        ['12:00:00', 'manual', null, 'Noon position: 35°15\'N, 75°30\'W. All engine room systems normal.', 'Marine Engineer'],
        ['14:30:00', 'fluid', 'lube_oil', 'Lube oil added to port main engine (15 gallons)', 'Marine Engineer'],
        ['16:00:00', 'engine', 'port_main', 'Engine readings recorded - RPM: 1850, Oil Pressure: 46 PSI, Water Temp: 185°F, Total Hours: 15860.8', 'Marine Engineer'],
        ['18:00:00', 'manual', null, 'Evening rounds completed. Minor leak detected in cooling system - repair scheduled.', 'Marine Engineer'],
        ['20:00:00', 'generator', 'starboard_gen', 'Generator started for electrical load balancing', 'Marine Engineer'],
        ['22:00:00', 'manual', null, 'Night watch commenced. All systems stable and within normal parameters.', 'Night Engineer']
    ];
    
    foreach ($sample_entries as $entry) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO log_entries 
            (vessel_id, log_date, log_time, entry_type, equipment_id, log_entry, created_by, created_at) 
            VALUES (1, ?, ?, ?, ?, ?, ?, datetime('now', '-' || ? || ' minutes'))");
            
        // Add some random minutes to make timestamps more realistic
        $random_minutes = rand(0, 1440); // Random time offset for created_at
        
        $stmt->execute([
            $today,
            $entry[0],
            $entry[1],
            $entry[2],
            $entry[3],
            $entry[4],
            $random_minutes
        ]);
    }
    
    // Add some entries for yesterday
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    $yesterday_entries = [
        ['06:00:00', 'manual', null, 'Daily engine room inspection commenced. All systems operational.', 'Chief Engineer'],
        ['10:30:00', 'maintenance', 'center_main', 'Engine maintenance - Oil change completed, new filter installed', 'Chief Engineer'],
        ['14:15:00', 'fluid', 'fuel', 'Fuel bunkering operation - Added 5,000 gallons to port tank', 'Marine Engineer'],
        ['18:00:00', 'manual', null, 'Evening inspection complete. Fuel consumption: 180 gal/hr average', 'Marine Engineer']
    ];
    
    foreach ($yesterday_entries as $entry) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO log_entries 
            (vessel_id, log_date, log_time, entry_type, equipment_id, log_entry, created_by) 
            VALUES (1, ?, ?, ?, ?, ?, ?)");
            
        $stmt->execute([
            $yesterday,
            $entry[0],
            $entry[1],
            $entry[2],
            $entry[3],
            $entry[4]
        ]);
    }
    
    echo "<p>✅ Sample log entries created successfully!</p>\n";
    echo "<p>📅 Today's entries: " . count($sample_entries) . "</p>\n";
    echo "<p>📅 Yesterday's entries: " . count($yesterday_entries) . "</p>\n";
    echo "<p><a href='view_logs.php'>View Log Book →</a></p>\n";

} catch (Exception $e) {
    echo "<p>❌ Error creating sample entries: " . $e->getMessage() . "</p>\n";
}
?>
