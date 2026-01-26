<?php
session_start();
require '../../config/database.php';
require '../../config/weather_api.php'; // Load unified weather functions

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get_feed';

header('Content-Type: application/json');

if ($action === 'calculate_fish_activity') {
    // Calculate fish activity based on weather and environmental conditions
    $location = $_GET['location'] ?? '';
    $lat = $_GET['lat'] ?? 42.7;
    $lon = $_GET['lon'] ?? 25.5;
    
    if (empty($location)) {
        http_response_code(400);
        exit(json_encode(['error' => 'Location required']));
    }
    
    try {
        // Get weather data
        $weatherData = getWeatherData($lat, $lon);
        
        // Calculate activity score based on multiple factors
        $activityScore = calculateFishActivityScore($weatherData, $lat, $lon);
        
        exit(json_encode([
            'success' => true,
            'activity_score' => $activityScore['total_score'],
            'factors' => $activityScore['factors'],
            'location' => $location
        ]));
    } catch (Exception $e) {
        error_log("Fish activity calculation error: " . $e->getMessage());
        exit(json_encode([
            'success' => false,
            'error' => 'Calculation error: ' . $e->getMessage()
        ]));
    }
    
} else if ($action === 'get_fish_activity') {
    // Old endpoint - fallback
    $stmt = $pdo->prepare("
        SELECT 
            p.id, p.user_id, p.title, p.content, p.image, p.created_at,
            u.username, up.avatar_url,
            'post' as action_type,
            p.title as description
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE p.user_id IN (
            SELECT following_id FROM follows WHERE follower_id = ?
        ) OR p.user_id = ?
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $userId, (int)$limit, (int)$offset]);
    $activities = $stmt->fetchAll();
    
    exit(json_encode([
        'success' => true,
        'activities' => $activities
    ]));
    
} else if ($action === 'add_activity') {
    // Activity logging is disabled (table doesn't exist)
    exit(json_encode(['success' => true, 'message' => 'Activity logging disabled']));
    
} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid action']));
}

