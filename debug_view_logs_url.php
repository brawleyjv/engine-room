<?php
session_start();
require_once 'config.php';
require_once 'auth_functions.php';
require_once 'vessel_functions.php';

echo "<h1>Debug: view_logs.php URL Access</h1>";

// Check if user is logged in
echo "<h2>Authentication Check:</h2>";
try {
    require_login();
    echo "✅ User is logged in<br>";
    $current_user = get_logged_in_user();
    echo "Current user: " . htmlspecialchars($current_user['Username']) . "<br>";
} catch (Exception $e) {
    echo "❌ Authentication failed: " . $e->getMessage() . "<br>";
}

// Check URL parameters
echo "<h2>URL Parameters:</h2>";
echo "vessel_id from URL: " . ($_GET['vessel_id'] ?? 'Not set') . "<br>";

// Check session before any processing
echo "<h2>Session State (Before):</h2>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'Not set') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'Not set') . "<br>";
echo "current_vessel_name: " . ($_SESSION['current_vessel_name'] ?? 'Not set') . "<br>";

// Simulate the session logic from view_logs.php
echo "<h2>Session Logic Simulation:</h2>";
if (!isset($_SESSION['current_vessel_id']) || empty($_SESSION['current_vessel_id'])) {
    echo "current_vessel_id is not set or empty<br>";
    
    if (isset($_SESSION['active_vessel_id']) && !empty($_SESSION['active_vessel_id'])) {
        echo "Found active_vessel_id: " . $_SESSION['active_vessel_id'] . "<br>";
        echo "Syncing session variables...<br>";
        
        $_SESSION['current_vessel_id'] = $_SESSION['active_vessel_id'];
        
        // Get vessel name
        $sql = "SELECT VesselName FROM vessels WHERE VesselID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $_SESSION['active_vessel_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $vessel = $result->fetch_assoc();
        if ($vessel) {
            $_SESSION['current_vessel_name'] = $vessel['VesselName'];
            echo "Set vessel name: " . $vessel['VesselName'] . "<br>";
        }
    } else {
        echo "❌ No active_vessel_id found - would redirect to vessel selection<br>";
    }
} else {
    echo "✅ current_vessel_id is already set: " . $_SESSION['current_vessel_id'] . "<br>";
}

// Check session after processing
echo "<h2>Session State (After):</h2>";
echo "current_vessel_id: " . ($_SESSION['current_vessel_id'] ?? 'Not set') . "<br>";
echo "active_vessel_id: " . ($_SESSION['active_vessel_id'] ?? 'Not set') . "<br>";
echo "current_vessel_name: " . ($_SESSION['current_vessel_name'] ?? 'Not set') . "<br>";

// Get current vessel
echo "<h2>Vessel Information:</h2>";
try {
    $current_vessel = get_current_vessel($conn);
    if ($current_vessel) {
        echo "✅ Current vessel retrieved:<br>";
        echo "VesselID: " . $current_vessel['VesselID'] . "<br>";
        echo "VesselName: " . $current_vessel['VesselName'] . "<br>";
        echo "EngineConfig: " . $current_vessel['EngineConfig'] . "<br>";
    } else {
        echo "❌ No current vessel found<br>";
    }
} catch (Exception $e) {
    echo "❌ Error getting current vessel: " . $e->getMessage() . "<br>";
}

// Get available sides
echo "<h2>Available Sides:</h2>";
try {
    if (isset($current_vessel) && $current_vessel) {
        $sides = get_vessel_sides($conn, $current_vessel['VesselID']);
        echo "Sides for vessel " . $current_vessel['VesselID'] . ":<br>";
        foreach ($sides as $side) {
            echo "- " . htmlspecialchars($side) . "<br>";
        }
    } else {
        echo "❌ Cannot get sides - no current vessel<br>";
    }
} catch (Exception $e) {
    echo "❌ Error getting vessel sides: " . $e->getMessage() . "<br>";
}

// Check if URL vessel_id matches session
echo "<h2>URL vs Session Check:</h2>";
$url_vessel_id = $_GET['vessel_id'] ?? null;
$session_vessel_id = $_SESSION['current_vessel_id'] ?? null;

if ($url_vessel_id) {
    echo "URL vessel_id: $url_vessel_id<br>";
    echo "Session current_vessel_id: $session_vessel_id<br>";
    
    if ($url_vessel_id == $session_vessel_id) {
        echo "✅ URL and session vessel IDs match<br>";
    } else {
        echo "⚠️ URL and session vessel IDs don't match<br>";
        echo "Note: view_logs.php doesn't use URL vessel_id parameter - it uses session<br>";
    }
} else {
    echo "No vessel_id in URL<br>";
}

echo "<br><a href='view_logs.php'>Go to view_logs.php</a><br>";
echo "<a href='manage_vessels.php'>Go to manage_vessels.php</a><br>";
?>
