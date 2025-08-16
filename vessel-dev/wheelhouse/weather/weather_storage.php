<?php
/**
 * Weather Location Storage using Cookies
 * Manages persistent storage of weather location preferences
 */

/**
 * Save weather location to cookie
 * @param string $location
 * @param array $weather_data
 */
function saveWeatherLocation($location, $weather_data = null) {
    $cookie_data = [
        'location' => $location,
        'timestamp' => time(),
        'display_name' => $weather_data['location'] ?? $location
    ];
    
    if ($weather_data) {
        $cookie_data['last_weather'] = [
            'temperature' => $weather_data['current']['temperature'],
            'condition' => $weather_data['current']['description'],
            'updated' => $weather_data['updated']
        ];
    }
    
    // Save for 30 days
    setcookie('vessel_weather_location', json_encode($cookie_data), time() + (30 * 24 * 60 * 60), '/');
}

/**
 * Get saved weather location from cookie
 * @return array|null
 */
function getSavedWeatherLocation() {
    if (isset($_COOKIE['vessel_weather_location'])) {
        $cookie_data = json_decode($_COOKIE['vessel_weather_location'], true);
        if ($cookie_data && is_array($cookie_data)) {
            return $cookie_data;
        }
    }
    return null;
}

/**
 * Check if saved weather data is recent (less than 1 hour old)
 * @return bool
 */
function isWeatherDataFresh() {
    $saved = getSavedWeatherLocation();
    if (!$saved || !isset($saved['timestamp'])) {
        return false;
    }
    
    // Consider data fresh if less than 1 hour old
    return (time() - $saved['timestamp']) < 3600;
}

/**
 * Get default location or return null
 * @return string|null
 */
function getDefaultWeatherLocation() {
    $saved = getSavedWeatherLocation();
    return $saved ? $saved['location'] : null;
}

/**
 * Clear saved weather location
 */
function clearWeatherLocation() {
    setcookie('vessel_weather_location', '', time() - 3600, '/');
}

/**
 * Get cached weather summary for dashboard
 * @return array
 */
function getCachedWeatherSummary() {
    $saved = getSavedWeatherLocation();
    
    if (!$saved) {
        return [
            'temperature' => '--°F',
            'condition' => 'Set Location',
            'location' => 'No Location Set'
        ];
    }
    
    // If we have recent cached weather, use it
    if (isset($saved['last_weather']) && isWeatherDataFresh()) {
        return [
            'temperature' => $saved['last_weather']['temperature'] . '°F',
            'condition' => $saved['last_weather']['condition'],
            'location' => $saved['display_name']
        ];
    }
    
    // If data is stale but we have a location, try to fetch fresh data
    if (isset($saved['location'])) {
        try {
            require_once __DIR__ . '/weather_api.php';
            $fresh_weather = getWeatherData($saved['location']);
            
            // Update the cookie with fresh data
            saveWeatherLocation($saved['location'], $fresh_weather);
            
            return [
                'temperature' => $fresh_weather['current']['temperature'] . '°F',
                'condition' => $fresh_weather['current']['description'],
                'location' => $fresh_weather['location']
            ];
        } catch (Exception $e) {
            // If API fails, show location but indicate data is stale
            return [
                'temperature' => 'Update',
                'condition' => 'Click to Refresh',
                'location' => $saved['display_name']
            ];
        }
    }
    
    return [
        'temperature' => '--°F',
        'condition' => 'Set Location',
        'location' => 'No Location Set'
    ];
}
?>
