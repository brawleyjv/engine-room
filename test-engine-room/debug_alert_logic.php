<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== DEBUGGING ALERT CALCULATION LOGIC ===\n";

$current_hours = 1850;
$next_service_hours = 2600;
$due_soon_threshold = $next_service_hours - 72; // 2528

echo "Testing alert calculation:\n";
echo "Current hours: $current_hours\n";
echo "Next service: $next_service_hours\n";
echo "Due soon threshold: $due_soon_threshold\n\n";

echo "Logic test:\n";
echo "1. Is $current_hours >= $next_service_hours? " . ($current_hours >= $next_service_hours ? "YES (overdue)" : "NO") . "\n";
echo "2. Is $current_hours >= $due_soon_threshold? " . ($current_hours >= $due_soon_threshold ? "YES (due_soon)" : "NO") . "\n";

if ($current_hours >= $next_service_hours) {
    $result = 'overdue';
} elseif ($current_hours >= $due_soon_threshold) {
    $result = 'due_soon';
} else {
    $result = 'good';
}

echo "Expected result: $result\n\n";

// Test the exact SQL query
echo "Testing SQL query:\n";
$stmt = $pdo->prepare("
    SELECT 
        CASE 
            WHEN ? >= ? THEN 'overdue'
            WHEN ? >= (? - 72) THEN 'due_soon'
            ELSE 'good'
        END as status
");
$stmt->execute([$current_hours, $next_service_hours, $current_hours, $next_service_hours]);
$sql_result = $stmt->fetchColumn();

echo "SQL result: $sql_result\n";

if ($sql_result != $result) {
    echo "❌ MISMATCH between PHP logic and SQL logic!\n";
} else {
    echo "✅ PHP and SQL logic match\n";
}

// Let me also test with the actual service tracking record
echo "\n=== TESTING WITH ACTUAL DATABASE RECORD ===\n";
$stmt = $pdo->prepare("
    SELECT st.next_service_hours,
           CASE 
               WHEN ? >= st.next_service_hours THEN 'overdue'
               WHEN ? >= (st.next_service_hours - 72) THEN 'due_soon'
               ELSE 'good'
           END as calculated_status
    FROM service_tracking st
    WHERE st.equipment_type = 'engine' 
      AND st.equipment_id = 'starboard_main' 
      AND st.service_item_code = 'turbo_oil_filter'
");
$stmt->execute([$current_hours, $current_hours]);
$db_result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Database calculation result:\n";
echo "Next service from DB: {$db_result['next_service_hours']}\n";
echo "Calculated status: {$db_result['calculated_status']}\n";

echo "\n=== DEBUG COMPLETED ===\n";
?>
