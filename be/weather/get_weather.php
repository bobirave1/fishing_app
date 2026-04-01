<?php
/**
 * Weather endpoint — delegates to WeatherService.
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/bootstrap.php';

$container = $GLOBALS['container'];
$weatherService = $container->get(App\Services\WeatherService::class);

$lat = $_GET['lat'] ?? null;
$lon = $_GET['lon'] ?? null;
$lang = $_GET['lang'] ?? 'en';

if (!$lat || !$lon) {
    echo json_encode(['error' => 'Latitude and longitude required']);
    exit;
}

if (!is_numeric($lat) || !is_numeric($lon)) {
    echo json_encode(['error' => 'Invalid coordinates']);
    exit;
}

$weather = $weatherService->getWeather((float)$lat, (float)$lon, $lang);
echo json_encode($weather);