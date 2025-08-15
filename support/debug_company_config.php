<?php
// Debug script to check company database configuration
$license_db_config = [
    'host' => 'localhost',
    'username' => 'license_admin',
    'password' => 'Zhq4VNrT',
    'database' => 'vessel_license_master'
];

try {
    $license_conn = new mysqli(
        $license_db_config['host'],
        $license_db_config['username'],
        $license_db_config['password'],
        $license_db_config['database']
    );
    
    if ($license_conn->connect_error) {
        throw new Exception("License database connection failed: " . $license_conn->connect_error);
    }
    
    echo "✓ Connected to license database successfully\n\n";
    
    // Check companies in the database
    $sql = "SELECT 
                id,
                company_name,
                company_domain,
                database_host,
                database_name,
                database_username,
                database_password,
                subscription_status,
                trial_start_date,
                trial_end_date
            FROM companies 
            WHERE company_domain = 'logicdock' OR company_name LIKE '%logicdock%'";
    
    $result = $license_conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "Found companies:\n";
        while ($company = $result->fetch_assoc()) {
            echo "ID: " . $company['id'] . "\n";
            echo "Name: " . $company['company_name'] . "\n";
            echo "Domain: " . $company['company_domain'] . "\n";
            echo "DB Host: " . ($company['database_host'] ?: 'NULL') . "\n";
            echo "DB Name: " . ($company['database_name'] ?: 'NULL') . "\n";
            echo "DB User: " . ($company['database_username'] ?: 'NULL') . "\n";
            echo "DB Password: " . ($company['database_password'] ?: 'NULL') . "\n";
            echo "Status: " . $company['subscription_status'] . "\n";
            echo "Trial Start: " . $company['trial_start_date'] . "\n";
            echo "Trial End: " . $company['trial_end_date'] . "\n";
            echo "---\n";
        }
    } else {
        echo "No companies found with domain 'logicdock'\n";
        
        // List all companies
        $sql = "SELECT id, company_name, company_domain FROM companies LIMIT 5";
        $result = $license_conn->query($sql);
        echo "\nAll companies (first 5):\n";
        while ($company = $result->fetch_assoc()) {
            echo "ID: " . $company['id'] . " - " . $company['company_name'] . " (" . $company['company_domain'] . ")\n";
        }
    }
    
    $license_conn->close();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
