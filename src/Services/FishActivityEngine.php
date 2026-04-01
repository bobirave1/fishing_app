<?php

namespace App\Services;

/**
 * FishActivityEngine — Advanced multi-factor fish activity prediction.
 *
 * Factors (weights):
 *  1. Solunar Theory      30%  – major/minor periods × moon-phase strength
 *  2. Time of Day          20%  – dawn/dusk golden hours
 *  3. Barometric Pressure  18%  – falling pressure = feeding frenzy
 *  4. Temperature          12%  – species-specific optimal ranges
 *  5. Wind & Cloud Cover    8%  – light wind + overcast = ideal
 *  6. Humidity              5%  – high humidity = active insects = active fish
 *  7. Precipitation         4%  – light rain boost, storm penalty
 *  8. Moon Illumination     3%  – night visibility affecting nocturnal species
 *
 * Features:
 *  - 6 species profiles (carp, trout, pike, catfish, perch, bass) + general
 *  - 24-hour hourly activity curve generation
 *  - Best fishing windows recommendation
 *  - Water temperature estimation from air temp + season
 */
class FishActivityEngine
{
    /** Species-specific temperature ranges (°C) and behaviour flags */
    private const SPECIES_PROFILES = [
        'general' => [
            'label_en' => 'General (All species)',
            'label_bg' => 'Общо (всички видове)',
            'temp' => ['spring' => [12,16,20], 'summer' => [18,22,26], 'autumn' => [14,18,22], 'winter' => [6,10,14]],
            'nocturnal' => false,
            'pressure_sensitive' => 1.0,
            'wind_tolerance' => 7,
        ],
        'carp' => [
            'label_en' => 'Carp',
            'label_bg' => 'Шаран',
            'temp' => ['spring' => [14,18,22], 'summer' => [20,24,28], 'autumn' => [14,18,22], 'winter' => [4,8,12]],
            'nocturnal' => true,
            'pressure_sensitive' => 1.2,
            'wind_tolerance' => 6,
        ],
        'trout' => [
            'label_en' => 'Trout',
            'label_bg' => 'Пъстърва',
            'temp' => ['spring' => [8,12,16], 'summer' => [10,14,18], 'autumn' => [8,12,16], 'winter' => [4,8,12]],
            'nocturnal' => false,
            'pressure_sensitive' => 0.9,
            'wind_tolerance' => 5,
        ],
        'pike' => [
            'label_en' => 'Pike',
            'label_bg' => 'Щука',
            'temp' => ['spring' => [10,14,18], 'summer' => [16,20,24], 'autumn' => [10,14,18], 'winter' => [4,8,12]],
            'nocturnal' => false,
            'pressure_sensitive' => 1.1,
            'wind_tolerance' => 8,
        ],
        'catfish' => [
            'label_en' => 'Catfish',
            'label_bg' => 'Сом',
            'temp' => ['spring' => [16,20,24], 'summer' => [22,26,30], 'autumn' => [16,20,24], 'winter' => [6,10,14]],
            'nocturnal' => true,
            'pressure_sensitive' => 1.3,
            'wind_tolerance' => 9,
        ],
        'perch' => [
            'label_en' => 'Perch',
            'label_bg' => 'Костур',
            'temp' => ['spring' => [10,14,18], 'summer' => [16,20,24], 'autumn' => [10,14,18], 'winter' => [4,8,12]],
            'nocturnal' => false,
            'pressure_sensitive' => 1.0,
            'wind_tolerance' => 6,
        ],
        'bass' => [
            'label_en' => 'Bass',
            'label_bg' => 'Черен бас',
            'temp' => ['spring' => [14,18,22], 'summer' => [20,24,28], 'autumn' => [14,18,22], 'winter' => [6,10,14]],
            'nocturnal' => false,
            'pressure_sensitive' => 1.1,
            'wind_tolerance' => 7,
        ],
    ];