// Function to calculate fish activity score (Professional Solunar-based algorithm)
function calculateFishActivityScore($weather, $lat = 42.7, $lon = 25.5) {
    $factors = [];
    $hour = (int)date('H');
    $minute = (int)date('i');
    $currentTime = $hour + ($minute / 60);
    
    // Get moon data for solunar calculations
    $moonData = getMoonPhase();
    $solunarPeriods = calculateSolunarPeriods($moonData, $lat, $lon);
    
    // Get current season
    $month = (int)date('n');
    $season = getSeason($month);
    
    // 1. Solunar Score (35% weight) - PRIMARY FACTOR like Fishing Points
    $solunarData = calculateSolunarScore($currentTime, $solunarPeriods, $moonData);
    $solunarScore = $solunarData['score'];
    $solunarImpact = $solunarData['impact'];
    
    // 2. Time of Day (25% weight) - Dawn/Dusk feeding patterns
    $timeData = calculateTimeScore($currentTime, $season);
    $timeScore = $timeData['score'];
    $timeImpact = $timeData['impact'];
    
    // 3. Barometric Pressure (20% weight) - Trend matters
    $pressure = $weather['pressure'];
    $pressureData = calculatePressureScore($pressure);
    $pressureScore = $pressureData['score'];
    $pressureImpact = $pressureData['impact'];
    
    // 4. Temperature (12% weight) - Species & Season Dependent
    $temp = $weather['temperature'];
    $tempScore = calculateTemperatureScore($temp, $season);
    
    // 5. Wind & Cloud Cover (8% weight) - Secondary factors
    $wind = $weather['wind_speed'];
    $clouds = $weather['clouds'];
    $windCloudData = calculateWindCloudScore($wind, $clouds, $currentTime);
    $windScore = $windCloudData['score'];
    $windImpact = $windCloudData['impact'];
    
    // Calculate base weighted score (matching Fishing Points weights)
    $baseScore = (
        ($solunarScore * 0.35) +
        ($timeScore * 0.25) +
        ($pressureScore * 0.20) +
        ($tempScore['score'] * 0.12) +
        ($windScore * 0.08)
    );
    
    // Apply multipliers for optimal combinations (Fishing Points style)
    $multiplier = 1.0;
    
    // PERFECT CONDITIONS: Major period + Dawn/Dusk + Good pressure
    if ($solunarScore > 85 && $timeScore > 85 && $pressureScore > 80) {
        $multiplier = 1.20; // This is rare but EXCELLENT
    }
    // EXCELLENT: Major period + Good time OR good pressure
    else if ($solunarScore > 85 && ($timeScore > 80 || $pressureScore > 75)) {
        $multiplier = 1.12;
    }
    // VERY GOOD: Major/Minor + Dawn/Dusk
    else if ($solunarScore > 70 && $timeScore > 80) {
        $multiplier = 1.08;
    }
    // GOOD: Either solunar or time is good
    else if ($solunarScore > 70 || $timeScore > 75) {
        $multiplier = 1.03;
    }
    
    // Weather modifiers
    if ($weather['weather'] === 'Rain' && $wind < 4) {
        $multiplier *= 1.04; // Light rain slightly increases activity
    } else if ($weather['weather'] === 'Rain' && $wind > 7) {
        $multiplier *= 0.88; // Heavy rain/storm decreases
    } else if ($weather['weather'] === 'Thunderstorm') {
        $multiplier *= 0.75; // Thunder significantly decreases
    }
    
    // Penalty for bad combinations
    if ($solunarScore < 30 && $timeScore < 40 && $pressureScore < 50) {
        $multiplier *= 0.85; // Everything is bad
    }
    
    $totalScore = round(min(100, $baseScore * $multiplier));
    
    return [
        'total_score' => max(0, $totalScore),
        'factors' => [
            'temperature_score' => round($tempScore['score']),
            'temperature_impact' => $tempScore['impact'],
            'pressure_score' => round($pressureScore),
            'pressure_impact' => $pressureImpact,
            'solunar_score' => round($solunarScore),
            'solunar_impact' => $solunarImpact,
            'wind_score' => round($windScore),
            'wind_impact' => $windImpact,
            'time_score' => round($timeScore),
            'time_impact' => $timeImpact,
            'moon_phase' => $moonData['name'],
            'weather' => [
                'temperature' => round($weather['temperature'], 1),
                'wind_speed' => round($weather['wind_speed'], 1),
                'pressure' => $weather['pressure'],
                'humidity' => $weather['humidity'],
                'clouds' => $clouds,
                'conditions' => $weather['weather']
            ],
            'solunar_periods' => $solunarPeriods
        ]
    ];
}

// Season detection
function getSeason($month) {
    if ($month >= 3 && $month <= 5) return 'spring';
    if ($month >= 6 && $month <= 8) return 'summer';
    if ($month >= 9 && $month <= 11) return 'autumn';
    return 'winter';
}

// Temperature scoring with seasonal adjustment
function calculateTemperatureScore($temp, $season) {
    $optimalRanges = [
        'spring' => ['min' => 12, 'ideal' => 16, 'max' => 20],
        'summer' => ['min' => 18, 'ideal' => 22, 'max' => 26],
        'autumn' => ['min' => 14, 'ideal' => 18, 'max' => 22],
        'winter' => ['min' => 6, 'ideal' => 10, 'max' => 14]
    ];
    
    $range = $optimalRanges[$season];
    $ideal = $range['ideal'];
    
    // Perfect temperature
    if (abs($temp - $ideal) < 2) {
        return [
            'score' => 100,
            'impact' => "🌡️ Идеална температура за {$season} ({$temp}°C)"
        ];
    }
    
    // Within optimal range
    if ($temp >= $range['min'] && $temp <= $range['max']) {
        $deviation = abs($temp - $ideal);
        $score = 100 - ($deviation * 8);
        return [
            'score' => max(70, $score),
            'impact' => "🌡️ Отлична температура - рибата е активна"
        ];
    }
    
    // Outside optimal but acceptable
    if ($temp > $range['max'] && $temp <= $range['max'] + 6) {
        $score = 70 - (($temp - $range['max']) * 5);
        return [
            'score' => max(30, $score),
            'impact' => "🌡️ Топло - рибата търси по-дълбоки води"
        ];
    }
    
    if ($temp < $range['min'] && $temp >= $range['min'] - 6) {
        $score = 70 - (($range['min'] - $temp) * 5);
        return [
            'score' => max(30, $score),
            'impact' => "🌡️ Прохладно - забавена активност"
        ];
    }
    
    // Extreme conditions
    if ($temp > 30) {
        return ['score' => 15, 'impact' => "🔥 Прекалено горещо - минимална активност"];
    }
    if ($temp < 4) {
        return ['score' => 20, 'impact' => "❄️ Студено - много ниска активност"];
    }
    
    return ['score' => 25, 'impact' => "⚠️ Неблагоприятна температура"];
}

