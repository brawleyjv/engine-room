<?php
/**
 * Weather Widget for Dashboard
 * Provides simple weather summary for wheelhouse dashboard
 */

require_once __DIR__ . '/weather_api.php';
require_once __DIR__ . '/weather_storage.php';

/**
 * Get simple weather summary for dashboard display
 * @param string $location
 * @return array|null
 */
function getWeatherSummary($location) {
    if (empty($location)) {
        return null;
    }
    
    try {
        $weather_data = getWeatherData($location);
        
        // Clean up location name for dashboard display
        $location_display = $weather_data['location'];
        if (strlen($location_display) > 25) {
            $location_display = substr($location_display, 0, 22) . '...';
        }
        
        return [
            'temperature' => $weather_data['current']['temperature'] . '°F',
            'condition' => $weather_data['current']['description'],
            'location' => $location_display
        ];
    } catch (Exception $e) {
        return [
            'temperature' => '--°F',
            'condition' => 'Weather Unavailable',
            'location' => 'Set Location'
        ];
    }
}

/**
 * Get cached weather or return placeholder
 * This now uses the cookie-based storage system
 */
function getDashboardWeather() {
    return getCachedWeatherSummary();
}
?>
