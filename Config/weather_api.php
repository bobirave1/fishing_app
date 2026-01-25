<?php
/**
 * Unified Weather API Functions
 * Used by both weather widget and fish activity calculations
 */

// Load environment variables
if (!function_exists('getenv')) {
    function getenv($var) {
        return $_ENV[$var] ?? false;
    }
}

/**
 * Get weather data from OpenWeatherMap API or fallback
 * @param float $lat Latitude
 * @param float $lon Longitude
 * @return array Weather data
 */
function getWeatherData($lat, $lon) {
    // Get API key from environment
    $apiKey = getenv('OPENWEATHER_API_KEY');
    
    // Validate coordinates
    if (!is_numeric($lat) || !is_numeric($lon)) {
        error_log("Invalid coordinates: lat={$lat}, lon={$lon}");
        return getWeatherDataFallback();
    }
    
    // Check if API key is configured
    if (empty($apiKey) || $apiKey === 'your_api_key_here') {
        // Try hardcoded fallback key
        $apiKey = '6cd4ed73300b08fd04b2cb0b7bc31d0f';
    }
    
    if (empty($apiKey)) {
        error_log("OpenWeatherMap API key not configured. Using fallback data.");
        return getWeatherDataFallback();
    }
    
    // Build API URL
    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric&lang=en";
    
    // Make API request with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FishingApp/2.0');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        error_log("OpenWeatherMap API cURL error: {$error}");
        return getWeatherDataFallback();
    }
    
    // Check HTTP status code
    if ($httpCode !== 200) {
        error_log("OpenWeatherMap API returned HTTP {$httpCode}: {$response}");
        return getWeatherDataFallback();
    }
    
    // Parse JSON response
    $data = json_decode($response, true);
    
    // Validate API response
    if (!$data || !isset($data['main']) || !isset($data['weather'])) {
        error_log("Invalid OpenWeatherMap API response: " . substr($response, 0, 200));
        return getWeatherDataFallback();
    }
    
    // Extract weather data
    return [
        'temperature' => round($data['main']['temp'], 1),
        'feels_like' => round($data['main']['feels_like'] ?? $data['main']['temp'], 1),
        'pressure' => $data['main']['pressure'],
        'humidity' => $data['main']['humidity'],
        'wind_speed' => round($data['wind']['speed'] ?? 0, 1),
        'wind_deg' => $data['wind']['deg'] ?? 0,
        'wind_direction' => $data['wind']['deg'] ?? 0,
        'clouds' => $data['clouds']['all'] ?? 0,
        'weather' => $data['weather'][0]['main'] ?? 'Clear',
        'weather_description' => $data['weather'][0]['description'] ?? 'clear sky',
        'description' => ucfirst($data['weather'][0]['description'] ?? 'clear sky'),
        'visibility' => isset($data['visibility']) ? round($data['visibility'] / 1000, 1) : 10,
        'sunrise' => $data['sys']['sunrise'] ?? null,
        'sunset' => $data['sys']['sunset'] ?? null,
        'location' => $data['name'] ?? 'Unknown',
        'location_name' => $data['name'] ?? 'Unknown',
        'country' => $data['sys']['country'] ?? '',
        'icon' => $data['weather'][0]['icon'] ?? '01d',
        'sea_level' => $data['main']['sea_level'] ?? null,
        'grnd_level' => $data['main']['grnd_level'] ?? null,
        'source' => 'OpenWeatherMap API'
    ];
}

/**
 * Fallback function for simulated weather data
 * @return array Simulated weather data
 */
function getWeatherDataFallback() {
    // Generate realistic weather data based on current season and time
    $month = (int)date('n');
    $hour = (int)date('H');
    
    // Season-based temperature
    if ($month >= 3 && $month <= 5) { // Spring
        $baseTemp = 15 + rand(-3, 5);
    } elseif ($month >= 6 && $month <= 8) { // Summer
        $baseTemp = 25 + rand(-3, 5);
    } elseif ($month >= 9 && $month <= 11) { // Autumn
        $baseTemp = 12 + rand(-3, 5);
    } else { // Winter
        $baseTemp = 3 + rand(-3, 5);
    }
    
    // Time-based temperature adjustment
    if ($hour >= 12 && $hour <= 16) {
        $baseTemp += 3; // Warmer midday
    } elseif ($hour >= 0 && $hour <= 6) {
        $baseTemp -= 4; // Cooler at night
    }
    
    $windSpeed = round(rand(0, 80) / 10, 1);
    $pressure = 1013 + rand(-15, 15);
    $humidity = 60 + rand(-20, 20);
    $clouds = rand(0, 100);
    
    $weatherTypes = ['Clear', 'Clouds', 'Rain', 'Drizzle'];
    $weatherDescriptions = [
        'Clear' => 'clear sky',
        'Clouds' => 'scattered clouds',
        'Rain' => 'light rain',
        'Drizzle' => 'light drizzle'
    ];
    $weatherIcons = [
        'Clear' => $hour >= 6 && $hour <= 18 ? '01d' : '01n',
        'Clouds' => '03d',
        'Rain' => '10d',
        'Drizzle' => '09d'
    ];
    
    $weather = $weatherTypes[array_rand($weatherTypes)];
    
    return [
        'temperature' => round($baseTemp, 1),
        'feels_like' => round($baseTemp + rand(-2, 2), 1),
        'pressure' => $pressure,
        'humidity' => $humidity,
        'wind_speed' => $windSpeed,
        'wind_deg' => rand(0, 359),
        'wind_direction' => rand(0, 359),
        'clouds' => $clouds,
        'weather' => $weather,
        'weather_description' => $weatherDescriptions[$weather],
        'description' => ucfirst($weatherDescriptions[$weather]),
        'visibility' => rand(5, 10),
        'sunrise' => strtotime('06:30'),
        'sunset' => strtotime('18:30'),
        'location' => 'Simulated Location',
        'location_name' => 'Simulated Location',
        'country' => 'BG',
        'icon' => $weatherIcons[$weather],
        'sea_level' => null,
        'grnd_level' => null,
        'source' => 'Simulated Data (No API key)'
    ];
}