    private const WEIGHTS = [
        'solunar'       => 0.30,
        'time'          => 0.20,
        'pressure'      => 0.18,
        'temperature'   => 0.12,
        'wind_cloud'    => 0.08,
        'humidity'      => 0.05,
        'precipitation' => 0.04,
        'moon_light'    => 0.03,
    ];

    private array $weather;
    private float $lat;
    private float $lon;
    private string $species;
    private string $season;
    private int $month;
    private array $moonData;
    private array $solunarPeriods;
    private array $sunTimes;

    /**
     * Calculate activity for current moment.
     *
     * @param array  $weather  Weather array from getWeatherData()
     * @param float  $lat      Latitude
     * @param float  $lon      Longitude
     * @param string $species  Species key (default: general)
     * @return array Full result with score, factors, hourly curve, best times
     */
    public function calculate(array $weather, float $lat = 42.7, float $lon = 25.5, string $species = 'general'): array
    {
        $this->weather = $weather;
        $this->lat = $lat;
        $this->lon = $lon;
        $this->species = isset(self::SPECIES_PROFILES[$species]) ? $species : 'general';
        $this->month = (int) date('n');
        $this->season = $this->getSeason($this->month);
        $this->moonData = $this->getMoonPhase();
        $this->solunarPeriods = $this->calculateSolunarPeriods();
        $this->sunTimes = $this->getSunTimes();

        $hour = (int) date('H');
        $minute = (int) date('i');
        $currentTime = $hour + ($minute / 60);

        $factors = $this->scoreAllFactors($currentTime);
        $baseScore = $this->weightedSum($factors);
        $multiplier = $this->comboMultiplier($factors);
        $totalScore = (int) round(min(100, max(0, $baseScore * $multiplier)));

        // 24-hour curve
        $hourlyCurve = $this->generateHourlyCurve();
        $bestTimes = $this->findBestTimes($hourlyCurve);

        $profile = self::SPECIES_PROFILES[$this->species];

        return [
            'total_score'    => $totalScore,
            'species'        => $this->species,
            'species_label'  => $profile['label_en'],
            'factors'        => $this->formatFactors($factors),
            'moon_phase'     => $this->moonData,
            'solunar_periods'=> $this->solunarPeriods,
            'hourly_curve'   => $hourlyCurve,
            'best_times'     => $bestTimes,
            'weather_summary'=> [
                'temperature'   => round($this->weather['temperature'], 1),
                'feels_like'    => round($this->weather['feels_like'] ?? $this->weather['temperature'], 1),
                'wind_speed'    => round($this->weather['wind_speed'], 1),
                'pressure'      => $this->weather['pressure'],
                'humidity'      => $this->weather['humidity'],
                'clouds'        => $this->weather['clouds'],
                'conditions'    => $this->weather['weather'],
                'description'   => $this->weather['description'] ?? '',
            ],
            'water_temp_est' => $this->estimateWaterTemp(),
        ];
    }

    /**
     * Return available species profiles.
     */
    public static function getSpeciesList(): array
    {
        $list = [];
        foreach (self::SPECIES_PROFILES as $key => $p) {
            $list[$key] = ['en' => $p['label_en'], 'bg' => $p['label_bg']];
        }
        return $list;
    }

    // ═══════════════════════════════════════════════════════════
    //  Factor Scoring
    // ═══════════════════════════════════════════════════════════

    private function scoreAllFactors(float $time): array
    {
        return [
            'solunar'       => $this->scoreSolunar($time),
            'time'          => $this->scoreTime($time),
            'pressure'      => $this->scorePressure(),
            'temperature'   => $this->scoreTemperature(),
            'wind_cloud'    => $this->scoreWindCloud($time),
            'humidity'      => $this->scoreHumidity(),
            'precipitation' => $this->scorePrecipitation(),
            'moon_light'    => $this->scoreMoonLight($time),
        ];
    }

    private function weightedSum(array $factors): float
    {
        $sum = 0;
        foreach (self::WEIGHTS as $key => $w) {
            $sum += $factors[$key]['score'] * $w;
        }
        return $sum;
    }

