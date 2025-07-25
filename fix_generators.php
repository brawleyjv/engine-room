<?php
require_once 'config.php';

echo "<h2>Fixing Generator Records with Empty Side Values</h2>";

// First, let's see what we have
$sql = "SELECT EntryID, Side, EntryDate, GenHrs, RecordedBy FROM generators WHERE Side = '' OR Side IS NULL";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h3>Records with empty Side values:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>EntryID</th><th>Current Side</th><th>Entry Date</th><th>GenHrs</th><th>Recorded By</th><th>Action</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['EntryID'] . "</td>";
        echo "<td>'" . $row['Side'] . "'</td>";
        echo "<td>" . $row['EntryDate'] . "</td>";
        echo "<td>" . $row['GenHrs'] . "</td>";
        echo "<td>" . $row['RecordedBy'] . "</td>";
        echo "<td>";
        echo "<a href='?fix_id=" . $row['EntryID'] . "&set_side=Port' style='margin-right: 10px;'>Set to Port</a>";
        echo "<a href='?fix_id=" . $row['EntryID'] . "&set_side=Starboard'>Set to Starboard</a>";
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: green;'>No records found with empty Side values.</p>";
}

// Handle the fix requests
if (isset($_GET['fix_id']) && isset($_GET['set_side'])) {
    $fix_id = (int)$_GET['fix_id'];
    $new_side = $_GET['set_side'];
    
    if (in_array($new_side, ['Port', 'Starboard'])) {
        $update_sql = "UPDATE generators SET Side = ? WHERE EntryID = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param('si', $new_side, $fix_id);
        
        if ($stmt->execute()) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "✅ Successfully updated EntryID $fix_id to Side: $new_side";
            echo "</div>";
            echo "<script>setTimeout(function(){ window.location.href = 'fix_generators.php'; }, 2000);</script>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "❌ Error updating record: " . $stmt->error;
            echo "</div>";
        }
    }
}

echo "<br><h3>All Generator Records:</h3>";
$all_sql = "SELECT * FROM generators ORDER BY EntryDate DESC, Timestamp DESC";
$all_result = $conn->query($all_sql);

if ($all_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>EntryID</th><th>Side</th><th>Entry Date</th><th>GenHrs</th><th>Oil Press</th><th>Fuel Press</th><th>Water Temp</th><th>Recorded By</th><th>Timestamp</th></tr>";
    
    while ($row = $all_result->fetch_assoc()) {
        $side_color = empty($row['Side']) ? 'background-color: #ffcccc;' : '';
        echo "<tr style='$side_color'>";
        echo "<td>" . $row['EntryID'] . "</td>";
        echo "<td><strong>" . ($row['Side'] ?: 'EMPTY') . "</strong></td>";
        echo "<td>" . $row['EntryDate'] . "</td>";
        echo "<td>" . $row['GenHrs'] . "</td>";
        echo "<td>" . $row['OilPress'] . "</td>";
        echo "<td>" . $row['FuelPress'] . "</td>";
        echo "<td>" . $row['WaterTemp'] . "</td>";
        echo "<td>" . $row['RecordedBy'] . "</td>";
        echo "<td>" . $row['Timestamp'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<br><p><a href='add_log.php'>← Back to Add Log</a> | <a href='view_logs.php'>View Logs</a></p>";
?>
