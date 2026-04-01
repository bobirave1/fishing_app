<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\WeatherService;

class WeatherController extends Controller
{
    public function get(): void
    {
        $lat = $_GET['lat'] ?? null;
        $lon = $_GET['lon'] ?? null;
        $lang = $_GET['lang'] ?? 'en';

        if (!$lat || !$lon || !is_numeric($lat) || !is_numeric($lon)) {
            $this->jsonError('Valid latitude and longitude required');
        }

        $service = $this->service(WeatherService::class);
        $data = $service->getWeather((float) $lat, (float) $lon, $lang);

        Response::json($data);
    }
}