    /**
     * Combo multiplier: synergy when multiple factors align perfectly.
     */
    private function comboMultiplier(array $factors): float
    {
        $sol = $factors['solunar']['score'];
        $time = $factors['time']['score'];
        $press = $factors['pressure']['score'];

        $m = 1.0;
        if ($sol > 85 && $time > 85 && $press > 80) {
            $m = 1.20;
        } elseif ($sol > 85 && ($time > 80 || $press > 75)) {
            $m = 1.12;
        } elseif ($sol > 70 && $time > 80) {
            $m = 1.08;
        } elseif ($sol > 70 || $time > 75) {
            $m = 1.03;
        }

        // Weather combo
        $weather = $this->weather['weather'];
        $wind = $this->weather['wind_speed'];
        if ($weather === 'Rain' && $wind < 4) {
            $m *= 1.05;
        } elseif ($weather === 'Rain' && $wind > 7) {
            $m *= 0.88;
        } elseif ($weather === 'Thunderstorm') {
            $m *= 0.72;
        } elseif ($weather === 'Drizzle') {
            $m *= 1.06;
        }

        // Negative synergy
        if ($sol < 30 && $time < 40 && $press < 50) {
            $m *= 0.82;
        }

        return $m;
    }

    // ── 1. Solunar Score ──────────────────────────────────────

    private function scoreSolunar(float $currentTime): array
    {
        $inMajor = false;
        $inMinor = false;
        $nearPeak = false;
        $minutesToPeak = 999;
        $closestPeriod = null;
        $distanceToClosest = 999;

        foreach ($this->solunarPeriods as $key => $period) {
            $isMajor = strpos($key, 'major') !== false;
            $start = $this->normalizeHour($period['start']);
            $end   = $this->normalizeHour($period['end']);
            $peak  = $this->normalizeHour($period['peak']);

            $inPeriod = ($start < $end)
                ? ($currentTime >= $start && $currentTime <= $end)
                : ($currentTime >= $start || $currentTime <= $end);

            if ($inPeriod) {
                if ($isMajor) {
                    $inMajor = true;
                    $dist = abs($currentTime - $peak);
                    if ($dist > 12) $dist = 24 - $dist;
                    if ($dist < $minutesToPeak) {
                        $minutesToPeak = $dist;
                        $nearPeak = ($dist < 0.5);
                    }
                } else {
                    $inMinor = true;
                }
            }

            $distToPeriod = $this->hourDistance($currentTime, $peak);
            if ($distToPeriod < $distanceToClosest) {
                $distanceToClosest = $distToPeriod;
                $closestPeriod = $isMajor ? 'major' : 'minor';
            }
        }

        $moonMult = $this->moonData['score'] / 100;
        $distBonus = ($this->moonData['score'] > 90) ? 1.15 : (($this->moonData['score'] < 40) ? 0.85 : 1.0);

        if ($inMajor && $nearPeak) {
            return ['score' => min(100, 98 * $moonMult * $distBonus), 'impact' => 'solunar_major_peak'];
        }
        if ($inMajor) {
            return ['score' => min(100, 88 * $moonMult * $distBonus), 'impact' => 'solunar_major'];
        }
        if ($inMinor) {
            return ['score' => min(100, 72 * $moonMult * $distBonus), 'impact' => 'solunar_minor'];
        }
        if ($distanceToClosest < 1.0) {
            $s = 60 - ($distanceToClosest * 10);
            return ['score' => max(40, $s * $moonMult), 'impact' => 'solunar_near'];
        }
        if ($distanceToClosest < 2.0) {
            $s = 50 - ($distanceToClosest * 5);
            return ['score' => max(30, $s * $moonMult), 'impact' => 'solunar_between'];
        }
        return ['score' => max(15, 35 * $moonMult), 'impact' => 'solunar_outside'];
    }

    // ── 2. Time of Day ────────────────────────────────────────

