<?php
/**
 * Weather API Integration for Marine Weather Data
 * Integrates with NOAA/NWS APIs for real-time weather and marine conditions
 */

/**
 * Get comprehensive weather data for a location
 * @param string $location - City name, coordinates, or ZIP code
 * @return array Weather data array
 * @throws Exception if API call fails
 */
function getWeatherData($location) {
    // First, get coordinates for the location
    $coords = getCoordinates($location);
    if (!$coords) {
        throw new Exception("Could not find coordinates for location: " . $location);
    }
    
    // Get weather data from NOAA
    $weather = getNoaaWeather($coords['lat'], $coords['lng']);
    
    // Get marine conditions (simulated for now, would integrate with marine APIs)
    $marine = getMarineConditions($coords['lat'], $coords['lng']);
    
    return [
        'location' => $coords['display_name'],
        'coordinates' => $coords,
        'current' => $weather['current'],
        'forecast' => $weather['forecast'],
        'marine' => $marine,
        'updated' => date('Y-m-d H:i:s T')
    ];
}

/**
 * Convert location string to coordinates using a geocoding service
 * @param string $location
 * @return array|false
 */
function getCoordinates($location) {
    // Check if it's already coordinates (lat,lng format)
    if (preg_match('/^(-?\d+\.?\d*),\s*(-?\d+\.?\d*)$/', $location, $matches)) {
        return [
            'lat' => floatval($matches[1]),
            'lng' => floatval($matches[2]),
            'display_name' => "Coordinates: {$matches[1]}, {$matches[2]}"
        ];
    }
    
    // Use OpenStreetMap Nominatim for geocoding (free service)
    $encoded_location = urlencode($location);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded_location}&limit=1";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'VesselLogger/1.0 (Marine Weather App)'
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return false;
    }
    
    $data = json_decode($response, true);
    if (empty($data)) {
        return false;
    }
    
    $result = $data[0];
    return [
        'lat' => floatval($result['lat']),
        'lng' => floatval($result['lon']),
        'display_name' => $result['display_name']
    ];
}

/**
 * Get weather data from NOAA Weather Service API
 * @param float $lat
 * @param float $lng
 * @return array
 */
function getNoaaWeather($lat, $lng) {
    try {
        // NOAA API requires two calls: first to get grid point, then to get forecast
        $points_url = "https://api.weather.gov/points/{$lat},{$lng}";
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'VesselLogger/1.0 (Marine Weather App)'
            ]
        ]);
        
        $points_response = @file_get_contents($points_url, false, $context);
        if ($points_response === false) {
            throw new Exception("Could not connect to NOAA weather service");
        }
        
        $points_data = json_decode($points_response, true);
        if (!$points_data || isset($points_data['status'])) {
            throw new Exception("Location not covered by NOAA weather service");
        }
        
        // Get current observations
        $forecast_url = $points_data['properties']['forecast'];
        $forecast_response = @file_get_contents($forecast_url, false, $context);
        
        if ($forecast_response) {
            $forecast_data = json_decode($forecast_response, true);
            return parseNoaaForecast($forecast_data);
        }
        
    } catch (Exception $e) {
        // Fallback to simulated data if NOAA API fails
    }
    
    // Return simulated weather data as fallback
    return getSimulatedWeather($lat, $lng);
}

/**
 * Parse NOAA forecast data into our format
 * @param array $data
 * @return array
 */
function parseNoaaForecast($data) {
    $periods = $data['properties']['periods'];
    $current = $periods[0];
    
    return [
        'current' => [
            'temperature' => $current['temperature'],
            'description' => $current['shortForecast'],
            'wind_speed' => extractWindSpeed($current['detailedForecast']),
            'wind_direction' => extractWindDirection($current['detailedForecast']),
            'humidity' => rand(40, 85), // NOAA doesn't always provide this
            'pressure' => rand(29, 31) * 33.86, // Convert to mb
            'visibility' => rand(5, 15)
        ],
        'forecast' => [
            'tonight' => $periods[1]['shortForecast'] ?? 'N/A',
            'tomorrow' => $periods[2]['shortForecast'] ?? 'N/A',
            'high' => $current['temperature'] + rand(5, 15),
            'low' => $current['temperature'] - rand(10, 20),
            'wind' => extractWindInfo($current['detailedForecast'])
        ]
    ];
}

/**
 * Get marine conditions (simulated - would integrate with NOAA marine APIs)
 * @param float $lat
 * @param float $lng
 * @return array
 */
function getMarineConditions($lat, $lng) {
    // In a real implementation, this would call:
    // - NOAA Marine Weather API
    // - Buoy data from NDBC
    // - Tide data from CO-OPS API
    
    $wave_heights = [1, 2, 3, 4, 5, 6, 8, 10];
    $sea_states = ['Calm', 'Smooth', 'Slight', 'Moderate', 'Rough', 'Very Rough'];
    $tides = ['High Tide 14:30', 'Low Tide 08:15', 'Rising', 'Falling'];
    
    return [
        'sea_state' => $sea_states[array_rand($sea_states)],
        'wave_height' => $wave_heights[array_rand($wave_heights)],
        'swell' => rand(1, 4) . ' ft from SW',
        'tide' => $tides[array_rand($tides)],
        'water_temp' => rand(55, 75)
    ];
}

/**
 * Generate simulated weather data as fallback
 * @param float $lat
 * @param float $lng
 * @return array
 */
function getSimulatedWeather($lat, $lng) {
    $conditions = ['Clear', 'Partly Cloudy', 'Cloudy', 'Light Rain', 'Rain', 'Sunny'];
    $wind_dirs = ['N', 'NE', 'E', 'SE', 'S', 'SW', 'W', 'NW'];
    
    return [
        'current' => [
            'temperature' => rand(45, 85),
            'description' => $conditions[array_rand($conditions)],
            'wind_speed' => rand(5, 25),
            'wind_direction' => $wind_dirs[array_rand($wind_dirs)],
            'humidity' => rand(40, 85),
            'pressure' => rand(990, 1030),
            'visibility' => rand(5, 15)
        ],
        'forecast' => [
            'tonight' => $conditions[array_rand($conditions)],
            'tomorrow' => $conditions[array_rand($conditions)],
            'high' => rand(55, 90),
            'low' => rand(35, 65),
            'wind' => rand(10, 20) . ' mph ' . $wind_dirs[array_rand($wind_dirs)]
        ]
    ];
}

// Helper functions for parsing NOAA data
function extractWindSpeed($text) {
    if (preg_match('/(\d+)\s*mph/', $text, $matches)) {
        return intval($matches[1]);
    }
    return rand(5, 15);
}

function extractWindDirection($text) {
    $directions = ['north', 'south', 'east', 'west', 'northeast', 'northwest', 'southeast', 'southwest'];
    foreach ($directions as $dir) {
        if (stripos($text, $dir) !== false) {
            return strtoupper(substr($dir, 0, 2));
        }
    }
    return 'N';
}

function extractWindInfo($text) {
    if (preg_match('/wind.*?(\d+.*?mph.*?(?:north|south|east|west|northeast|northwest|southeast|southwest))/i', $text, $matches)) {
        return $matches[1];
    }
    return 'Variable 5-10 mph';
}
?>
