<?php

namespace App\Services;

class WeatherService
{
    /**
     * Get weather data for coordinates.
     * Delegates to the unified getWeatherData() function from config/weather_api.php.
     */
    public function getWeather(float $lat, float $lon, string $lang = 'en'): array
    {
        if (!function_exists('getWeatherData')) {
            require_once dirname(__DIR__, 2) . '/config/weather_api.php';
        }
        return getWeatherData($lat, $lon, $lang);
    }
}