    private function scoreTime(float $currentTime): array
    {
        $sunrise = $this->sunTimes['sunrise'];
        $sunset  = $this->sunTimes['sunset'];

        $profile = self::SPECIES_PROFILES[$this->species];

        // Nocturnal species prefer night
        if ($profile['nocturnal'] && ($currentTime < $sunrise - 1 || $currentTime > $sunset + 1)) {
            return ['score' => 85, 'impact' => 'time_nocturnal_peak'];
        }

        $dawnStart = $sunrise - 1.0;
        $dawnEnd   = $sunrise + 1.5;
        $duskStart = $sunset - 1.5;
        $duskEnd   = $sunset + 1.0;

        if (($currentTime >= $dawnStart && $currentTime <= $dawnEnd) ||
            ($currentTime >= $duskStart && $currentTime <= $duskEnd)) {
            if (abs($currentTime - $sunrise) < 0.5 || abs($currentTime - $sunset) < 0.5) {
                return ['score' => 100, 'impact' => 'time_golden_hour'];
            }
            return ['score' => 90, 'impact' => 'time_dawn_dusk'];
        }

        if (($currentTime > $dawnEnd && $currentTime < $sunrise + 3) ||
            ($currentTime < $duskStart && $currentTime > $sunset - 3)) {
            return ['score' => 75, 'impact' => 'time_good'];
        }

        if ($currentTime < $sunrise - 1 || $currentTime > $sunset + 1) {
            $sc = $profile['nocturnal'] ? 70 : 55;
            return ['score' => $sc, 'impact' => 'time_night'];
        }

        $noon = ($sunrise + $sunset) / 2;
        if (abs($currentTime - $noon) < 2) {
            return ['score' => 30, 'impact' => 'time_noon'];
        }

        return ['score' => 50, 'impact' => 'time_day'];
    }

    // ── 3. Barometric Pressure ────────────────────────────────

    private function scorePressure(): array
    {
        $p = $this->weather['pressure'];
        $sens = self::SPECIES_PROFILES[$this->species]['pressure_sensitive'];

        if ($p >= 1012 && $p <= 1016) {
            return ['score' => min(100, 100 * $sens), 'impact' => 'pressure_perfect'];
        }
        if ($p >= 1010 && $p < 1020) {
            return ['score' => min(100, 90 * $sens), 'impact' => 'pressure_stable'];
        }
        if ($p >= 1005 && $p < 1010) {
            return ['score' => min(100, 82 * $sens), 'impact' => 'pressure_falling_slight'];
        }
        if ($p >= 995 && $p < 1005) {
            return ['score' => min(100, 72 * $sens), 'impact' => 'pressure_falling'];
        }
        if ($p >= 1020 && $p <= 1030) {
            return ['score' => 70, 'impact' => 'pressure_high_stable'];
        }
        if ($p < 995) {
            return ['score' => 35, 'impact' => 'pressure_storm'];
        }
        if ($p > 1030) {
            return ['score' => 50, 'impact' => 'pressure_very_high'];
        }
        return ['score' => 60, 'impact' => 'pressure_moderate'];
    }

    // ── 4. Temperature ────────────────────────────────────────

    private function scoreTemperature(): array
    {
        $temp = $this->weather['temperature'];
        $profile = self::SPECIES_PROFILES[$this->species];
        $range = $profile['temp'][$this->season]; // [min, ideal, max]
        $ideal = $range[1];

        if (abs($temp - $ideal) < 2) {
            return ['score' => 100, 'impact' => 'temp_ideal'];
        }
        if ($temp >= $range[0] && $temp <= $range[2]) {
            $dev = abs($temp - $ideal);
            return ['score' => max(70, 100 - $dev * 8), 'impact' => 'temp_good'];
        }
        if ($temp > $range[2] && $temp <= $range[2] + 6) {
            return ['score' => max(30, 70 - ($temp - $range[2]) * 5), 'impact' => 'temp_warm'];
        }
        if ($temp < $range[0] && $temp >= $range[0] - 6) {
            return ['score' => max(30, 70 - ($range[0] - $temp) * 5), 'impact' => 'temp_cool'];
        }
        if ($temp > 32) {
            return ['score' => 12, 'impact' => 'temp_extreme_hot'];
        }
        if ($temp < 2) {
            return ['score' => 15, 'impact' => 'temp_extreme_cold'];
        }
        return ['score' => 25, 'impact' => 'temp_unfavorable'];
    }

