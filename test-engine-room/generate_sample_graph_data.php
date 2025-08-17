<?php
/**
 * Generate Sample Historical Data for Graphing
 * Creates realistic engine and generator readings over the past month
 */

require_once 'test_db.php';

$pdo = getTestDatabase();

echo "Generating historical sample data for graphs...\n\n";

// Parameters for realistic marine engine data
$engines = [
    'port_main' => [
        'rpm_base' => 1200, 'rpm_var' => 200,
        'oil_pressure_base' => 65, 'oil_pressure_var' => 10,
        'fuel_pressure_base' => 35, 'fuel_pressure_var' => 5,
        'water_temp_in_base' => 160, 'water_temp_in_var' => 20,
        'water_temp_out_base' => 180, 'water_temp_out_var' => 15,
        'oil_temp_in_base' => 175, 'oil_temp_in_var' => 15,
        'oil_temp_out_base' => 195, 'oil_temp_out_var' => 15,
        'turbo_oil_pressure_base' => 45, 'turbo_oil_pressure_var' => 8
    ],
    'center_main' => [
        'rpm_base' => 1250, 'rpm_var' => 180,
        'oil_pressure_base' => 70, 'oil_pressure_var' => 8,
        'fuel_pressure_base' => 38, 'fuel_pressure_var' => 4,
        'water_temp_in_base' => 165, 'water_temp_in_var' => 18,
        'water_temp_out_base' => 185, 'water_temp_out_var' => 12,
        'oil_temp_in_base' => 180, 'oil_temp_in_var' => 12,
        'oil_temp_out_base' => 200, 'oil_temp_out_var' => 12,
        'turbo_oil_pressure_base' => 48, 'turbo_oil_pressure_var' => 7
    ],
    'starboard_main' => [
        'rpm_base' => 1180, 'rpm_var' => 220,
        'oil_pressure_base' => 62, 'oil_pressure_var' => 12,
        'fuel_pressure_base' => 32, 'fuel_pressure_var' => 6,
        'water_temp_in_base' => 155, 'water_temp_in_var' => 25,
        'water_temp_out_base' => 175, 'water_temp_out_var' => 20,
        'oil_temp_in_base' => 170, 'oil_temp_in_var' => 18,
        'oil_temp_out_base' => 190, 'oil_temp_out_var' => 18,
        'turbo_oil_pressure_base' => 42, 'turbo_oil_pressure_var' => 10
    ]
];

$generators = [
    'port_gen' => [
        'rpm_base' => 1800, 'rpm_var' => 50,
        'lube_oil_pressure_base' => 55, 'lube_oil_pressure_var' => 8,
        'fuel_pressure_base' => 28, 'fuel_pressure_var' => 4,
        'water_temp_in_base' => 150, 'water_temp_in_var' => 15,
        'water_temp_out_base' => 170, 'water_temp_out_var' => 12,
        'battery_voltage_base' => 24.5, 'battery_voltage_var' => 1.5,
        'voltage_out_base' => 480, 'voltage_out_var' => 15,
        'frequency_hz_base' => 60, 'frequency_hz_var' => 1,
        'amperage_base' => 120, 'amperage_var' => 30
    ],
    'center_gen' => [
        'rpm_base' => 1800, 'rpm_var' => 45,
        'lube_oil_pressure_base' => 58, 'lube_oil_pressure_var' => 7,
        'fuel_pressure_base' => 30, 'fuel_pressure_var' => 3,
        'water_temp_in_base' => 155, 'water_temp_in_var' => 12,
        'water_temp_out_base' => 175, 'water_temp_out_var' => 10,
        'battery_voltage_base' => 24.8, 'battery_voltage_var' => 1.2,
        'voltage_out_base' => 485, 'voltage_out_var' => 12,
        'frequency_hz_base' => 60, 'frequency_hz_var' => 0.8,
        'amperage_base' => 135, 'amperage_var' => 25
    ]
];

// Generate data for the past 30 days
$startDate = new DateTime('-30 days');
$endDate = new DateTime();

// Clear existing data to avoid conflicts
echo "Clearing existing sample data...\n";
$pdo->exec("DELETE FROM engine_readings WHERE vessel_id = 1");
$pdo->exec("DELETE FROM generator_readings WHERE vessel_id = 1");

$engineCount = 0;
$generatorCount = 0;

