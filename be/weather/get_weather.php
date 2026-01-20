<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Replace with your OpenWeatherMap API key
$apiKey = '6cd4ed73300b08fd04b2cb0b7bc31d0f';

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;

if (!$lat || !$lon) {
    echo json_encode(['error' => 'Latitude and longitude required']);
    exit;
}

$url = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$apiKey&units=metric";

$response = file_get_contents($url);
if ($response === false) {
    echo json_encode(['error' => 'Unable to fetch data from API. Check network or API key.']);
    exit;
}

$data = json_decode($response, true);

if ($data['cod'] != 200) {
    echo json_encode(['error' => 'API Error: ' . ($data['message'] ?? 'Unknown error')]);
    exit;
}

// Extract fishing-relevant weather data
$weather = [
    'location' => $data['name'] ?? 'Unknown',
    'temperature' => $data['main']['temp'] ?? null, // Celsius
    'feels_like' => $data['main']['feels_like'] ?? null,
    'humidity' => $data['main']['humidity'] ?? null, // %
    'pressure' => $data['main']['pressure'] ?? null, // hPa
    'wind_speed' => $data['wind']['speed'] ?? null, // m/s
    'wind_direction' => $data['wind']['deg'] ?? null, // degrees
    'visibility' => isset($data['visibility']) ? $data['visibility'] / 1000 : null, // km
    'description' => $data['weather'][0]['description'] ?? '',
    'icon' => $data['weather'][0]['icon'] ?? '',
    // Additional fishing data if available
    'sea_level' => $data['main']['sea_level'] ?? null, // hPa, for marine areas
    'grnd_level' => $data['main']['grnd_level'] ?? null
];

echo json_encode($weather);
?>