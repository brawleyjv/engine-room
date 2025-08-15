<?php
/**
 * LogicDock Company Installation System
 * Automatically creates isolated company instances like WordPress installation
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

class CompanyInstaller {
    
    private $base_path;
    private $template_path;
    private $license_db_config;
    
    public function __construct() {
        $this->base_path = __DIR__;
        $this->template_path = $this->base_path . '/template';
        
        // Database config for local vs production
        $this->license_db_config = [
            'host' => 'localhost',
            'database' => 'vessel_license_master',
            'username' => 'license_admin',
            'password' => 'master_license_key_2024'
        ];
        
        // Override for production
        if (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'logicdock.org') {
            $this->license_db_config['password'] = 'Zhq4VNrT';
        }
    }
    
    /**
     * Install a new company instance
     */
    public function installCompany($company_data) {
        $company_domain = $company_data['company_domain'];
        $company_path = $this->base_path . '/companies/' . $company_domain;
        
        try {
            // Step 1: Validate company data
            $this->validateCompanyData($company_data);
            
            // Step 2: Create company directory structure
            $this->createDirectoryStructure($company_path);
            
            // Step 3: Copy template files
            $this->copyTemplateFiles($company_path);
            
            // Step 4: Create company database
            $db_credentials = $this->createCompanyDatabase($company_data);
            
            // Step 5: Create company-specific config
            $this->createCompanyConfig($company_path, $company_data, $db_credentials);
            
            // Step 6: Import database schema
            $this->importDatabaseSchema($db_credentials);
            
            // Step 7: Create admin user
            $this->createAdminUser($db_credentials, $company_data);
            
            // Step 8: Set proper permissions
            $this->setPermissions($company_path);
            
            // Step 9: Update licensing database
            $this->updateLicensingDatabase($company_data, $db_credentials);
            
            return [
                'success' => true,
                'company_url' => $this->getCompanyUrl($company_domain),
                'admin_username' => $company_data['admin_username'],
                'admin_password' => $company_data['admin_password'],
                'message' => "Company installation completed successfully"
            ];
            
        } catch (Exception $e) {
            // Cleanup on failure
            $this->cleanupFailedInstall($company_path, $company_data);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate company data before installation
     */
    private function validateCompanyData($company_data) {
        $required_fields = ['company_name', 'company_domain', 'admin_username', 'admin_password', 'admin_email'];
        
        foreach ($required_fields as $field) {
            if (empty($company_data[$field])) {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate company domain format
        if (!preg_match('/^[a-z0-9-]+$/', $company_data['company_domain'])) {
            throw new Exception("Company domain must contain only lowercase letters, numbers, and hyphens");
        }
        
        // Check if company already exists
        $company_path = $this->base_path . '/companies/' . $company_data['company_domain'];
        if (is_dir($company_path)) {
            throw new Exception("Company directory already exists");
        }
    }
    
    /**
     * Create the directory structure for a company
     */
    private function createDirectoryStructure($company_path) {
        $directories = [
            $company_path,
            $company_path . '/assets',
            $company_path . '/assets/css',
            $company_path . '/assets/js',
            $company_path . '/assets/images',
            $company_path . '/includes',
            $company_path . '/logs',
            $company_path . '/uploads',
            $company_path . '/backups'
        ];
        
        foreach ($directories as $dir) {
            if (!mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
    }
    
    /**
     * Copy template files to company directory
     */
    private function copyTemplateFiles($company_path) {
        $this->copyDirectory($this->template_path, $company_path, ['database']);
    }
    
    /**
     * Recursively copy directory contents
     */
    private function copyDirectory($source, $destination, $exclude_dirs = []) {
        if (!is_dir($source)) {
            throw new Exception("Template directory not found: $source");
        }
        
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $item) {
            $target = $destination . '/' . $iterator->getSubPathName();
            
            // Skip excluded directories
            $relative_path = $iterator->getSubPath();
            $skip = false;
            foreach ($exclude_dirs as $exclude) {
                if (strpos($relative_path, $exclude) === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip) continue;
            
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                $dir = dirname($target);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                if (!copy($item, $target)) {
                    throw new Exception("Failed to copy file: " . $item);
                }
            }
        }
    }
    
    /**
     * Create company database and user
     */
    private function createCompanyDatabase($company_data) {
        // Connect as admin to create database
        $admin_conn = new mysqli('localhost', 'root', '', '');
        
        if ($admin_conn->connect_error) {
            throw new Exception("Could not connect to MySQL as admin: " . $admin_conn->connect_error);
        }
        
        $db_name = 'vessel_' . str_replace('-', '_', $company_data['company_domain']);
        $db_user = 'user_' . substr(md5($company_data['company_domain']), 0, 8);
        $db_pass = $this->generateSecurePassword();
        
        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        if (!$admin_conn->query($sql)) {
            throw new Exception("Failed to create database: " . $admin_conn->error);
        }
        
        // Create user
        $sql = "CREATE USER IF NOT EXISTS '$db_user'@'localhost' IDENTIFIED BY '$db_pass'";
        if (!$admin_conn->query($sql)) {
            throw new Exception("Failed to create database user: " . $admin_conn->error);
        }
        
        // Grant permissions
        $sql = "GRANT ALL PRIVILEGES ON `$db_name`.* TO '$db_user'@'localhost'";
        if (!$admin_conn->query($sql)) {
            throw new Exception("Failed to grant permissions: " . $admin_conn->error);
        }
        
        $admin_conn->query("FLUSH PRIVILEGES");
        $admin_conn->close();
        
        return [
            'host' => 'localhost',
            'database' => $db_name,
            'username' => $db_user,
            'password' => $db_pass
        ];
    }
    
    /**
     * Create company-specific configuration file
     */
    private function createCompanyConfig($company_path, $company_data, $db_credentials) {
        $config_content = "<?php\n";
        $config_content .= "/**\n";
        $config_content .= " * Company Configuration: " . $company_data['company_name'] . "\n";
        $config_content .= " * Auto-generated on " . date('Y-m-d H:i:s') . "\n";
        $config_content .= " */\n\n";
        
        $config_content .= "// Company Information\n";
        $config_content .= "define('COMPANY_ID', " . ($company_data['company_id'] ?? 0) . ");\n";
        $config_content .= "define('COMPANY_NAME', '" . addslashes($company_data['company_name']) . "');\n";
        $config_content .= "define('COMPANY_DOMAIN', '{$company_data['company_domain']}');\n\n";
        
        $config_content .= "// Database Configuration\n";
        $config_content .= "define('DB_HOST', '{$db_credentials['host']}');\n";
        $config_content .= "define('DB_NAME', '{$db_credentials['database']}');\n";
        $config_content .= "define('DB_USER', '{$db_credentials['username']}');\n";
        $config_content .= "define('DB_PASS', '{$db_credentials['password']}');\n\n";
        
        $config_content .= "// Trial Information\n";
        $config_content .= "define('TRIAL_START', '" . date('Y-m-d') . "');\n";
        $config_content .= "define('TRIAL_END', '" . date('Y-m-d', strtotime('+30 days')) . "');\n\n";
        
        $config_content .= "// Base URLs\n";
        $config_content .= "define('COMPANY_URL', '" . $this->getCompanyUrl($company_data['company_domain']) . "');\n";
        $config_content .= "define('LICENSING_URL', '" . $this->getLicensingUrl() . "');\n\n";
        
        $config_content .= "?>";
        
        if (!file_put_contents($company_path . '/config.php', $config_content)) {
            throw new Exception("Failed to create company config file");
        }
    }
    
    /**
     * Import the base database schema
     */
    private function importDatabaseSchema($db_credentials) {
        $schema_file = $this->template_path . '/database/schema.sql';
        
        if (!file_exists($schema_file)) {
            throw new Exception("Database schema file not found: $schema_file");
        }
        
        $conn = new mysqli(
            $db_credentials['host'],
            $db_credentials['username'],
            $db_credentials['password'],
            $db_credentials['database']
        );
        
        if ($conn->connect_error) {
            throw new Exception("Could not connect to company database: " . $conn->connect_error);
        }
        
        $schema_sql = file_get_contents($schema_file);
        
        if ($conn->multi_query($schema_sql)) {
            do {
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->next_result());
        } else {
            throw new Exception("Failed to import database schema: " . $conn->error);
        }
        
        $conn->close();
    }
    
    /**
     * Create admin user in company database
     */
    private function createAdminUser($db_credentials, $company_data) {
        $conn = new mysqli(
            $db_credentials['host'],
            $db_credentials['username'],
            $db_credentials['password'],
            $db_credentials['database']
        );
        
        if ($conn->connect_error) {
            throw new Exception("Could not connect to company database for user creation");
        }
        
        // Use default credentials: admin / ChangePwd101
        $default_password = 'ChangePwd101';
        $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
        $admin_name = $company_data['admin_name'] ?? 'Administrator';
        $admin_username = 'admin';
        $must_change = 1;
        
        $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, full_name = ?, password_must_change = ? WHERE role = 'admin' LIMIT 1");
        $stmt->bind_param("ssssi", 
            $admin_username,
            $company_data['admin_email'],
            $hashed_password,
            $admin_name,
            $must_change
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create admin user: " . $stmt->error);
        }
        
        $conn->close();
    }
    
    /**
     * Set proper file permissions
     */
    private function setPermissions($company_path) {
        // Set directory permissions
        chmod($company_path, 0755);
        chmod($company_path . '/logs', 0777);
        chmod($company_path . '/uploads', 0777);
        chmod($company_path . '/backups', 0755);
        
        // Set file permissions
        $files = glob($company_path . '/*.php');
        foreach ($files as $file) {
            chmod($file, 0644);
        }
    }
    
    /**
     * Update licensing database with company info
     */
    private function updateLicensingDatabase($company_data, $db_credentials) {
        $license_conn = new mysqli(
            $this->license_db_config['host'],
            $this->license_db_config['username'],
            $this->license_db_config['password'],
            $this->license_db_config['database']
        );
        
        if ($license_conn->connect_error) {
            throw new Exception("Could not connect to licensing database");
        }
        
        $encrypted_pass = base64_encode($db_credentials['password']);
        
        // Check if company already exists in licensing DB
        $stmt = $license_conn->prepare("SELECT id FROM companies WHERE company_domain = ?");
        $stmt->bind_param("s", $company_data['company_domain']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing record
            $company = $result->fetch_assoc();
            $stmt = $license_conn->prepare("UPDATE companies SET database_name = ?, database_username = ?, database_password = ?, setup_completed = 1 WHERE id = ?");
            $stmt->bind_param("sssi", $db_credentials['database'], $db_credentials['username'], $encrypted_pass, $company['id']);
            $company_data['company_id'] = $company['id'];
        } else {
            // Insert new record
            $stmt = $license_conn->prepare("INSERT INTO companies (company_name, company_domain, database_name, database_username, database_password, trial_start_date, trial_end_date, setup_completed, contact_email) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)");
            $trial_start = date('Y-m-d');
            $trial_end = date('Y-m-d', strtotime('+30 days'));
            $stmt->bind_param("ssssssss", 
                $company_data['company_name'],
                $company_data['company_domain'],
                $db_credentials['database'],
                $db_credentials['username'],
                $encrypted_pass,
                $trial_start,
                $trial_end,
                $company_data['admin_email']
            );
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update licensing database: " . $stmt->error);
        }
        
        $license_conn->close();
    }
    
    /**
     * Generate a secure random password
     */
    private function generateSecurePassword($length = 16) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Get company URL
     */
    private function getCompanyUrl($company_domain) {
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host/companies/$company_domain/";
    }
    
    /**
     * Get licensing URL
     */
    private function getLicensingUrl() {
        $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host/licensing/";
    }
    
    /**
     * Cleanup failed installation
     */
    private function cleanupFailedInstall($company_path, $company_data) {
        // Remove company directory if created
        if (is_dir($company_path)) {
            $this->removeDirectory($company_path);
        }
        
        // Remove database and user if created
        try {
            $admin_conn = new mysqli('localhost', 'root', '', '');
            if (!$admin_conn->connect_error) {
                $db_name = 'vessel_' . str_replace('-', '_', $company_data['company_domain']);
                $db_user = 'user_' . substr(md5($company_data['company_domain']), 0, 8);
                
                $admin_conn->query("DROP DATABASE IF EXISTS `$db_name`");
                $admin_conn->query("DROP USER IF EXISTS '$db_user'@'localhost'");
                $admin_conn->close();
            }
        } catch (Exception $e) {
            // Cleanup errors are not critical
        }
    }
    
    /**
     * Recursively remove directory
     */
    private function removeDirectory($dir) {
        if (!is_dir($dir)) return;
        
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $path = $dir . '/' . $file;
                if (is_dir($path)) {
                    $this->removeDirectory($path);
                } else {
                    unlink($path);
                }
            }
        }
        rmdir($dir);
    }
}

// Test installation if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'company_installer.php') {
    echo "<h2>Company Installer Test</h2>";
    
    if (isset($_POST['install_test'])) {
        $installer = new CompanyInstaller();
        
        $company_data = [
            'company_name' => 'Test Marine Solutions',
            'company_domain' => 'test-marine-' . substr(md5(time()), 0, 8),
            'admin_username' => 'admin',
            'admin_password' => 'admin123',
            'admin_email' => 'admin@test-marine.com',
            'admin_name' => 'Test Administrator'
        ];
        
        echo "<h3>Installing Test Company...</h3>";
        echo "<p>Company Domain: " . $company_data['company_domain'] . "</p>";
        
        $result = $installer->installCompany($company_data);
        
        if ($result['success']) {
            echo "<div style='color: green; padding: 10px; background: #e6ffe6; border: 1px solid #4CAF50;'>";
            echo "<h4>✅ Installation Successful!</h4>";
            echo "<p><strong>Company URL:</strong> <a href='" . $result['company_url'] . "'>" . $result['company_url'] . "</a></p>";
            echo "<p><strong>Admin Username:</strong> " . $result['admin_username'] . "</p>";
            echo "<p><strong>Admin Password:</strong> " . $result['admin_password'] . "</p>";
            echo "</div>";
        } else {
            echo "<div style='color: red; padding: 10px; background: #ffe6e6; border: 1px solid #f44336;'>";
            echo "<h4>❌ Installation Failed</h4>";
            echo "<p><strong>Error:</strong> " . $result['error'] . "</p>";
            echo "</div>";
        }
    } else {
        echo "<form method='post'>";
        echo "<p>This will create a test company installation with all files, database, and configuration.</p>";
        echo "<button type='submit' name='install_test' style='background: #4CAF50; color: white; padding: 10px 20px; border: none; cursor: pointer;'>🚀 Install Test Company</button>";
        echo "</form>";
    }
}
?>
