<?php

namespace App\Services;

/**
 * Simple file-based cache for weather data and activity calculations.
 *
 * Cache directory: storage/cache/
 * Each cache entry is a JSON file named by its key hash.
 */
class ActivityCacheService
{
    private string $cacheDir;

    /** Default TTL in seconds */
    private const TTL_WEATHER  = 900;   // 15 minutes
    private const TTL_ACTIVITY = 1800;  // 30 minutes

    public function __construct()
    {
        $this->cacheDir = dirname(__DIR__, 2) . '/storage/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
    }

    /**
     * Get cached weather data for coordinates.
     */
    public function getWeather(float $lat, float $lon, string $lang): ?array
    {
        $key = $this->weatherKey($lat, $lon, $lang);
        return $this->get($key, self::TTL_WEATHER);
    }

    /**
     * Store weather data in cache.
     */
    public function setWeather(float $lat, float $lon, string $lang, array $data): void
    {
        $key = $this->weatherKey($lat, $lon, $lang);
        $this->set($key, $data);
    }

    /**
     * Get cached activity calculation.
     */
    public function getActivity(float $lat, float $lon, string $species): ?array
    {
        $key = $this->activityKey($lat, $lon, $species);
        return $this->get($key, self::TTL_ACTIVITY);
    }

    /**
     * Store activity calculation in cache.
     */
    public function setActivity(float $lat, float $lon, string $species, array $data): void
    {
        $key = $this->activityKey($lat, $lon, $species);
        $this->set($key, $data);
    }

    /**
     * Purge expired cache files.
     * Should be called periodically (e.g., cron or on-demand).
     */
    public function purgeExpired(int $maxAge = 7200): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/*.json');
        if (!$files) return 0;

        $now = time();
        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAge) {
                @unlink($file);
                $count++;
            }
        }
        return $count;
    }

    // ─── Internal ──────────────────────────────────────────

    private function get(string $key, int $ttl): ?array
    {
        $file = $this->filePath($key);
        if (!file_exists($file)) return null;
        if (time() - filemtime($file) > $ttl) {
            @unlink($file);
            return null;
        }
        $data = @file_get_contents($file);
        if ($data === false) return null;
        $decoded = json_decode($data, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function set(string $key, array $data): void
    {
        $file = $this->filePath($key);
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        @file_put_contents($file, $json, LOCK_EX);
    }

    private function filePath(string $key): string
    {
        return $this->cacheDir . '/' . $key . '.json';
    }

    /**
     * Round coordinates to ~1 km grid so nearby requests share cache.
     */
    private function weatherKey(float $lat, float $lon, string $lang): string
    {
        $lat = round($lat, 2);
        $lon = round($lon, 2);
        return 'weather_' . md5("{$lat}_{$lon}_{$lang}");
    }

    private function activityKey(float $lat, float $lon, string $species): string
    {
        $lat = round($lat, 2);
        $lon = round($lon, 2);
        $date = date('Y-m-d');
        $hour = (int) (date('H') / 1); // per-hour granularity
        return 'activity_' . md5("{$lat}_{$lon}_{$species}_{$date}_{$hour}");
    }
}
