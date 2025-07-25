<?php
require_once 'config.php';

echo "Creating multi-vessel database schema...\n";

// Create vessels table
$sql_vessels = "
CREATE TABLE IF NOT EXISTS vessels (
    VesselID INT AUTO_INCREMENT PRIMARY KEY,
    VesselName VARCHAR(100) NOT NULL UNIQUE,
    VesselType VARCHAR(50) DEFAULT 'Fishing Vessel',
    Owner VARCHAR(100),
    YearBuilt YEAR,
    Length DECIMAL(6,2),
    CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsActive BOOLEAN DEFAULT TRUE,
    Notes TEXT
)";

if ($conn->query($sql_vessels) === TRUE) {
    echo "✓ Vessels table created successfully\n";
} else {
    echo "Error creating vessels table: " . $conn->error . "\n";
}

// Add VesselID to existing tables
$tables_to_modify = ['mainengines', 'generators', 'gears'];

foreach ($tables_to_modify as $table) {
    // Check if VesselID column already exists
    $check_sql = "SHOW COLUMNS FROM $table LIKE 'VesselID'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Get the first column name (usually the primary key)
        $columns_sql = "SHOW COLUMNS FROM $table";
        $columns_result = $conn->query($columns_sql);
        $first_column = $columns_result->fetch_assoc();
        $first_column_name = $first_column['Field'];
        
        // Add VesselID column after the first column (primary key)
        $alter_sql = "ALTER TABLE $table ADD COLUMN VesselID INT NOT NULL DEFAULT 1 AFTER $first_column_name";
        if ($conn->query($alter_sql) === TRUE) {
            echo "✓ Added VesselID column to $table table\n";
        } else {
            echo "Error adding VesselID to $table: " . $conn->error . "\n";
        }
        
        // Add foreign key constraint
        $fk_sql = "ALTER TABLE $table ADD CONSTRAINT fk_{$table}_vessel 
                   FOREIGN KEY (VesselID) REFERENCES vessels(VesselID) ON DELETE CASCADE";
        if ($conn->query($fk_sql) === TRUE) {
            echo "✓ Added foreign key constraint to $table table\n";
        } else {
            echo "Warning: Could not add foreign key to $table: " . $conn->error . "\n";
        }
        
        // Add composite index for better performance
        $index_sql = "ALTER TABLE $table ADD INDEX idx_{$table}_vessel_date (VesselID, EntryDate)";
        if ($conn->query($index_sql) === TRUE) {
            echo "✓ Added performance index to $table table\n";
        } else {
            echo "Warning: Could not add index to $table: " . $conn->error . "\n";
        }
    } else {
        echo "✓ VesselID column already exists in $table table\n";
    }
}

// Insert default vessel if none exist
$count_sql = "SELECT COUNT(*) as count FROM vessels";
$result = $conn->query($count_sql);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    $insert_sql = "INSERT INTO vessels (VesselName, VesselType, CreatedDate) 
                   VALUES ('Default Vessel', 'Fishing Vessel', NOW())";
    if ($conn->query($insert_sql) === TRUE) {
        echo "✓ Default vessel created\n";
    } else {
        echo "Error creating default vessel: " . $conn->error . "\n";
    }
}

echo "\nMulti-vessel schema setup complete!\n";
echo "Next steps:\n";
echo "1. Access vessel management at: manage_vessels.php\n";
echo "2. All existing data is now associated with 'Default Vessel'\n";
echo "3. You can add new vessels and switch between them\n";

$conn->close();
?>