    // ── 5. Wind & Cloud Cover ─────────────────────────────────

    private function scoreWindCloud(float $currentTime): array
    {
        $wind = $this->weather['wind_speed'];
        $clouds = $this->weather['clouds'];
        $tol = self::SPECIES_PROFILES[$this->species]['wind_tolerance'];

        if ($wind <= 2) { $ws = 95; }
        elseif ($wind <= $tol * 0.6) { $ws = 85; }
        elseif ($wind <= $tol) { $ws = 65; }
        elseif ($wind <= $tol * 1.4) { $ws = 40; }
        else { $ws = 18; }

        $isDaytime = ($currentTime >= $this->sunTimes['sunrise'] && $currentTime <= $this->sunTimes['sunset']);
        if ($isDaytime) {
            if ($clouds <= 20) { $cf = 68; }
            elseif ($clouds <= 50) { $cf = 100; }
            elseif ($clouds <= 80) { $cf = 90; }
            else { $cf = 75; }
        } else {
            $cf = 85;
        }

        $score = $ws * 0.6 + $cf * 0.4;
        return ['score' => $score, 'impact' => $wind <= $tol ? 'wind_good' : 'wind_strong'];
    }

    // ── 6. Humidity ───────────────────────────────────────────

    private function scoreHumidity(): array
    {
        $h = $this->weather['humidity'];
        if ($h >= 60 && $h <= 80) {
            return ['score' => 95, 'impact' => 'humidity_ideal'];
        }
        if ($h > 80 && $h <= 95) {
            return ['score' => 85, 'impact' => 'humidity_high'];
        }
        if ($h >= 45 && $h < 60) {
            return ['score' => 75, 'impact' => 'humidity_moderate'];
        }
        if ($h < 45) {
            return ['score' => 55, 'impact' => 'humidity_low'];
        }
        return ['score' => 60, 'impact' => 'humidity_extreme'];
    }

    // ── 7. Precipitation ──────────────────────────────────────

    private function scorePrecipitation(): array
    {
        $w = $this->weather['weather'];
        $wind = $this->weather['wind_speed'];

        if ($w === 'Drizzle') {
            return ['score' => 95, 'impact' => 'precip_drizzle'];
        }
        if ($w === 'Rain' && $wind < 4) {
            return ['score' => 88, 'impact' => 'precip_light_rain'];
        }
        if ($w === 'Rain') {
            return ['score' => 55, 'impact' => 'precip_rain_windy'];
        }
        if ($w === 'Thunderstorm') {
            return ['score' => 20, 'impact' => 'precip_storm'];
        }
        if ($w === 'Snow') {
            return ['score' => 30, 'impact' => 'precip_snow'];
        }
        // Clear / Clouds / Mist / Fog
        return ['score' => 75, 'impact' => 'precip_none'];
    }

    // ── 8. Moon Illumination (night hunting) ──────────────────

    private function scoreMoonLight(float $currentTime): array
    {
        $isNight = ($currentTime < $this->sunTimes['sunrise'] || $currentTime > $this->sunTimes['sunset']);
        $illum = $this->moonData['illumination'];
        $nocturnal = self::SPECIES_PROFILES[$this->species]['nocturnal'];

        if (!$isNight) {
            return ['score' => 70, 'impact' => 'moonlight_daytime'];
        }

        // At night, moderate illumination helps predators
        if ($nocturnal) {
            if ($illum >= 40 && $illum <= 70) {
                return ['score' => 100, 'impact' => 'moonlight_ideal_nocturnal'];
            }
            if ($illum > 70) {
                return ['score' => 80, 'impact' => 'moonlight_bright'];
            }
            return ['score' => 65, 'impact' => 'moonlight_dark'];
        }

        // Non-nocturnal species: darker = worse
        if ($illum < 20) {
            return ['score' => 50, 'impact' => 'moonlight_very_dark'];
        }
        return ['score' => 70, 'impact' => 'moonlight_night'];
    }

