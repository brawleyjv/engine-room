<?php
// Three Engine Support Migration Script
// Run this to add three-engine configuration support to existing vessels

require_once 'config.php';

echo "🚢 Adding Three Engine Support to Vessel Data Logger...\n\n";

// Check if EngineConfig column already exists
$check_sql = "SHOW COLUMNS FROM vessels LIKE 'EngineConfig'";
$result = $conn->query($check_sql);

if ($result->num_rows == 0) {
    echo "⚙️  Adding EngineConfig column to vessels table...\n";
    
    // Add EngineConfig column
    $alter_sql = "ALTER TABLE vessels ADD COLUMN EngineConfig ENUM('standard', 'three_engine') DEFAULT 'standard' AFTER VesselType";
    
    if ($conn->query($alter_sql) === TRUE) {
        echo "✅ EngineConfig column added successfully!\n";
        
        // Update existing vessels to standard configuration
        $update_sql = "UPDATE vessels SET EngineConfig = 'standard' WHERE EngineConfig IS NULL";
        if ($conn->query($update_sql) === TRUE) {
            echo "✅ Existing vessels set to standard configuration\n";
        } else {
            echo "❌ Error updating existing vessels: " . $conn->error . "\n";
        }
    } else {
        echo "❌ Error adding EngineConfig column: " . $conn->error . "\n";
        exit();
    }
} else {
    echo "✅ EngineConfig column already exists\n";
}

echo "\n🎯 Three Engine Support Installation Complete!\n\n";
echo "📋 What's New:\n";
echo "   • Vessels can now be configured for 2 or 3 main engines\n";
echo "   • New engine configuration: Port, Center Main, Starboard\n";
echo "   • All data entry forms now support center main engine\n";
echo "   • Viewing and graphing works with all three engines\n\n";

echo "🔧 Next Steps:\n";
echo "   1. Go to Manage Vessels to set up three-engine vessels\n";
echo "   2. Existing vessels remain as standard (Port/Starboard)\n";
echo "   3. New vessels can be created with three-engine configuration\n\n";

echo "🚀 Three-engine support is now active!\n";

$conn->close();
?>