// Barometric pressure scoring - TREND is key
function calculatePressureScore($pressure) {
    // In real implementation, track pressure history
    // For now, simulate trend based on pressure range
    
    // Rising pressure (High pressure = 1020+)
    if ($pressure >= 1020 && $pressure <= 1030) {
        return [
            'score' => 75,
            'impact' => "📈 Високо и стабилно - добри условия"
        ];
    }
    
    // Optimal stable pressure (1010-1020)
    if ($pressure >= 1010 && $pressure < 1020) {
        // Check if near perfect stability point
        if ($pressure >= 1012 && $pressure <= 1016) {
            return [
                'score' => 100,
                'impact' => "📊 Перфектно стабилно налягане - пик активност!"
            ];
        }
        return [
            'score' => 90,
            'impact' => "📊 Стабилно налягане - отлични условия"
        ];
    }
    
    // Slightly falling (1005-1010) - CAN BE GOOD before storm
    if ($pressure >= 1005 && $pressure < 1010) {
        return [
            'score' => 80,
            'impact' => "📉 Леко падащо - рибата усеща промяна (добре!)"
        ];
    }
    
    // Falling pressure (995-1005) - Pre-storm feeding
    if ($pressure >= 995 && $pressure < 1005) {
        return [
            'score' => 70,
            'impact' => "📉 Падащо налягане - активно хранене преди буря"
        ];
    }
    
    // Low pressure (storm)
    if ($pressure < 995) {
        return [
            'score' => 40,
            'impact' => "⛈️ Много ниско - буря, слаба активност"
        ];
    }
    
    // Very high pressure
    if ($pressure > 1030) {
        return [
            'score' => 55,
            'impact' => "📈 Много високо - рибата е пасивна"
        ];
    }
    
    return ['score' => 60, 'impact' => "📊 Средно налягане"];
}

// Solunar theory implementation (Astronomical calculation for accuracy)
function calculateSolunarPeriods($moonData, $lat = 42.7, $lon = 25.5) {
    try {
        // Get current date info
        $year = (int)date('Y');
        $month = (int)date('n');
        $day = (int)date('j');
        
        // Use astronomical calculations for moon position
        // Calculate Julian Day
        $a = floor((14 - $month) / 12);
        $y = $year + 4800 - $a;
        $m = $month + (12 * $a) - 3;
        $jd = $day + floor((153 * $m + 2) / 5) + (365 * $y) + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;
        
        // Calculate moon transit (when moon is highest in sky)
        $moonTransit = calculateMoonTransit($jd, $lon);
        
        // Calculate moonrise and moonset
        $moonTimes = calculateMoonRiseSet($jd, $lat, $lon);
        $moonrise = $moonTimes['rise'];
        $moonset = $moonTimes['set'];
        
        // Moon underfoot (opposite side - 12 hours from transit)
        $moonUnderfoot = fmod($moonTransit + 12, 24);
        if ($moonUnderfoot < 0) $moonUnderfoot += 24;
        
        // MAJOR PERIODS: Moon overhead (transit) and underfoot
        // Duration: 2 hours (1 hour before and after peak)
        $major1Start = $moonTransit - 1.0;
        $major1End = $moonTransit + 1.0;
        $major2Start = $moonUnderfoot - 1.0;
        $major2End = $moonUnderfoot + 1.0;
        
        // MINOR PERIODS: Moonrise and moonset
        // Duration: 1 hour (30 min before and after)
        $minor1Start = $moonrise - 0.5;
        $minor1End = $moonrise + 0.5;
        $minor2Start = $moonset - 0.5;
        $minor2End = $moonset + 0.5;
        
        return [
            'major1' => ['start' => $major1Start, 'end' => $major1End, 'peak' => $moonTransit],
            'major2' => ['start' => $major2Start, 'end' => $major2End, 'peak' => $moonUnderfoot],
            'minor1' => ['start' => $minor1Start, 'end' => $minor1End, 'peak' => $moonrise],
            'minor2' => ['start' => $minor2Start, 'end' => $minor2End, 'peak' => $moonset]
        ];
    } catch (Exception $e) {
        // Fallback to simplified calculation if astronomical fails
        error_log("Solunar calculation error: " . $e->getMessage());
        return calculateSolunarPeriodsSimplified($moonData);
    }
}

