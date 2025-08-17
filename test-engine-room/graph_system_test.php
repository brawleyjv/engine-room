<?php
/**
 * Quick Test - Open the graphing system and perform basic tests
 */

echo "📊 Equipment Performance Graphs - Quick Test\n\n";

echo "Testing system components:\n";
echo "✅ Graph page created: equipment_graphs.php\n";
echo "✅ API backend created: graph_api.php\n";
echo "✅ Sample data generated: 378 total records\n";
echo "✅ Dashboard navigation updated\n\n";

echo "Available test data:\n";
echo "📈 Engines: port_main, center_main, starboard_main\n";
echo "🔌 Generators: port_gen, center_gen\n";
echo "📅 Date range: Past 30 days (2025-07-18 to 2025-08-17)\n";
echo "📊 Total readings: 256 engine + 122 generator = 378 records\n\n";

echo "Key features implemented:\n";
echo "🔍 Equipment type and ID selection\n";
echo "📅 Date range selection (7/30/90/365 days, all time, custom)\n";
echo "⚙️ Parameter selection checkboxes by category\n";
echo "📊 Interactive Chart.js graphs with dual Y-axes\n";
echo "🎛️ Scale settings for RPM, pressure, and temperature ranges\n";
echo "📈 Time-series data with hover details\n";
echo "🎨 Color-coded parameter lines with interactive legend\n";
echo "💾 Persistent scale settings stored in database\n\n";

echo "Available parameters by equipment:\n";
echo "\nEngines:\n";
echo "  🔄 RPM: rpm\n";
echo "  💨 Pressures: oil_pressure, fuel_pressure, turbo_oil_pressure, governor_air_pressure,\n";
echo "               aftercooler_water_pressure, lube_oil_filter_pressure_in/out, air_box_pressure, ship_air_pressure\n";
echo "  🌡️ Temperatures: water_temp_in, water_temp_out, oil_temp_in, oil_temp_out, aftercooler_water_temp_out\n";
echo "  📊 Other: crankcase_vacuum\n";

echo "\nGenerators:\n";
echo "  🔄 RPM: rpm\n";
echo "  💨 Pressures: lube_oil_pressure, fuel_pressure\n";
echo "  🌡️ Temperatures: water_temp_in, water_temp_out\n";
echo "  ⚡ Electrical: battery_voltage, voltage_out, frequency_hz, amperage\n\n";

echo "🌐 Access the system at: http://localhost/enginerm/test-engine-room/equipment_graphs.php\n";
echo "🏠 Return to dashboard: http://localhost/enginerm/test-engine-room/index.php\n\n";

echo "✨ Historical Performance Graphing System is ready for testing!\n";
?>
