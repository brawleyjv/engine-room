<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h2>Company Cleanup Tool</h2>";

$host = 'localhost';
$username = 'license_admin';
$password = 'master_license_key_2024';
$database = 'vessel_license_master';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle deletion
if (isset($_POST['delete_ids']) && !empty($_POST['delete_ids'])) {
    $ids_to_delete = $_POST['delete_ids'];
    
    echo "<h3>Deleting Companies:</h3>";
    
    foreach ($ids_to_delete as $id) {
        $id = intval($id);
        
        // Get company info first
        $stmt = $conn->prepare("SELECT company_name, company_domain, database_name FROM companies WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $company = $stmt->get_result()->fetch_assoc();
        
        if ($company) {
            echo "<p>Deleting: " . htmlspecialchars($company['company_name']) . " (" . htmlspecialchars($company['company_domain']) . ")</p>";
            
            // Drop their database if it exists
            if (!empty($company['database_name'])) {
                $db_name = $company['database_name'];
                if (preg_match('/^[a-zA-Z0-9_]+$/', $db_name)) {
                    $drop_result = $conn->query("DROP DATABASE IF EXISTS `$db_name`");
                    if ($drop_result) {
                        echo "<p style='margin-left: 20px; color: green;'>✅ Dropped database: $db_name</p>";
                    } else {
                        echo "<p style='margin-left: 20px; color: orange;'>⚠️ Could not drop database $db_name: " . $conn->error . "</p>";
                    }
                }
            }
            
            // Delete company record
            $delete_stmt = $conn->prepare("DELETE FROM companies WHERE id = ?");
            $delete_stmt->bind_param("i", $id);
            if ($delete_stmt->execute()) {
                echo "<p style='margin-left: 20px; color: green;'>✅ Company deleted</p>";
            } else {
                echo "<p style='margin-left: 20px; color: red;'>❌ Failed to delete: " . $delete_stmt->error . "</p>";
            }
        }
    }
    
    echo "<hr>";
}

// Show current companies
$result = $conn->query("SELECT id, company_name, company_domain, database_name FROM companies ORDER BY id");

echo "<h3>Current Companies:</h3>";
echo "<form method='post'>";

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $is_test = (
            stripos($row['company_name'], 'test') !== false ||
            stripos($row['company_name'], 'demo') !== false ||
            stripos($row['company_domain'], 'test') !== false ||
            stripos($row['company_domain'], 'demo') !== false ||
            $row['company_name'] === 'Ozark Made' ||
            $row['company_name'] === 'Omc'
        );
        
        $bg_color = $is_test ? '#ffe6e6' : '#e6ffe6';
        $label = $is_test ? ' (TEST - DELETE)' : ' (KEEP)';
        
        echo "<div style='background: $bg_color; padding: 10px; margin: 5px; border: 1px solid #ccc;'>";
        echo "<label>";
        if ($is_test) {
            echo "<input type='checkbox' name='delete_ids[]' value='" . $row['id'] . "' checked> ";
        }
        echo "<strong>" . htmlspecialchars($row['company_name']) . "</strong> (" . htmlspecialchars($row['company_domain']) . ")" . $label;
        echo "<br>Database: " . htmlspecialchars($row['database_name'] ?? 'none');
        echo "<br>ID: " . $row['id'];
        echo "</label>";
        echo "</div>";
    }
    
    echo "<br><button type='submit' onclick='return confirm(\"Delete selected companies and their databases?\")' style='background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer;'>🗑️ Delete Selected Companies</button>";
} else {
    echo "<p>No companies found.</p>";
}

echo "</form>";

$conn->close();
?>