// Simplified fallback solunar calculation
function calculateSolunarPeriodsSimplified($moonData) {
    $moonAge = $moonData['age'];
    
    // Approximate moon transit based on moon age
    $lunarNoon = 12 + ($moonAge * 0.8);
    $lunarNoon = fmod($lunarNoon, 24);
    if ($lunarNoon < 0) $lunarNoon += 24;
    
    $lunarMidnight = fmod($lunarNoon + 12, 24);
    if ($lunarMidnight < 0) $lunarMidnight += 24;
    
    $moonrise = fmod($lunarNoon - 6, 24);
    if ($moonrise < 0) $moonrise += 24;
    
    $moonset = fmod($lunarNoon + 6, 24);
    if ($moonset < 0) $moonset += 24;
    
    return [
        'major1' => ['start' => $lunarNoon - 1.0, 'end' => $lunarNoon + 1.0, 'peak' => $lunarNoon],
        'major2' => ['start' => $lunarMidnight - 1.0, 'end' => $lunarMidnight + 1.0, 'peak' => $lunarMidnight],
        'minor1' => ['start' => $moonrise - 0.5, 'end' => $moonrise + 0.5, 'peak' => $moonrise],
        'minor2' => ['start' => $moonset - 0.5, 'end' => $moonset + 0.5, 'peak' => $moonset]
    ];
}

// Calculate moon transit time (when moon crosses meridian)
function calculateMoonTransit($jd, $lon) {
    // Julian centuries from J2000.0
    $T = ($jd - 2451545.0) / 36525.0;
    
    // Moon's mean longitude
    $L = 218.316 + 13.176396 * ($jd - 2451545.0);
    $L = fmod($L, 360);
    
    // Sun's mean anomaly
    $M = 357.529 + 0.98560028 * ($jd - 2451545.0);
    $M = fmod($M, 360);
    
    // Moon's mean anomaly
    $Mm = 134.963 + 13.064993 * ($jd - 2451545.0);
    $Mm = fmod($Mm, 360);
    
    // Moon's argument of latitude
    $F = 93.272 + 13.229350 * ($jd - 2451545.0);
    $F = fmod($F, 360);
    
    // Right ascension (simplified)
    $RA = $L + 6.289 * sin(deg2rad($Mm));
    
    // Local sidereal time
    $LST = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) + $lon;
    $LST = fmod($LST, 360);
    
    // Hour angle
    $HA = $LST - $RA;
    if ($HA < 0) $HA += 360;
    if ($HA > 180) $HA -= 360;
    
    // Transit time in hours
    $transitTime = 12.0 - ($HA / 15.0);
    if ($transitTime < 0) $transitTime += 24;
    if ($transitTime >= 24) $transitTime -= 24;
    
    return $transitTime;
}