    // ═══════════════════════════════════════════════════════════
    //  24-Hour Prediction Curve
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate activity prediction for each hour of the day.
     * @return array<int, int> hour => score (0-100)
     */
    public function generateHourlyCurve(): array
    {
        $curve = [];
        for ($h = 0; $h < 24; $h++) {
            $time = $h + 0.5; // mid-hour
            $factors = $this->scoreAllFactors($time);
            $base = $this->weightedSum($factors);
            $mult = $this->comboMultiplier($factors);
            $curve[$h] = (int) round(min(100, max(0, $base * $mult)));
        }
        return $curve;
    }

    /**
     * Find the top 3 best fishing windows (contiguous hours >= threshold).
     */
    private function findBestTimes(array $curve): array
    {
        $windows = [];
        $threshold = max(50, max($curve) * 0.75);
        $inWindow = false;
        $start = 0;
        $peak = 0;
        $peakScore = 0;

        for ($h = 0; $h < 24; $h++) {
            if ($curve[$h] >= $threshold) {
                if (!$inWindow) {
                    $inWindow = true;
                    $start = $h;
                    $peak = $h;
                    $peakScore = $curve[$h];
                }
                if ($curve[$h] > $peakScore) {
                    $peak = $h;
                    $peakScore = $curve[$h];
                }
            } else {
                if ($inWindow) {
                    $windows[] = [
                        'start' => sprintf('%02d:00', $start),
                        'end'   => sprintf('%02d:00', $h),
                        'peak_hour' => sprintf('%02d:00', $peak),
                        'peak_score' => $peakScore,
                    ];
                    $inWindow = false;
                }
            }
        }
        if ($inWindow) {
            $windows[] = [
                'start' => sprintf('%02d:00', $start),
                'end'   => '24:00',
                'peak_hour' => sprintf('%02d:00', $peak),
                'peak_score' => $peakScore,
            ];
        }

        // Sort by peak score descending, return top 3
        usort($windows, fn($a, $b) => $b['peak_score'] <=> $a['peak_score']);
        return array_slice($windows, 0, 3);
    }

    // ═══════════════════════════════════════════════════════════
    //  Astronomical Calculations
    // ═══════════════════════════════════════════════════════════

    public function getMoonPhase(): array
    {
        $year  = (int) date('Y');
        $month = (int) date('n');
        $day   = (int) date('j');

        $baseDate    = mktime(0, 0, 0, 1, 6, 2000);
        $currentDate = mktime(0, 0, 0, $month, $day, $year);
        $daysSince   = ($currentDate - $baseDate) / 86400;
        $moonCycle   = 29.53058867;
        $phase       = fmod($daysSince, $moonCycle);
        if ($phase < 0) $phase += $moonCycle;
        $phasePercent = ($phase / $moonCycle) * 100;
        $moonAge     = $phase;

        // Illumination: 0 at new moon (0%), 100 at full (50%), back to 0
        $illumination = round((1 - cos(2 * M_PI * $phase / $moonCycle)) / 2 * 100);

        $phases = [
            [6.25,  '🌑', 'new_moon',            100],
            [18.75, '🌒', 'waxing_crescent',       70],
            [31.25, '🌓', 'first_quarter',          75],
            [43.75, '🌔', 'waxing_gibbous',         85],
            [56.25, '🌕', 'full_moon',             100],
            [68.75, '🌖', 'waning_gibbous',         85],
            [81.25, '🌗', 'last_quarter',            75],
            [93.75, '🌘', 'waning_crescent',         70],
        ];

        foreach ($phases as $i => [$limit, $icon, $key, $score]) {
            if ($phasePercent < $limit || ($i === 0 && $phasePercent >= 93.75)) {
                return [
                    'name'         => $key,
                    'icon'         => $icon,
                    'score'        => $score,
                    'age'          => $moonAge,
                    'illumination' => $illumination,
                    'phase_pct'    => round($phasePercent, 1),
                ];
            }
        }

        // Fallback — waning crescent
        return ['name' => 'waning_crescent', 'icon' => '🌘', 'score' => 70, 'age' => $moonAge, 'illumination' => $illumination, 'phase_pct' => round($phasePercent, 1)];
    }

