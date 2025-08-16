<?php
// Weather API functions and storage
require_once 'weather_api.php';
require_once 'weather_storage.php';

$weather_data = null;
$error_message = '';
$location = '';

// Get saved location on page load
$saved_location_data = getSavedWeatherLocation();
if ($saved_location_data && !isset($_POST['location'])) {
    $location = $saved_location_data['location'];
    
    // If data is fresh, use cached data, otherwise fetch fresh
    if (isWeatherDataFresh() && isset($saved_location_data['last_weather'])) {
        // Use cached data but still try to get full data in background
        try {
            $weather_data = getWeatherData($location);
            // Update cache with fresh data
            saveWeatherLocation($location, $weather_data);
        } catch (Exception $e) {
            // If fresh fetch fails, we'll show form to re-enter
            $error_message = 'Weather data refresh failed. Please update your location.';
        }
    } else {
        // Data is stale, fetch fresh
        try {
            $weather_data = getWeatherData($location);
            saveWeatherLocation($location, $weather_data);
        } catch (Exception $e) {
            $error_message = 'Could not refresh weather data for saved location: ' . $e->getMessage();
        }
    }
}

// Handle location submission
if (isset($_POST['location']) && !empty(trim($_POST['location']))) {
    $location = trim($_POST['location']);
    try {
        $weather_data = getWeatherData($location);
        // Save the successful location and weather data
        saveWeatherLocation($location, $weather_data);
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Handle clear location
if (isset($_POST['clear_location'])) {
    clearWeatherLocation();
    $location = '';
    $weather_data = null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Marine Weather - Wheelhouse</title>
    <link rel="stylesheet" type="text/css" href="../../css/styles.css">
    <style>
        .weather-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .weather-header { background: linear-gradient(135deg, #3498db, #2980b9); color: white; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .weather-title { margin: 0; font-size: 28px; display: flex; align-items: center; }
        .weather-title::before { content: "🌤️"; margin-right: 15px; font-size: 32px; }
        
        .location-form { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .form-row { display: flex; gap: 10px; align-items: end; }
        .location-input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .weather-btn { padding: 12px 24px; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .weather-btn:hover { background: #2980b9; }
        
        .weather-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .weather-card { background: white; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); overflow: hidden; }
        .weather-card-header { background: #34495e; color: white; padding: 15px; font-weight: bold; }
        .weather-card-body { padding: 20px; }
        
        .current-weather { background: linear-gradient(135deg, #e74c3c, #c0392b); color: white; }
        .marine-conditions { background: linear-gradient(135deg, #3498db, #2980b9); color: white; }
        .forecast-weather { background: linear-gradient(135deg, #27ae60, #229954); color: white; }
        
        .weather-stat { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #ecf0f1; }
        .weather-stat:last-child { border-bottom: none; }
        .weather-value { font-weight: bold; }
        
        .error-box { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #f5c6cb; }
        .info-box { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 20px 0; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="weather-container">
        <div class="nav">
            <a href="../index.php">← Back to Wheelhouse</a> / <strong>Marine Weather</strong>
        </div>
        
        <div class="weather-header">
            <h1 class="weather-title">Marine Weather Center</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Real-time weather conditions and marine forecasts</p>
        </div>
        
        <div class="location-form">
            <h3>Weather Location</h3>
            <?php if ($saved_location_data): ?>
                <div style="background: #d1ecf1; padding: 10px; border-radius: 4px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <span>
                        <strong>📍 Saved Location:</strong> <?php echo htmlspecialchars($saved_location_data['display_name']); ?>
                        <small style="opacity: 0.7;">(Last updated: <?php echo date('M j, g:i A', $saved_location_data['timestamp']); ?>)</small>
                    </span>
                    <form method="POST" style="margin: 0;">
                        <button type="submit" name="clear_location" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; font-size: 12px; cursor: pointer;">
                            Clear
                        </button>
                    </form>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <input type="text" name="location" class="location-input" 
                           placeholder="Enter city, coordinates (lat,lng), or ZIP code" 
                           value="<?php echo htmlspecialchars($location); ?>">
                    <button type="submit" class="weather-btn">
                        <?php echo $saved_location_data ? 'Update Weather' : 'Get Weather'; ?>
                    </button>
                </div>
            </form>
            
            <div class="info-box">
                <strong>💡 Tips:</strong> Enter a nearby port city, coordinates (e.g., "40.7,-74.0"), or ZIP code. 
                Your location will be saved for future visits.
                <?php if ($saved_location_data): ?>
                    <br><strong>🔄 Auto-refresh:</strong> Weather data automatically updates every hour.
                <?php endif; ?>
            </div>
        </div>
        
        <?php if ($error_message): ?>
            <div class="error-box">
                <strong>⚠️ Error:</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($weather_data): ?>
            <div class="weather-grid">
                <!-- Current Conditions -->
                <div class="weather-card">
                    <div class="weather-card-header current-weather">
                        🌡️ Current Conditions
                    </div>
                    <div class="weather-card-body">
                        <div class="weather-stat">
                            <span>Location:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['location']); ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Temperature:</span>
                            <span class="weather-value"><?php echo $weather_data['current']['temperature']; ?>°F</span>
                        </div>
                        <div class="weather-stat">
                            <span>Condition:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['current']['description']); ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Wind:</span>
                            <span class="weather-value"><?php echo $weather_data['current']['wind_speed']; ?> mph <?php echo $weather_data['current']['wind_direction']; ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Humidity:</span>
                            <span class="weather-value"><?php echo $weather_data['current']['humidity']; ?>%</span>
                        </div>
                        <div class="weather-stat">
                            <span>Pressure:</span>
                            <span class="weather-value"><?php echo $weather_data['current']['pressure']; ?> mb</span>
                        </div>
                        <div class="weather-stat">
                            <span>Visibility:</span>
                            <span class="weather-value"><?php echo $weather_data['current']['visibility']; ?> miles</span>
                        </div>
                    </div>
                </div>
                
                <!-- Marine Conditions -->
                <div class="weather-card">
                    <div class="weather-card-header marine-conditions">
                        🌊 Marine Conditions
                    </div>
                    <div class="weather-card-body">
                        <div class="weather-stat">
                            <span>Sea State:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['marine']['sea_state']); ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Wave Height:</span>
                            <span class="weather-value"><?php echo $weather_data['marine']['wave_height']; ?> ft</span>
                        </div>
                        <div class="weather-stat">
                            <span>Swell:</span>
                            <span class="weather-value"><?php echo $weather_data['marine']['swell']; ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Tide:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['marine']['tide']); ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Water Temp:</span>
                            <span class="weather-value"><?php echo $weather_data['marine']['water_temp']; ?>°F</span>
                        </div>
                    </div>
                </div>
                
                <!-- Forecast -->
                <div class="weather-card">
                    <div class="weather-card-header forecast-weather">
                        📅 24-Hour Forecast
                    </div>
                    <div class="weather-card-body">
                        <div class="weather-stat">
                            <span>Tonight:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['forecast']['tonight']); ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>Tomorrow:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['forecast']['tomorrow']); ?></span>
                        </div>
                        <div class="weather-stat">
                            <span>High/Low:</span>
                            <span class="weather-value"><?php echo $weather_data['forecast']['high']; ?>°F / <?php echo $weather_data['forecast']['low']; ?>°F</span>
                        </div>
                        <div class="weather-stat">
                            <span>Wind Forecast:</span>
                            <span class="weather-value"><?php echo htmlspecialchars($weather_data['forecast']['wind']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-box">
                <strong>🕐 Last Updated:</strong> <?php echo $weather_data['updated']; ?>
                <br><strong>📡 Data Source:</strong> National Weather Service (NOAA)
            </div>
        <?php else: ?>
            <div class="info-box">
                <strong>🌤️ Welcome to Marine Weather Center</strong><br>
                Enter a location above to get comprehensive weather and marine conditions. 
                This system integrates with NOAA weather services to provide:
                <ul style="margin: 10px 0;">
                    <li>Current weather conditions</li>
                    <li>Marine-specific data (waves, tides, water temperature)</li>
                    <li>Weather forecasts</li>
                    <li>Wind and visibility information</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
