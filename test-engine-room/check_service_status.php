<?php
require_once 'test_db.php';

$pdo = getTestDatabase();

echo "=== SERVICE STATUS AFTER 1000 HOUR ADDITION ===\n\n";

$stmt = $pdo->prepare("
    SELECT 
        st.equipment_type,
        st.equipment_id, 
        st.service_item_code,
        st.last_service_hours,
        ss.interval_hours,
        (
            CASE st.equipment_type
                WHEN 'engine' THEN (SELECT total_hours FROM engine_hours WHERE engine_type = st.equipment_id)
                WHEN 'gearbox' THEN (SELECT total_hours FROM gearbox_hours WHERE gearbox_type = st.equipment_id)
                WHEN 'generator' THEN (SELECT total_hours FROM generator_hours WHERE generator_type = st.equipment_id)
            END - st.last_service_hours
        ) as hours_since_service,
        CASE 
            WHEN (
                CASE st.equipment_type
                    WHEN 'engine' THEN (SELECT total_hours FROM engine_hours WHERE engine_type = st.equipment_id)
                    WHEN 'gearbox' THEN (SELECT total_hours FROM gearbox_hours WHERE gearbox_type = st.equipment_id)
                    WHEN 'generator' THEN (SELECT total_hours FROM generator_hours WHERE generator_type = st.equipment_id)
                END - st.last_service_hours
            ) >= ss.interval_hours THEN 'OVERDUE'
            WHEN (
                CASE st.equipment_type
                    WHEN 'engine' THEN (SELECT total_hours FROM engine_hours WHERE engine_type = st.equipment_id)
                    WHEN 'gearbox' THEN (SELECT total_hours FROM gearbox_hours WHERE gearbox_type = st.equipment_id)
                    WHEN 'generator' THEN (SELECT total_hours FROM generator_hours WHERE generator_type = st.equipment_id)
                END - st.last_service_hours
            ) >= (ss.interval_hours - 72) THEN 'DUE SOON'
            ELSE 'GOOD'
        END as status
    FROM service_tracking st
    JOIN service_settings ss ON st.equipment_type = ss.equipment_type 
                              AND st.equipment_id = ss.equipment_id 
                              AND st.service_item_code = ss.service_item_code
    WHERE st.equipment_type = 'engine' AND st.equipment_id = 'starboard_main'
    ORDER BY hours_since_service DESC
");

$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($services as $service) {
    $status_color = $service['status'] == 'OVERDUE' ? '🔴' : ($service['status'] == 'DUE SOON' ? '🟡' : '🟢');
    echo "$status_color {$service['service_item_code']}: ";
    echo "{$service['hours_since_service']} / {$service['interval_hours']} hrs - ";
    echo "{$service['status']}\n";
}

echo "\nNow when you check the dashboard, you should see service alerts for the overdue items!\n";
?>