    private function calculateSolunarPeriods(): array
    {
        try {
            $jd = $this->julianDay();
            $moonTransit  = $this->moonTransit($jd);
            $moonTimes    = $this->moonRiseSet($jd);
            $moonUnderfoot = fmod($moonTransit + 12, 24);
            if ($moonUnderfoot < 0) $moonUnderfoot += 24;

            return [
                'major1' => ['start' => $moonTransit - 1.0, 'end' => $moonTransit + 1.0, 'peak' => $moonTransit],
                'major2' => ['start' => $moonUnderfoot - 1.0, 'end' => $moonUnderfoot + 1.0, 'peak' => $moonUnderfoot],
                'minor1' => ['start' => $moonTimes['rise'] - 0.5, 'end' => $moonTimes['rise'] + 0.5, 'peak' => $moonTimes['rise']],
                'minor2' => ['start' => $moonTimes['set'] - 0.5, 'end' => $moonTimes['set'] + 0.5, 'peak' => $moonTimes['set']],
            ];
        } catch (\Exception $e) {
            error_log("Solunar calculation error: " . $e->getMessage());
            return $this->solunarFallback();
        }
    }

    private function solunarFallback(): array
    {
        $age = $this->moonData['age'];
        $lunarNoon = fmod(12 + ($age * 0.8), 24);
        if ($lunarNoon < 0) $lunarNoon += 24;
        $lunarMidnight = fmod($lunarNoon + 12, 24);
        $moonrise = fmod($lunarNoon - 6 + 24, 24);
        $moonset  = fmod($lunarNoon + 6, 24);

        return [
            'major1' => ['start' => $lunarNoon - 1.0, 'end' => $lunarNoon + 1.0, 'peak' => $lunarNoon],
            'major2' => ['start' => $lunarMidnight - 1.0, 'end' => $lunarMidnight + 1.0, 'peak' => $lunarMidnight],
            'minor1' => ['start' => $moonrise - 0.5, 'end' => $moonrise + 0.5, 'peak' => $moonrise],
            'minor2' => ['start' => $moonset - 0.5, 'end' => $moonset + 0.5, 'peak' => $moonset],
        ];
    }

    private function julianDay(): float
    {
        $y = (int) date('Y');
        $m = (int) date('n');
        $d = (int) date('j');
        $a = floor((14 - $m) / 12);
        $yy = $y + 4800 - $a;
        $mm = $m + 12 * $a - 3;
        return $d + floor((153 * $mm + 2) / 5) + 365 * $yy + floor($yy / 4) - floor($yy / 100) + floor($yy / 400) - 32045;
    }

    private function moonTransit(float $jd): float
    {
        $L  = fmod(218.316 + 13.176396 * ($jd - 2451545.0), 360);
        $Mm = fmod(134.963 + 13.064993 * ($jd - 2451545.0), 360);
        $RA = $L + 6.289 * sin(deg2rad($Mm));
        $LST = fmod(280.46061837 + 360.98564736629 * ($jd - 2451545.0) + $this->lon, 360);
        $HA = $LST - $RA;
        if ($HA < 0) $HA += 360;
        if ($HA > 180) $HA -= 360;
        $t = 12.0 - ($HA / 15.0);
        if ($t < 0) $t += 24;
        if ($t >= 24) $t -= 24;
        return $t;
    }

