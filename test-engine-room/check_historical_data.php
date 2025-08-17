<?php
require_once 'test_db.php';
$pdo = getTestDatabase();

echo "=== CHECKING HISTORICAL DATA FOR GRAPHING ===\n\n";

echo "Engine readings table structure:\n";
$stmt = $pdo->prepare('PRAGMA table_info(engine_readings)');
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "- {$col['name']} ({$col['type']})\n";
}

echo "\nGenerator readings table structure:\n";
$stmt = $pdo->prepare('PRAGMA table_info(generator_readings)');
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "- {$col['name']} ({$col['type']})\n";
}

echo "\nSample engine historical data:\n";
$stmt = $pdo->query("SELECT engine_type, reading_date, rpm, oil_pressure, water_temp_out, fuel_pressure 
                     FROM engine_readings 
                     WHERE rpm IS NOT NULL AND rpm > 0 
                     ORDER BY reading_date DESC LIMIT 5");
$readings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($readings as $reading) {
    echo "{$reading['engine_type']} - {$reading['reading_date']}: ";
    echo "RPM={$reading['rpm']}, Oil={$reading['oil_pressure']}psi, ";
    echo "Temp={$reading['water_temp_out']}°F, Fuel={$reading['fuel_pressure']}psi\n";
}

echo "\nData availability for graphing:\n";
$stmt = $pdo->query("SELECT engine_type, COUNT(*) as readings, MIN(reading_date) as first_date, MAX(reading_date) as last_date 
                     FROM engine_readings 
                     WHERE rpm IS NOT NULL AND rpm > 0 
                     GROUP BY engine_type");
$summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($summary as $sum) {
    echo "📊 {$sum['engine_type']}: {$sum['readings']} readings from {$sum['first_date']} to {$sum['last_date']}\n";
}

echo "\n✅ Ready to build historical performance graphs!\n";
?>