// Calculate moonrise and moonset times
function calculateMoonRiseSet($jd, $lat, $lon) {
    // Julian centuries from J2000.0
    $T = ($jd - 2451545.0) / 36525.0;
    
    // Moon's mean longitude
    $L = 218.316 + 13.176396 * ($jd - 2451545.0);
    $L = fmod($L, 360);
    
    // Moon's mean anomaly
    $Mm = 134.963 + 13.064993 * ($jd - 2451545.0);
    $Mm = fmod($Mm, 360);
    
    // Moon's ecliptic longitude (simplified)
    $lambda = $L + 6.289 * sin(deg2rad($Mm));
    
    // Moon's ecliptic latitude (simplified)
    $beta = 5.128 * sin(deg2rad($Mm));
    
    // Obliquity of ecliptic
    $epsilon = 23.439 - 0.0000004 * ($jd - 2451545.0);
    
    // Right ascension and declination
    $RA = atan2(
        sin(deg2rad($lambda)) * cos(deg2rad($epsilon)) - tan(deg2rad($beta)) * sin(deg2rad($epsilon)),
        cos(deg2rad($lambda))
    );
    $RA = rad2deg($RA);
    if ($RA < 0) $RA += 360;
    
    $decValue = sin(deg2rad($beta)) * cos(deg2rad($epsilon)) + 
                 cos(deg2rad($beta)) * sin(deg2rad($epsilon)) * sin(deg2rad($lambda));
    $decValue = max(-1, min(1, $decValue)); // Clamp to valid range
    $dec = asin($decValue);
    $dec = rad2deg($dec);
    
    // Local sidereal time at midnight
    $LST0 = 280.46061837 + 360.98564736629 * ($jd - 2451545.0) + $lon;
    $LST0 = fmod($LST0, 360);
    
    // Hour angle at rise/set (accounting for refraction and moon's semi-diameter)
    $h0 = -0.833; // Standard value for rise/set
    $cosHValue = (sin(deg2rad($h0)) - sin(deg2rad($lat)) * sin(deg2rad($dec))) / 
                 (cos(deg2rad($lat)) * cos(deg2rad($dec)));
    
    // Clamp value to valid range for acos
    $cosHValue = max(-1, min(1, $cosHValue));
    
    // Moon always above or below horizon
    if ($cosHValue >= 0.9999 || $cosHValue <= -0.9999) {
        // Use approximate times based on transit
        $transit = calculateMoonTransit($jd, $lon);
        return [
            'rise' => fmod($transit - 6 + 24, 24),
            'set' => fmod($transit + 6, 24)
        ];
    }
    
    $H = rad2deg(acos($cosHValue));
    
    // Transit time
    $transit = ($RA - $LST0) / 15.0;
    if ($transit < 0) $transit += 24;
    if ($transit >= 24) $transit -= 24;
    
    // Rise and set times
    $rise = $transit - ($H / 15.0);
    $set = $transit + ($H / 15.0);
    
    // Normalize to 0-24 range
    if ($rise < 0) $rise += 24;
    if ($rise >= 24) $rise -= 24;
    if ($set < 0) $set += 24;
    if ($set >= 24) $set -= 24;
    
    return [
        'rise' => $rise,
        'set' => $set
    ];
}