    private function moonRiseSet(float $jd): array
    {
        $L  = fmod(218.316 + 13.176396 * ($jd - 2451545.0), 360);
        $Mm = fmod(134.963 + 13.064993 * ($jd - 2451545.0), 360);
        $lambda = $L + 6.289 * sin(deg2rad($Mm));
        $beta   = 5.128 * sin(deg2rad($Mm));
        $eps    = 23.439 - 0.0000004 * ($jd - 2451545.0);

        $RA = rad2deg(atan2(
            sin(deg2rad($lambda)) * cos(deg2rad($eps)) - tan(deg2rad($beta)) * sin(deg2rad($eps)),
            cos(deg2rad($lambda))
        ));
        if ($RA < 0) $RA += 360;

        $decV = max(-1, min(1,
            sin(deg2rad($beta)) * cos(deg2rad($eps)) + cos(deg2rad($beta)) * sin(deg2rad($eps)) * sin(deg2rad($lambda))
        ));
        $dec = rad2deg(asin($decV));

        $LST0 = fmod(280.46061837 + 360.98564736629 * ($jd - 2451545.0) + $this->lon, 360);
        $h0 = -0.833;
        $cosH = max(-1, min(1,
            (sin(deg2rad($h0)) - sin(deg2rad($this->lat)) * sin(deg2rad($dec))) /
            (cos(deg2rad($this->lat)) * cos(deg2rad($dec)))
        ));

        if ($cosH >= 0.9999 || $cosH <= -0.9999) {
            $transit = $this->moonTransit($jd);
            return ['rise' => fmod($transit - 6 + 24, 24), 'set' => fmod($transit + 6, 24)];
        }

        $H = rad2deg(acos($cosH));
        $transit = ($RA - $LST0) / 15.0;
        if ($transit < 0) $transit += 24;
        if ($transit >= 24) $transit -= 24;

        $rise = fmod($transit - $H / 15.0 + 24, 24);
        $set  = fmod($transit + $H / 15.0 + 24, 24);
        return ['rise' => $rise, 'set' => $set];
    }

    // ═══════════════════════════════════════════════════════════
    //  Helpers
    // ═══════════════════════════════════════════════════════════

    private function getSeason(int $month): string
    {
        if ($month >= 3 && $month <= 5)  return 'spring';
        if ($month >= 6 && $month <= 8)  return 'summer';
        if ($month >= 9 && $month <= 11) return 'autumn';
        return 'winter';
    }

    private function getSunTimes(): array
    {
        $map = [
            'spring' => ['sunrise' => 6.0, 'sunset' => 19.0],
            'summer' => ['sunrise' => 5.5, 'sunset' => 20.5],
            'autumn' => ['sunrise' => 6.5, 'sunset' => 18.0],
            'winter' => ['sunrise' => 7.5, 'sunset' => 17.0],
        ];
        return $map[$this->season];
    }

    /**
     * Estimate water temperature from air temperature + seasonal lag.
     */
    private function estimateWaterTemp(): float
    {
        $airTemp = $this->weather['temperature'];
        // Water lags behind air temperature; average offset ~3-4°C in summer, smaller in winter
        $offset = match ($this->season) {
            'spring' => -2,
            'summer' => -4,
            'autumn' => 2,
            'winter' => 2,
        };
        return round(max(0, $airTemp + $offset), 1);
    }

    private function normalizeHour(float $h): float
    {
        $h = fmod($h, 24);
        return $h < 0 ? $h + 24 : $h;
    }

    private function hourDistance(float $a, float $b): float
    {
        $d = abs($a - $b);
        return $d > 12 ? 24 - $d : $d;
    }

    /**
     * Format factor scores for API response.
     */
    private function formatFactors(array $factors): array
    {
        $result = [];
        foreach ($factors as $key => $f) {
            $result[$key] = [
                'score'  => round($f['score']),
                'impact' => $f['impact'],
                'weight' => self::WEIGHTS[$key],
            ];
        }
        return $result;
    }
}
