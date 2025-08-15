<?php
/**
 * Enhanced Company Database Cleanup
 * Clean both company records AND their individual databases
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// For local testing, use local credentials
$license_db_config = [
    'host' => 'localhost',
    'database' => 'vessel_license_master',
    'username' => 'license_admin', 
    'password' => 'master_license_key_2024'
];

// Override for production
if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'logicdock.org') {
    $license_db_config = [
        'host' => 'localhost',
        'database' => 'vessel_license_master',
        'username' => 'license_admin',
        'password' => 'Zhq4VNrT'
    ];
}

echo "<h2>Company Database Cleanup Tool</h2>";
echo "<style>
    .company-item { border: 1px solid #ccc; margin: 10px 0; padding: 10px; background: #f9f9f9; }
    .btn { padding: 5px 10px; margin: 5px; cursor: pointer; }
    .btn-danger { background: #dc3545; color: white; border: none; }
    .btn-warning { background: #ffc107; color: black; border: none; }
    .btn-info { background: #17a2b8; color: white; border: none; }
    .alert { padding: 10px; margin: 10px 0; border: 1px solid; }
    .alert-success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
    .alert-danger { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
    .alert-warning { background: #fff3cd; border-color: #ffeaa7; color: #856404; }
</style>";

try {
    // Connect to license database
    $license_conn = new mysqli(
        $license_db_config['host'],
        $license_db_config['username'],
        $license_db_config['password'],
        $license_db_config['database']
    );
    
    if ($license_conn->connect_error) {
        throw new Exception("License DB connection failed: " . $license_conn->connect_error);
    }
    
    echo "<div class='alert alert-success'>✅ Connected to license database successfully</div>";
    
    // Handle cleanup actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_company'])) {
            $company_id = intval($_POST['company_id']);
            
            // Get company details first
            $stmt = $license_conn->prepare("SELECT company_name, company_domain, database_name FROM companies WHERE id = ?");
            $stmt->bind_param("i", $company_id);
            $stmt->execute();
            $company = $stmt->get_result()->fetch_assoc();
            
            if ($company) {
                $success = true;
                $messages = [];
                
                // Step 1: Drop the company's individual database if it exists
                if (!empty($company['database_name'])) {
                    $db_name = $company['database_name'];
                    // Sanitize database name for security
                    if (preg_match('/^[a-zA-Z0-9_]+$/', $db_name)) {
                        $drop_result = $license_conn->query("DROP DATABASE IF EXISTS `$db_name`");
                        if ($drop_result) {
                            $messages[] = "✅ Dropped database: $db_name";
                        } else {
                            $messages[] = "⚠️ Could not drop database: $db_name (" . $license_conn->error . ")";
                            $success = false;
                        }
                    }
                }
                
                // Step 2: Delete the company record
                $stmt = $license_conn->prepare("DELETE FROM companies WHERE id = ?");
                $stmt->bind_param("i", $company_id);
                if ($stmt->execute()) {
                    $messages[] = "✅ Deleted company record: " . $company['company_name'];
                } else {
                    $messages[] = "❌ Failed to delete company record: " . $stmt->error;
                    $success = false;
                }
                
                $alert_class = $success ? 'alert-success' : 'alert-warning';
                echo "<div class='alert $alert_class'>";
                foreach ($messages as $message) {
                    echo "<p>$message</p>";
                }
                echo "</div>";
            }
        }
        
        if (isset($_POST['delete_all_test'])) {
            // Delete all companies with test-like names
            $test_patterns = ['test%', '%demo%', '%sample%', '%example%'];
            $deleted_total = 0;
            
            foreach ($test_patterns as $pattern) {
                $stmt = $license_conn->prepare("SELECT id, company_name, database_name FROM companies WHERE company_name LIKE ? OR company_domain LIKE ?");
                $stmt->bind_param("ss", $pattern, $pattern);
                $stmt->execute();
                $test_companies = $stmt->get_result();
                
                while ($company = $test_companies->fetch_assoc()) {
                    // Drop database if exists
                    if (!empty($company['database_name'])) {
                        $db_name = $company['database_name'];
                        if (preg_match('/^[a-zA-Z0-9_]+$/', $db_name)) {
                            $license_conn->query("DROP DATABASE IF EXISTS `$db_name`");
                        }
                    }
                    
                    // Delete company record
                    $delete_stmt = $license_conn->prepare("DELETE FROM companies WHERE id = ?");
                    $delete_stmt->bind_param("i", $company['id']);
                    if ($delete_stmt->execute()) {
                        $deleted_total++;
                    }
                }
            }
            
            if ($deleted_total > 0) {
                echo "<div class='alert alert-success'>✅ Deleted $deleted_total test companies and their databases</div>";
            } else {
                echo "<div class='alert alert-warning'>⚠️ No test companies found to delete</div>";
            }
        }
    }
    
    // Show existing companies
    $result = $license_conn->query("SELECT id, company_name, company_domain, database_name, subscription_status, created_at FROM companies ORDER BY created_at DESC");
    
    if ($result->num_rows > 0) {
        echo "<h3>Existing Companies (" . $result->num_rows . " total)</h3>";
        
        echo "<form method='post' style='margin-bottom: 20px;'>";
        echo "<button type='submit' name='delete_all_test' class='btn btn-warning' onclick='return confirm(\"Delete ALL test companies and their databases?\")'>🗑️ Delete All Test Companies</button>";
        echo "</form>";
        
        while ($row = $result->fetch_assoc()) {
            $is_test = (stripos($row['company_name'], 'test') !== false || 
                       stripos($row['company_domain'], 'test') !== false ||
                       stripos($row['company_name'], 'demo') !== false ||
                       stripos($row['company_name'], 'sample') !== false);
            
            $bg_color = $is_test ? '#ffe6e6' : '#f9f9f9';
            
            echo "<div class='company-item' style='background-color: $bg_color;'>";
            echo "<h4>" . htmlspecialchars($row['company_name']) . " " . ($is_test ? "⚠️ (TEST)" : "") . "</h4>";
            echo "<p><strong>Domain:</strong> " . htmlspecialchars($row['company_domain']) . "</p>";
            echo "<p><strong>Database:</strong> " . htmlspecialchars($row['database_name'] ?? 'Not set') . "</p>";
            echo "<p><strong>Status:</strong> " . htmlspecialchars($row['subscription_status']) . "</p>";
            echo "<p><strong>Created:</strong> " . $row['created_at'] . "</p>";
            echo "<p><strong>ID:</strong> " . $row['id'] . "</p>";
            
            echo "<form method='post' style='display: inline;'>";
            echo "<input type='hidden' name='company_id' value='" . $row['id'] . "'>";
            echo "<button type='submit' name='delete_company' class='btn btn-danger' onclick='return confirm(\"Delete this company and its database?\")'>🗑️ Delete Company & Database</button>";
            echo "</form>";
            
            echo "</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>No companies found in database</div>";
    }
    
    // Show available databases
    echo "<hr><h3>All Databases on Server</h3>";
    $db_result = $license_conn->query("SHOW DATABASES");
    echo "<ul>";
    while ($db = $db_result->fetch_array()) {
        $db_name = $db[0];
        $is_system = in_array($db_name, ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin', 'test']);
        $color = $is_system ? '#ccc' : ($db_name === 'vessel_license_master' ? '#e6ffe6' : '#fff');
        echo "<li style='background: $color; padding: 5px; margin: 2px;'>$db_name" . ($is_system ? " (system)" : "") . "</li>";
    }
    echo "</ul>";
    
    $license_conn->close();
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<hr>";
echo "<p><a href='simple_domain_test.php'>🔍 Test Domain Availability</a> | <a href='signup.php'>📝 Test Signup</a></p>";

?>
