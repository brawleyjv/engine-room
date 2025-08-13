<?php
// Step 5: Database Creation & Installation
$error = '';
$success = '';

if (!isset($_SESSION['install_data'])) {
    header('Location: ?step=1');
    exit;
}

// Database configuration (you'll customize these)
$db_host = 'localhost';
$db_root_user = 'root';  // Or your admin user
$db_root_pass = '';      // Your admin password

if ($_POST && isset($_POST['create_database'])) {
    try {
        // Generate unique customer database name
        $company_slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $_SESSION['install_data']['company_name']));
        $customer_id = 'cust_' . substr(md5($_SESSION['install_data']['contact_email'] . time()), 0, 8);
        $customer_db_name = 'vessel_' . $customer_id;
        
        // Connect to MySQL as admin
        $admin_conn = new mysqli($db_host, $db_root_user, $db_root_pass);
        
        if ($admin_conn->connect_error) {
            throw new Exception("Database connection failed: " . $admin_conn->connect_error);
        }
        
        // Create customer database
        $sql = "CREATE DATABASE `$customer_db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$admin_conn->query($sql)) {
            throw new Exception("Database creation failed: " . $admin_conn->error);
        }
        
        // Create database user for this customer
        $db_user = $customer_id . '_user';
        $db_pass = bin2hex(random_bytes(12)); // Generate secure password
        
        $sql = "CREATE USER '$db_user'@'localhost' IDENTIFIED BY '$db_pass'";
        $admin_conn->query($sql);
        
        $sql = "GRANT ALL PRIVILEGES ON `$customer_db_name`.* TO '$db_user'@'localhost'";
        $admin_conn->query($sql);
        
        $admin_conn->query("FLUSH PRIVILEGES");
        $admin_conn->close();
        
        // Connect to new database and create tables
        $customer_conn = new mysqli($db_host, $db_user, $db_pass, $customer_db_name);
        
        if ($customer_conn->connect_error) {
            throw new Exception("Customer database connection failed");
        }
        
        // Read and execute database schema
        $schema_file = '../vessel_logger_structure.sql';
        if (file_exists($schema_file)) {
            $schema_sql = file_get_contents($schema_file);
            
            // Split into individual queries and execute
            $queries = array_filter(array_map('trim', explode(';', $schema_sql)));
            
            foreach ($queries as $query) {
                if (!empty($query)) {
                    if (!$customer_conn->query($query)) {
                        throw new Exception("Schema creation failed: " . $customer_conn->error);
                    }
                }
            }
        }
        
        // Add licensing and module tables specific to this customer
        $licensing_sql = "
        CREATE TABLE IF NOT EXISTS license_info (
            id INT PRIMARY KEY AUTO_INCREMENT,
            customer_id VARCHAR(50) UNIQUE,
            company_name VARCHAR(255),
            subscription_status ENUM('trial', 'active', 'suspended', 'cancelled') DEFAULT 'trial',
            trial_start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            trial_end_date DATETIME,
            vessel_limit INT DEFAULT 1,
            modules_enabled JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );
        
        CREATE TABLE IF NOT EXISTS subscription_modules (
            id INT PRIMARY KEY AUTO_INCREMENT,
            module_code VARCHAR(50),
            module_name VARCHAR(100),
            base_price DECIMAL(10,2),
            vessel_type_applicable JSON,
            is_addon BOOLEAN DEFAULT FALSE
        );
        
        INSERT INTO subscription_modules (module_code, module_name, base_price, vessel_type_applicable, is_addon) VALUES
        ('engine_basic', 'Engine Room - Basic', 0.00, '[\"all\"]', FALSE),
        ('wheelhouse_basic', 'Wheelhouse - Basic', 0.00, '[\"all\"]', FALSE),
        ('engine_extended', 'Engine Room - Extended', 25.00, '[\"all\"]', TRUE),
        ('wheelhouse_extended', 'Wheelhouse - Extended', 35.00, '[\"all\"]', TRUE),
        ('deck_operations', 'Deck Operations', 45.00, '[\"towboat\", \"workboat\", \"tug\"]', TRUE),
        ('fuel_management', 'Fuel Management', 30.00, '[\"all\"]', TRUE);
        ";
        
        if (!$customer_conn->multi_query($licensing_sql)) {
            throw new Exception("Licensing tables creation failed: " . $customer_conn->error);
        }
        
        // Wait for all queries to complete
        do {
            if ($result = $customer_conn->store_result()) {
                $result->free();
            }
        } while ($customer_conn->next_result());
        
        // Insert customer license record
        $trial_end = date('Y-m-d H:i:s', strtotime('+30 days'));
        $modules_enabled = json_encode(['engine_basic', 'wheelhouse_basic']);
        
        $stmt = $customer_conn->prepare("
            INSERT INTO license_info 
            (customer_id, company_name, trial_end_date, modules_enabled) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param('ssss', $customer_id, $_SESSION['install_data']['company_name'], $trial_end, $modules_enabled);
        $stmt->execute();
        
        // Create admin user
        $admin_stmt = $customer_conn->prepare("
            INSERT INTO users 
            (Username, Email, PasswordHash, FirstName, LastName, role, IsAdmin, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
        ");
        $admin_username = $_SESSION['install_data']['admin_email'];
        $admin_email = $_SESSION['install_data']['admin_email'];
        $admin_password = $_SESSION['install_data']['admin_password'];
        $admin_name_parts = explode(' ', $_SESSION['install_data']['admin_name'], 2);
        $admin_first_name = $admin_name_parts[0];
        $admin_last_name = $admin_name_parts[1] ?? '';
        $admin_role = $_SESSION['install_data']['admin_role'];
        
        $admin_stmt->bind_param('ssssss', $admin_username, $admin_email, $admin_password, $admin_first_name, $admin_last_name, $admin_role);
        $admin_stmt->execute();
        $admin_user_id = $customer_conn->insert_id;
        
        // Create standardized support/IT user for LogicDock team access
        $support_password = 'VesselSupport2024!' . $customer_id; // Unique per customer
        $support_password_hash = password_hash($support_password, PASSWORD_DEFAULT);
        
        $support_stmt = $customer_conn->prepare("
            INSERT INTO users 
            (Username, Email, PasswordHash, FirstName, LastName, role, IsAdmin, is_support_user, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, 1, 1, CURRENT_TIMESTAMP)
        ");
        $support_username = 'logicdock_support';
        $support_email = 'support@logicdock.org';
        $support_first_name = 'LogicDock';
        $support_last_name = 'Support Team';
        $support_role = 'admin'; // Full access for troubleshooting
        
        $support_stmt->bind_param('ssssss', $support_username, $support_email, $support_password_hash, $support_first_name, $support_last_name, $support_role);
        $support_stmt->execute();
        
        // Store support credentials for LogicDock team reference
        $_SESSION['install_data']['support_username'] = $support_username;
        $_SESSION['install_data']['support_password'] = $support_password;
        
        // Track customer creation with support credentials in LogicDock system
        try {
            require_once __DIR__ . '/../logicdock_tracker.php';
            $master_pdo = new PDO("mysql:host=$db_host;dbname=vessellogger_master", $db_root_user, $db_root_pass);
            $tracker = new LogicDockTracker($master_pdo);
            
            $tracker->trackEvent($customer_id, 'trial_started', [
                'company_name' => $_SESSION['install_data']['company_name'],
                'admin_email' => $_SESSION['install_data']['admin_email'],
                'database_name' => $customer_db_name,
                'database_user' => $db_user,
                'support_username' => $support_username,
                'support_password' => $support_password,
                'trial_end_date' => $trial_end,
                'vessel_name' => $_SESSION['install_data']['vessel_name'],
                'installation_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Don't fail installation if tracking fails
            error_log("LogicDock tracking failed: " . $e->getMessage());
        }
        
        // Create primary vessel
        $vessel_stmt = $customer_conn->prepare("
            INSERT INTO vessels 
            (VesselName, VesselType, EngineConfig, Length, Beam, Draft, GrossTonnage, 
             HomePort, IMONumber, CallSign, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $vessel_name = $_SESSION['install_data']['vessel_name'];
        $vessel_type = $_SESSION['install_data']['vessel_type'];
        $engine_config = $_SESSION['install_data']['engine_config'];
        $vessel_length = $_SESSION['install_data']['vessel_length'] ?: null;
        $vessel_beam = $_SESSION['install_data']['vessel_beam'] ?: null;
        $vessel_draft = $_SESSION['install_data']['vessel_draft'] ?: null;
        $vessel_tonnage = $_SESSION['install_data']['vessel_tonnage'] ?: null;
        $home_port = $_SESSION['install_data']['home_port'] ?: null;
        $imo_number = $_SESSION['install_data']['imo_number'] ?: null;
        $call_sign = $_SESSION['install_data']['call_sign'] ?: null;
        
        $vessel_stmt->bind_param('sssdddssssi', 
            $vessel_name, $vessel_type, $engine_config, $vessel_length, $vessel_beam, 
            $vessel_draft, $vessel_tonnage, $home_port, $imo_number, $call_sign, $admin_user_id
        );
        $vessel_stmt->execute();
        $vessel_id = $customer_conn->insert_id;
        
        // Store vessel ID for later use
        $_SESSION['install_data']['vessel_id'] = $vessel_id;
        $_SESSION['install_data']['admin_user_id'] = $admin_user_id;
        
        $customer_conn->close();
        
        // Store database credentials for config file
        $_SESSION['install_data']['customer_id'] = $customer_id;
        $_SESSION['install_data']['db_name'] = $customer_db_name;
        $_SESSION['install_data']['db_user'] = $db_user;
        $_SESSION['install_data']['db_pass'] = $db_pass;
        $_SESSION['install_data']['trial_end'] = $trial_end;
        
        $success = "Database created successfully! Your customer ID is: <strong>$customer_id</strong>";
        
        // Auto-advance after successful creation
        echo "<script>setTimeout(function(){ window.location.href = '?step=6'; }, 2000);</script>";
        
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>

<h2>Database Creation</h2>
<p>We'll now create your dedicated database and initialize the vessel management system.</p>

<?php if ($error): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="success">
        <?php echo $success; ?>
        <p>Redirecting to module selection...</p>
    </div>
<?php else: ?>
    <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
        <h3>What happens next:</h3>
        <ul>
            <li>✅ Create isolated database for <strong><?php echo htmlspecialchars($_SESSION['install_data']['company_name']); ?></strong></li>
            <li>✅ Install vessel management tables and structure</li>
            <li>✅ Set up licensing and module system</li>
            <li>✅ Initialize 30-day trial period</li>
            <li>✅ Configure security and access controls</li>
        </ul>
        
        <p><strong>Note:</strong> Your data will be completely isolated from other customers.</p>
    </div>

    <form method="POST">
        <input type="hidden" name="create_database" value="1">
        <div style="margin-top: 20px;">
            <a href="?step=4" style="margin-right: 10px; text-decoration: none; color: #666;">← Back</a>
            <button type="submit" class="btn">Create Database & Initialize System</button>
        </div>
    </form>
<?php endif; ?>
