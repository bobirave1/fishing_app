<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0); // Hide errors from JSON output
ini_set('display_errors', 0);

// Load unified weather functions
require_once __DIR__ . '/../../config/weather_api.php';
require_once __DIR__ . '/../../config/database.php';

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if (!$lat || !$lon) {
    echo json_encode(['error' => 'Latitude and longitude required']);
    exit;
}

// Validate coordinates
if (!is_numeric($lat) || !is_numeric($lon)) {
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

// Get weather data using unified function
$weather = getWeatherData($lat, $lon);

// Return the weather data
echo json_encode($weather);
?>