// Calculate solunar score based on current time vs periods (Fishing Points method)
function calculateSolunarScore($currentTime, $periods, $moonData) {
    $inMajor = false;
    $inMinor = false;
    $nearPeak = false;
    $minutesToPeak = 999;
    $closestPeriod = null;
    $distanceToClosest = 999;
    
    foreach ($periods as $key => $period) {
        $isMajor = strpos($key, 'major') !== false;
        
        // Check if current time is in period (handle 24h wrap)
        $start = $period['start'];
        $end = $period['end'];
        $peak = $period['peak'];
        
        // Normalize to 0-24 range
        if ($start < 0) $start += 24;
        if ($end > 24) $end -= 24;
        if ($peak < 0) $peak += 24;
        if ($peak > 24) $peak -= 24;
        
        $inPeriod = false;
        if ($start < $end) {
            $inPeriod = ($currentTime >= $start && $currentTime <= $end);
        } else {
            $inPeriod = ($currentTime >= $start || $currentTime <= $end);
        }
        
        if ($inPeriod) {
            if ($isMajor) {
                $inMajor = true;
                $dist = abs($currentTime - $peak);
                if ($dist < $minutesToPeak) {
                    $minutesToPeak = $dist;
                    $nearPeak = ($dist < 0.5); // Within 30 minutes of peak
                }
            } else {
                $inMinor = true;
            }
        }
        
        // Calculate distance to this period
        $distToPeriod = min(
            abs($currentTime - $start),
            abs($currentTime - $end),
            abs($currentTime - $peak)
        );
        
        if ($distToPeriod < $distanceToClosest) {
            $distanceToClosest = $distToPeriod;
            $closestPeriod = $isMajor ? 'major' : 'minor';
        }
    }
    
    // Moon phase multiplier (New and Full moon are best)
    $moonPhaseMultiplier = $moonData['score'] / 100;
    
    // Moon distance factor (perigee = close = better)
    // Simplified: assume average distance, boost for strong phases
    $moonDistanceBonus = 1.0;
    if ($moonData['score'] > 90) {
        $moonDistanceBonus = 1.15; // Near perigee + good phase
    } else if ($moonData['score'] < 40) {
        $moonDistanceBonus = 0.85; // Far from ideal
    }
    
    // IN PERIOD SCORING
    if ($inMajor && $nearPeak) {
        return [
            'score' => min(100, 98 * $moonPhaseMultiplier * $moonDistanceBonus),
            'impact' => "🌕 MAJOR Period Peak - максимална активност!"
        ];
    }
    if ($inMajor) {
        return [
            'score' => min(100, 88 * $moonPhaseMultiplier * $moonDistanceBonus),
            'impact' => "🌕 Major Solunar Period - висока активност"
        ];
    }
    if ($inMinor) {
        return [
            'score' => min(100, 72 * $moonPhaseMultiplier * $moonDistanceBonus),
            'impact' => "🌗 Minor Period - добра активност"
        ];
    }
    
    // OUTSIDE PERIODS - gradual decline based on distance
    if ($distanceToClosest < 1.0) { // Within 1 hour
        $score = 60 - ($distanceToClosest * 10);
        return [
            'score' => max(40, $score * $moonPhaseMultiplier),
            'impact' => "⏱️ Близо до {$closestPeriod} период"
        ];
    } else if ($distanceToClosest < 2.0) { // 1-2 hours away
        $score = 50 - ($distanceToClosest * 5);
        return [
            'score' => max(30, $score * $moonPhaseMultiplier),
            'impact' => "⏱️ Между solunar периоди"
        ];
    } else { // Far from any period
        return [
            'score' => max(15, 35 * $moonPhaseMultiplier),
            'impact' => "⏱️ Извън solunar период - ниска активност"
        ];
    }
}

// Wind and cloud cover combined score
function calculateWindCloudScore($wind, $clouds, $currentTime) {
    $windScore = 100;
    $lightFactor = 100;
    
    // Wind scoring
    if ($wind <= 2) {
        $windScore = 95;
        $windDesc = "тихо";
    } else if ($wind <= 4) {
        $windScore = 85;
        $windDesc = "лек вятър";
    } else if ($wind <= 7) {
        $windScore = 65;
        $windDesc = "умерен вятър";
    } else if ($wind <= 10) {
        $windScore = 40;
        $windDesc = "силен вятър";
    } else {
        $windScore = 20;
        $windDesc = "буря";
    }
    
    // Cloud cover affects light penetration (important for fish feeding)
    $isDaytime = ($currentTime >= 6 && $currentTime <= 20);
    
    if ($isDaytime) {
        if ($clouds <= 20) {
            $lightFactor = 70; // Full sun can make fish less active
            $cloudDesc = "ясно - рибата може да е дълбоко";
        } else if ($clouds <= 50) {
            $lightFactor = 100; // OPTIMAL - diffused light
            $cloudDesc = "разсеяна облачност - идеално!";
        } else if ($clouds <= 80) {
            $lightFactor = 90;
            $cloudDesc = "облачно - добри условия";
        } else {
            $lightFactor = 75;
            $cloudDesc = "мрачно";
        }
    } else {
        $lightFactor = 85; // Night - clouds less important
        $cloudDesc = "нощ";
    }
    
    $combinedScore = ($windScore * 0.6) + ($lightFactor * 0.4);
    
    return [
        'score' => $combinedScore,
        'impact' => "💨 $windDesc, $cloudDesc"
    ];
}

