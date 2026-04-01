<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\FishActivityEngine;
use App\Services\ActivityCacheService;
use App\Services\WeatherService;

class ActivityController extends Controller
{
    public function page(): void
    {
        $this->requireAuth();
        require dirname(__DIR__, 2) . '/fe/pages/activity_feed.php';
    }

    public function handle(): void
    {
        // Delegates to the existing feed.php logic
        require dirname(__DIR__, 2) . '/be/activity/feed.php';
    }

    /**
     * GET /api/fish-activity
     * Enhanced fish activity prediction API endpoint.
     *
     * Query params: lat, lon, species (optional, default: general), lang (optional)
     * Returns JSON with score, factors, hourly curve, best times, moon, weather.
     */
    public function predict(): void
    {
        $lat     = $_GET['lat'] ?? null;
        $lon     = $_GET['lon'] ?? null;
        $species = $_GET['species'] ?? 'general';
        $lang    = $_GET['lang'] ?? ($_SESSION['lang'] ?? 'en');

        if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
            $this->jsonError('Valid latitude and longitude required');
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        $cache = $this->service(ActivityCacheService::class);

        // Check cache first
        $cached = $cache->getActivity($lat, $lon, $species);
        if ($cached !== null) {
            Response::json(array_merge(['success' => true, 'cached' => true], $cached));
        }

        // Get weather (with cache)
        $weatherService = $this->service(WeatherService::class);
        $weatherData = $cache->getWeather($lat, $lon, $lang);
        if ($weatherData === null) {
            $weatherData = $weatherService->getWeather($lat, $lon, $lang);
            $cache->setWeather($lat, $lon, $lang, $weatherData);
        }

        // Calculate activity
        $engine = $this->service(FishActivityEngine::class);
        $result = $engine->calculate($weatherData, $lat, $lon, $species);

        // Cache result
        $cache->setActivity($lat, $lon, $species, $result);

        Response::json(array_merge(['success' => true, 'cached' => false], $result));
    }

    /**
     * GET /api/fish-activity/species
     * Return available species list.
     */
    public function species(): void
    {
        $species = FishActivityEngine::getSpeciesList();
        Response::jsonOk(['species' => $species]);
    }
}
