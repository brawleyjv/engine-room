<?php
// vessel_setup.php - Configure single vessel details
define('VESSEL_LOGGER', true);

// Direct database connection for vessel setup
$db_path = __DIR__ . '/vessel_data.sqlite';

// Connect to database
try {
    $pdo = new PDO("sqlite:$db_path");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ensure vessels table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS vessels (
            VesselID INTEGER PRIMARY KEY DEFAULT 1,
            VesselName TEXT NOT NULL DEFAULT 'My Vessel',
            VesselType TEXT DEFAULT 'Towboat',
            EngineConfig TEXT DEFAULT 'standard' CHECK(EngineConfig IN ('standard', 'three_engine')),
            Owner TEXT DEFAULT 'Vessel Owner',
            YearBuilt INTEGER DEFAULT 2020,
            Length REAL DEFAULT 120.0,
            CreatedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            IsActive INTEGER DEFAULT 1,
            Notes TEXT DEFAULT 'Single vessel USB logger',
            RPMMin INTEGER DEFAULT 650,
            RPMMax INTEGER DEFAULT 1750,
            TempMin INTEGER DEFAULT 20,
            TempMax INTEGER DEFAULT 400,
            PressureMin INTEGER DEFAULT 20,
            PressureMax INTEGER DEFAULT 400,
            GenMin INTEGER DEFAULT 20,
            GenMax INTEGER DEFAULT 400
        )
    ");
    
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_POST && isset($_POST['update_vessel'])) {
    try {
        $stmt = $pdo->prepare("
            UPDATE vessels SET 
                VesselName = ?, 
                VesselType = ?, 
                EngineConfig = ?, 
                Owner = ?, 
                YearBuilt = ?, 
                Length = ?, 
                RPMMin = ?, 
                RPMMax = ?, 
                TempMin = ?, 
                TempMax = ?, 
                PressureMin = ?, 
                PressureMax = ?, 
                GenMin = ?, 
                GenMax = ?, 
                Notes = ?
            WHERE VesselID = 1
        ");
        
        $stmt->execute([
            $_POST['vessel_name'],
            $_POST['vessel_type'],
            $_POST['engine_config'],
            $_POST['owner'],
            $_POST['year_built'],
            $_POST['length'],
            $_POST['rpm_min'],
            $_POST['rpm_max'],
            $_POST['temp_min'],
            $_POST['temp_max'],
            $_POST['pressure_min'],
            $_POST['pressure_max'],
            $_POST['gen_min'],
            $_POST['gen_max'],
            $_POST['notes']
        ]);
        
        $success = "Vessel configuration updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating vessel: " . $e->getMessage();
    }
}

// Get current vessel configuration
$vessel = $pdo->query("SELECT * FROM vessels WHERE VesselID = 1")->fetch(PDO::FETCH_ASSOC);

// If no vessel exists, create default
if (!$vessel) {
    $pdo->exec("
        INSERT OR REPLACE INTO vessels 
        (VesselID, VesselName, VesselType, EngineConfig, Owner, YearBuilt, Length, Notes, RPMMin, RPMMax, TempMin, TempMax, PressureMin, PressureMax, GenMin, GenMax)
        VALUES 
        (1, 'My Vessel', 'Towboat', 'standard', 'Vessel Owner', 2020, 120.0, 'Single vessel USB logger', 650, 1750, 20, 400, 20, 400, 20, 400)
    ");
    $vessel = $pdo->query("SELECT * FROM vessels WHERE VesselID = 1")->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Vessel Configuration - <?php echo htmlspecialchars($vessel['VesselName']); ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-container { max-width: 800px; margin: 20px auto; padding: 20px; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { 
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; 
        }
        .form-row { display: flex; gap: 15px; }
        .form-row .form-group { flex: 1; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .nav-links { text-align: center; margin: 20px 0; }
        .nav-links a { margin: 0 10px; padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .nav-links a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>🚢 Vessel Configuration</h1>
        
        <?php if (isset($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label for="vessel_name">Vessel Name *</label>
                    <input type="text" id="vessel_name" name="vessel_name" 
                           value="<?php echo htmlspecialchars($vessel['VesselName']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="vessel_type">Vessel Type</label>
                    <select id="vessel_type" name="vessel_type">
                        <option value="Towboat" <?php echo $vessel['VesselType'] == 'Towboat' ? 'selected' : ''; ?>>Towboat</option>
                        <option value="Fishing Vessel" <?php echo $vessel['VesselType'] == 'Fishing Vessel' ? 'selected' : ''; ?>>Fishing Vessel</option>
                        <option value="Cargo Vessel" <?php echo $vessel['VesselType'] == 'Cargo Vessel' ? 'selected' : ''; ?>>Cargo Vessel</option>
                        <option value="Passenger Vessel" <?php echo $vessel['VesselType'] == 'Passenger Vessel' ? 'selected' : ''; ?>>Passenger Vessel</option>
                        <option value="Other" <?php echo $vessel['VesselType'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="engine_config">Engine Configuration *</label>
                    <select id="engine_config" name="engine_config" required>
                        <option value="standard" <?php echo $vessel['EngineConfig'] == 'standard' ? 'selected' : ''; ?>>Standard (Twin Screw)</option>
                        <option value="three_engine" <?php echo $vessel['EngineConfig'] == 'three_engine' ? 'selected' : ''; ?>>Three Engine (Triple Screw)</option>
                    </select>
                    <small>Standard: Port/Starboard engines. Three Engine: Adds Center Main engine.</small>
                </div>
                <div class="form-group">
                    <label for="owner">Owner</label>
                    <input type="text" id="owner" name="owner" 
                           value="<?php echo htmlspecialchars($vessel['Owner']); ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="year_built">Year Built</label>
                    <input type="number" id="year_built" name="year_built" min="1900" max="2030"
                           value="<?php echo $vessel['YearBuilt']; ?>">
                </div>
                <div class="form-group">
                    <label for="length">Length (feet)</label>
                    <input type="number" id="length" name="length" step="0.1" min="0"
                           value="<?php echo $vessel['Length']; ?>">
                </div>
            </div>
            
            <h3>⚙️ Operating Ranges</h3>
            <p><em>Set minimum and maximum values for data validation and dashboard displays.</em></p>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="rpm_min">RPM Min</label>
                    <input type="number" id="rpm_min" name="rpm_min" min="0" max="3000"
                           value="<?php echo $vessel['RPMMin']; ?>">
                </div>
                <div class="form-group">
                    <label for="rpm_max">RPM Max</label>
                    <input type="number" id="rpm_max" name="rpm_max" min="0" max="3000"
                           value="<?php echo $vessel['RPMMax']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="temp_min">Temperature Min (°F)</label>
                    <input type="number" id="temp_min" name="temp_min" min="0" max="500"
                           value="<?php echo $vessel['TempMin']; ?>">
                </div>
                <div class="form-group">
                    <label for="temp_max">Temperature Max (°F)</label>
                    <input type="number" id="temp_max" name="temp_max" min="0" max="500"
                           value="<?php echo $vessel['TempMax']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="pressure_min">Pressure Min (PSI)</label>
                    <input type="number" id="pressure_min" name="pressure_min" min="0" max="500"
                           value="<?php echo $vessel['PressureMin']; ?>">
                </div>
                <div class="form-group">
                    <label for="pressure_max">Pressure Max (PSI)</label>
                    <input type="number" id="pressure_max" name="pressure_max" min="0" max="500"
                           value="<?php echo $vessel['PressureMax']; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="gen_min">Generator Min (°F)</label>
                    <input type="number" id="gen_min" name="gen_min" min="0" max="500"
                           value="<?php echo $vessel['GenMin']; ?>">
                </div>
                <div class="form-group">
                    <label for="gen_max">Generator Max (°F)</label>
                    <input type="number" id="gen_max" name="gen_max" min="0" max="500"
                           value="<?php echo $vessel['GenMax']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($vessel['Notes']); ?></textarea>
            </div>
            
            <div class="form-group">
                <button type="submit" name="update_vessel" style="background: #28a745; color: white; padding: 12px 24px; border: none; border-radius: 4px; font-size: 16px; cursor: pointer;">
                    💾 Save Vessel Configuration
                </button>
            </div>
        </form>
        
        <div class="nav-links">
            <a href="engine_dashboard.php">📊 Engine Dashboard</a>
            <a href="verify_migration.php">🔍 Database Status</a>
            <a href="simple_login.php">🔐 Login System</a>
        </div>
    </div>
</body>
</html>