// Time of day scoring with seasonal adjustment
function calculateTimeScore($currentTime, $season) {
    // Sunrise/sunset times vary by season
    $sunTimes = [
        'spring' => ['sunrise' => 6.0, 'sunset' => 19.0],
        'summer' => ['sunrise' => 5.5, 'sunset' => 20.5],
        'autumn' => ['sunrise' => 6.5, 'sunset' => 18.0],
        'winter' => ['sunrise' => 7.5, 'sunset' => 17.0]
    ];
    
    $sunrise = $sunTimes[$season]['sunrise'];
    $sunset = $sunTimes[$season]['sunset'];
    
    // Dawn period (1h before to 1.5h after sunrise)
    $dawnStart = $sunrise - 1.0;
    $dawnEnd = $sunrise + 1.5;
    
    // Dusk period (1.5h before to 1h after sunset)
    $duskStart = $sunset - 1.5;
    $duskEnd = $sunset + 1.0;
    
    // Peak feeding times
    if (($currentTime >= $dawnStart && $currentTime <= $dawnEnd) ||
        ($currentTime >= $duskStart && $currentTime <= $duskEnd)) {
        
        // Exact sunrise/sunset = peak
        if (abs($currentTime - $sunrise) < 0.5 || abs($currentTime - $sunset) < 0.5) {
            return ['score' => 100, 'impact' => "🌅 ЗЛАТЕН ЧАС - пик на хранене!"];
        }
        return ['score' => 90, 'impact' => "🌄 Зазоряване/Залез - най-добро време"];
    }
    
    // Early morning / late afternoon
    if (($currentTime > $dawnEnd && $currentTime < $sunrise + 3) ||
        ($currentTime < $duskStart && $currentTime > $sunset - 3)) {
        return ['score' => 75, 'impact' => "⏰ Добро време за хранене"];
    }
    
    // Night fishing
    if ($currentTime < $sunrise - 1 || $currentTime > $sunset + 1) {
        return ['score' => 60, 'impact' => "🌙 Нощно риболов - умерена активност"];
    }
    
    // Midday (worst time)
    $noon = ($sunrise + $sunset) / 2;
    if (abs($currentTime - $noon) < 2) {
        return ['score' => 30, 'impact' => "☀️ Обяд - най-слаба активност"];
    }
    
    // Other daytime hours
    return ['score' => 55, 'impact' => "☀️ Дневно време - средна активност"];
}

// Function to calculate moon phase (Enhanced with solunar data)
function getMoonPhase() {
    // Simplified moon phase calculation
    $year = date('Y');
    $month = date('n');
    $day = date('j');
    
    // Calculate days since known new moon (Jan 6, 2000)
    $baseDate = mktime(0, 0, 0, 1, 6, 2000);
    $currentDate = mktime(0, 0, 0, $month, $day, $year);
    $daysSince = ($currentDate - $baseDate) / 86400;
    
    // Moon cycle is ~29.53 days
    $moonCycle = 29.53;
    $phase = fmod($daysSince, $moonCycle);
    $phasePercent = ($phase / $moonCycle) * 100;
    $moonAge = $phase; // Days since new moon
    
    // Determine phase name and score (Solunar theory: New/Full = best)
    if ($phasePercent < 6.25 || $phasePercent >= 93.75) {
        return [
            'name' => '🌑 Новолуние',
            'score' => 100,
            'impact' => '🌑 Новолуние - максимална гравитация!',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else if ($phasePercent < 18.75) {
        return [
            'name' => '🌒 Растящ полумесец',
            'score' => 70,
            'impact' => '🌒 Растяща луна - добра активност',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else if ($phasePercent < 31.25) {
        return [
            'name' => '🌓 Първа четвърт',
            'score' => 75,
            'impact' => '🌓 Първа четвърт - добра активност',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else if ($phasePercent < 43.75) {
        return [
            'name' => '🌔 Растяща луна',
            'score' => 85,
            'impact' => '🌔 Към пълнолуние - висока активност',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else if ($phasePercent < 56.25) {
        return [
            'name' => '🌕 Пълнолуние',
            'score' => 100,
            'impact' => '🌕 Пълнолуние - максимална активност!',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else if ($phasePercent < 68.75) {
        return [
            'name' => '🌖 Намаляваща луна',
            'score' => 85,
            'impact' => '🌖 След пълнолуние - висока активност',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else if ($phasePercent < 81.25) {
        return [
            'name' => '🌗 Последна четвърт',
            'score' => 75,
            'impact' => '🌗 Последна четвърт - добра активност',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    } else {
        return [
            'name' => '🌘 Намаляващ полумесец',
            'score' => 70,
            'impact' => '🌘 Намаляваща луна - добра активност',
            'age' => $moonAge,
            'illumination' => round($phasePercent)
        ];
    }
}
