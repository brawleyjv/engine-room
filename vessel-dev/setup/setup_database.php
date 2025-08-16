<?php
// Database setup script - creates a fresh database with basic tables

try {
    $db = new PDO('sqlite:data/vessel.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Creating fresh database...\n";
    
    // Create vessels table for login
    $db->exec("
        CREATE TABLE vessels (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            vessel_name TEXT NOT NULL,
            company_name TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Insert a test vessel for login
    $stmt = $db->prepare("INSERT INTO vessels (vessel_name, company_name) VALUES (?, ?)");
    $stmt->execute(['Test Vessel', 'Test Company']);
    
    echo "✓ Created vessels table\n";
    echo "✓ Added test vessel: 'Test Vessel' / 'Test Company'\n";
    echo "\nDatabase setup complete!\n";
    echo "You can now login with:\n";
    echo "Vessel Name: Test Vessel\n";
    echo "Company Name: Test Company\n";
    
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
