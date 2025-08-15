<?php
/**
 * Check and Clean Company Database
 * Shows existing companies and allows cleanup of test data
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Company Database Cleanup</h2>";

try {
    // Connect using the working credentials from our debugging
    $conn = new mysqli('localhost', 'license_admin', 'Zhq4VNrT', 'vessel_license_master');
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color: green;'>✅ Connected to database successfully</p>";
    
    // Check if cleanup is requested
    if (isset($_POST['cleanup']) && isset($_POST['company_ids'])) {
        $company_ids = $_POST['company_ids'];
        if (!empty($company_ids)) {
            $ids_placeholder = str_repeat('?,', count($company_ids) - 1) . '?';
            $delete_sql = "DELETE FROM companies WHERE id IN ($ids_placeholder)";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param(str_repeat('i', count($company_ids)), ...$company_ids);
            
            if ($stmt->execute()) {
                echo "<div class='alert alert-success'>✅ Deleted " . $stmt->affected_rows . " companies</div>";
            } else {
                echo "<div class='alert alert-danger'>❌ Error deleting companies: " . $stmt->error . "</div>";
            }
        }
    }
    
    // First, let's check what columns exist in the companies table
    $columns_result = $conn->query("DESCRIBE companies");
    echo "<h4>Available Columns in Companies Table:</h4>";
    echo "<ul>";
    while ($column = $columns_result->fetch_assoc()) {
        echo "<li>" . $column['Field'] . " (" . $column['Type'] . ")</li>";
    }
    echo "</ul>";
    
    // List all companies with available columns
    $sql = "SELECT id, company_name, company_domain, subscription_status, created_at FROM companies ORDER BY id DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<h3>All Companies in Database:</h3>";
        echo "<form method='post'>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>Select</th><th>ID</th><th>Company Name</th><th>Domain</th><th>Status</th><th>Created</th>";
        echo "</tr>";
        
        while ($company = $result->fetch_assoc()) {
            $is_test = (
                strpos($company['company_name'], 'test') !== false ||
                strpos($company['company_domain'], 'test') !== false ||
                $company['company_domain'] === 'logicdock'
            );
            
            $row_color = $is_test ? 'background: #fff3cd;' : '';
            
            echo "<tr style='$row_color'>";
            echo "<td><input type='checkbox' name='company_ids[]' value='" . $company['id'] . "'" . ($is_test ? ' checked' : '') . "></td>";
            echo "<td>" . htmlspecialchars($company['id']) . "</td>";
            echo "<td>" . htmlspecialchars($company['company_name']) . "</td>";
            echo "<td>" . htmlspecialchars($company['company_domain']) . "</td>";
            echo "<td>" . htmlspecialchars($company['subscription_status']) . "</td>";
            echo "<td>" . htmlspecialchars($company['created_at']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<div style='margin: 20px 0;'>";
        echo "<button type='submit' name='cleanup' onclick='return confirm(\"Are you sure you want to delete selected companies?\")' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px;'>Delete Selected Companies</button>";
        echo "</div>";
        echo "</form>";
        
        echo "<p><strong>Note:</strong> Companies highlighted in yellow appear to be test data.</p>";
        
    } else {
        echo "<p>No companies found in database.</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='signup.php'>Try Signup Again</a> | <a href='index.php'>Back to Home</a></p>";

// Add some basic styling
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 20px 0; }
    th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }
    .alert { padding: 15px; margin: 20px 0; border-radius: 5px; }
    .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
    .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
</style>";
?>