// Generate engine data
foreach ($engines as $engineId => $params) {
    echo "Generating data for engine: $engineId\n";
    
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        // Generate 2-4 readings per day (simulating watch changes)
        $readingsPerDay = rand(2, 4);
        
        for ($i = 0; $i < $readingsPerDay; $i++) {
            $readingTime = clone $currentDate;
            $readingTime->add(new DateInterval('PT' . ($i * (24 / $readingsPerDay)) . 'H'));
            
            // Add some randomness to the time
            $randomMinutes = rand(-30, 30);
            if ($randomMinutes >= 0) {
                $readingTime->add(new DateInterval('PT' . $randomMinutes . 'M'));
            } else {
                $readingTime->sub(new DateInterval('PT' . abs($randomMinutes) . 'M'));
            }
            
            // Generate realistic values with some variation
            $rpm = $params['rpm_base'] + rand(-$params['rpm_var'], $params['rpm_var']);
            $oil_pressure = $params['oil_pressure_base'] + rand(-$params['oil_pressure_var'], $params['oil_pressure_var']);
            $fuel_pressure = $params['fuel_pressure_base'] + rand(-$params['fuel_pressure_var'], $params['fuel_pressure_var']);
            $water_temp_in = $params['water_temp_in_base'] + rand(-$params['water_temp_in_var'], $params['water_temp_in_var']);
            $water_temp_out = $params['water_temp_out_base'] + rand(-$params['water_temp_out_var'], $params['water_temp_out_var']);
            $oil_temp_in = $params['oil_temp_in_base'] + rand(-$params['oil_temp_in_var'], $params['oil_temp_in_var']);
            $oil_temp_out = $params['oil_temp_out_base'] + rand(-$params['oil_temp_out_var'], $params['oil_temp_out_var']);
            $turbo_oil_pressure = $params['turbo_oil_pressure_base'] + rand(-$params['turbo_oil_pressure_var'], $params['turbo_oil_pressure_var']);
            
            // Additional parameters with some correlation to main parameters
            $governor_air_pressure = 90 + rand(-10, 10);
            $aftercooler_water_pressure = 25 + rand(-5, 5);
            $lube_oil_filter_pressure_in = $oil_pressure + rand(-3, 3);
            $lube_oil_filter_pressure_out = $lube_oil_filter_pressure_in - rand(2, 8);
            $aftercooler_water_temp_out = $water_temp_out - rand(10, 25);
            $air_box_pressure = 15 + rand(-3, 3);
            $crankcase_vacuum = 2.5 + (rand(-50, 50) / 100.0);
            $ship_air_pressure = 120 + rand(-10, 10);
            
            $stmt = $pdo->prepare("INSERT INTO engine_readings 
                (vessel_id, engine_type, rpm, fuel_pressure, oil_pressure, water_temp_in, water_temp_out, 
                 oil_temp_in, oil_temp_out, turbo_oil_pressure, governor_air_pressure, aftercooler_water_pressure,
                 lube_oil_filter_pressure_in, lube_oil_filter_pressure_out, aftercooler_water_temp_out,
                 air_box_pressure, crankcase_vacuum, ship_air_pressure, reading_date, created_by)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Sample Data Generator')");
            
            $stmt->execute([
                $engineId, $rpm, $fuel_pressure, $oil_pressure, $water_temp_in, $water_temp_out,
                $oil_temp_in, $oil_temp_out, $turbo_oil_pressure, $governor_air_pressure,
                $aftercooler_water_pressure, $lube_oil_filter_pressure_in, $lube_oil_filter_pressure_out,
                $aftercooler_water_temp_out, $air_box_pressure, $crankcase_vacuum, $ship_air_pressure,
                $readingTime->format('Y-m-d H:i:s')
            ]);
            
            $engineCount++;
        }
        
        $currentDate->add(new DateInterval('P1D'));
    }
}

// Generate generator data
foreach ($generators as $generatorId => $params) {
    echo "Generating data for generator: $generatorId\n";
    
    $currentDate = clone $startDate;
    while ($currentDate <= $endDate) {
        // Generators typically run less frequently, so 1-3 readings per day
        $readingsPerDay = rand(1, 3);
        
        for ($i = 0; $i < $readingsPerDay; $i++) {
            $readingTime = clone $currentDate;
            $readingTime->add(new DateInterval('PT' . ($i * (24 / $readingsPerDay)) . 'H'));
            
            // Add some randomness to the time
            $randomMinutes = rand(-45, 45);
            if ($randomMinutes >= 0) {
                $readingTime->add(new DateInterval('PT' . $randomMinutes . 'M'));
            } else {
                $readingTime->sub(new DateInterval('PT' . abs($randomMinutes) . 'M'));
            }
            
            // Generate realistic values
            $rpm = $params['rpm_base'] + rand(-$params['rpm_var'], $params['rpm_var']);
            $lube_oil_pressure = $params['lube_oil_pressure_base'] + rand(-$params['lube_oil_pressure_var'], $params['lube_oil_pressure_var']);
            $fuel_pressure = $params['fuel_pressure_base'] + rand(-$params['fuel_pressure_var'], $params['fuel_pressure_var']);
            $water_temp_in = $params['water_temp_in_base'] + rand(-$params['water_temp_in_var'], $params['water_temp_in_var']);
            $water_temp_out = $params['water_temp_out_base'] + rand(-$params['water_temp_out_var'], $params['water_temp_out_var']);
            $battery_voltage = $params['battery_voltage_base'] + (rand(-$params['battery_voltage_var'] * 100, $params['battery_voltage_var'] * 100) / 100.0);
            $voltage_out = $params['voltage_out_base'] + rand(-$params['voltage_out_var'], $params['voltage_out_var']);
            $frequency_hz = $params['frequency_hz_base'] + (rand(-$params['frequency_hz_var'] * 100, $params['frequency_hz_var'] * 100) / 100.0);
            $amperage = $params['amperage_base'] + rand(-$params['amperage_var'], $params['amperage_var']);
            
            $stmt = $pdo->prepare("INSERT INTO generator_readings 
                (vessel_id, generator_type, rpm, lube_oil_pressure, fuel_pressure, water_temp_in, water_temp_out,
                 battery_voltage, voltage_out, frequency_hz, amperage, reading_date, created_by)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Sample Data Generator')");
            
            $stmt->execute([
                $generatorId, $rpm, $lube_oil_pressure, $fuel_pressure, $water_temp_in, $water_temp_out,
                $battery_voltage, $voltage_out, $frequency_hz, $amperage, $readingTime->format('Y-m-d H:i:s')
            ]);
            
            $generatorCount++;
        }
        
        $currentDate->add(new DateInterval('P1D'));
    }
}

echo "\n=== Sample Data Generation Complete ===\n";
echo "Engine readings generated: $engineCount\n";
echo "Generator readings generated: $generatorCount\n";
echo "Total readings: " . ($engineCount + $generatorCount) . "\n";
echo "Date range: " . $startDate->format('Y-m-d') . " to " . $endDate->format('Y-m-d') . "\n";
echo "\nYou can now test the graphing system with realistic historical data!\n";
?>
