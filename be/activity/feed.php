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
    
    // Get weather data
    $weatherData = getWeatherData($lat, $lon);
    
    // Calculate activity score based on multiple factors
    $activityScore = calculateFishActivityScore($weatherData);
    
    exit(json_encode([
        'success' => true,
        'activity_score' => $activityScore['total_score'],
        'factors' => $activityScore['factors'],
        'location' => $location
    ]));
    
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
function calculateFishActivityScore($weather) {
    $factors = [];
    $hour = (int)date('H');
    $minute = (int)date('i');
    $currentTime = $hour + ($minute / 60);
    
    // Get moon data for solunar calculations
    $moonData = getMoonPhase();
    $solunarPeriods = calculateSolunarPeriods($moonData);
    
    // Get current season
    $month = (int)date('n');
    $season = getSeason($month);
    
    // 1. Temperature Score (25% weight) - Species & Season Dependent
    $temp = $weather['temperature'];
    $tempScore = calculateTemperatureScore($temp, $season);
    
    // 2. Barometric Pressure Trend (25% weight) - TREND is more important than absolute
    $pressure = $weather['pressure'];
    $pressureData = calculatePressureScore($pressure);
    $pressureScore = $pressureData['score'];
    $pressureImpact = $pressureData['impact'];
    
    // 3. Solunar Score (20% weight) - Major/Minor periods based on moon position
    $solunarData = calculateSolunarScore($currentTime, $solunarPeriods, $moonData);
    $solunarScore = $solunarData['score'];
    $solunarImpact = $solunarData['impact'];
    
    // 4. Wind & Cloud Cover (15% weight) - Combined light penetration factor
    $wind = $weather['wind_speed'];
    $clouds = $weather['clouds'];
    $windCloudData = calculateWindCloudScore($wind, $clouds, $currentTime);
    $windScore = $windCloudData['score'];
    $windImpact = $windCloudData['impact'];
    
    // 5. Time of Day (15% weight) - Crepuscular feeding patterns
    $timeData = calculateTimeScore($currentTime, $season);
    $timeScore = $timeData['score'];
    $timeImpact = $timeData['impact'];
    
    // Calculate base weighted score
    $baseScore = (
        ($tempScore['score'] * 0.25) +
        ($pressureScore * 0.25) +
        ($solunarScore * 0.20) +
        ($windScore * 0.15) +
        ($timeScore * 0.15)
    );
    
    // Apply multipliers for optimal combinations
    $multiplier = 1.0;
    
    // Perfect storm: Rising pressure + Major solunar + Dawn/Dusk
    if ($pressureScore > 85 && $solunarScore > 80 && $timeScore > 85) {
        $multiplier = 1.15;
    }
    // Good combo: Stable pressure + Minor solunar + Good time
    else if ($pressureScore > 70 && $solunarScore > 60 && $timeScore > 70) {
        $multiplier = 1.08;
    }
    
    // Weather penalties
    if ($weather['weather'] === 'Rain' && $wind < 4) {
        $multiplier *= 1.05; // Light rain can increase activity
    } else if ($weather['weather'] === 'Rain' && $wind > 7) {
        $multiplier *= 0.85; // Heavy rain decreases activity
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

// Solunar theory implementation (Professional algorithm)
function calculateSolunarPeriods($moonData) {
    $hour = (int)date('H');
    $minute = (int)date('i');
    
    // Moon transit times (simplified - in real app use astronomical calculations)
    // Major periods: Moon overhead (transit) and moon underfoot (opposite)
    // Minor periods: Moonrise and moonset
    
    $moonAge = $moonData['age']; // Days since new moon
    
    // Estimate moon transit (peaks around lunar noon)
    $lunarNoon = 12 + ($moonAge * 0.8); // Shifts ~50min per day
    $lunarNoon = fmod($lunarNoon, 24);
    $lunarMidnight = fmod($lunarNoon + 12, 24);
    
    // Major periods: 2-3 hours centered on transit
    $major1Start = $lunarNoon - 1.5;
    $major1End = $lunarNoon + 1.5;
    $major2Start = $lunarMidnight - 1.5;
    $major2End = $lunarMidnight + 1.5;
    
    // Minor periods: 1-1.5 hours at moonrise/set (estimate)
    $moonrise = fmod($lunarNoon - 6, 24);
    $moonset = fmod($lunarNoon + 6, 24);
    $minor1Start = $moonrise - 0.75;
    $minor1End = $moonrise + 0.75;
    $minor2Start = $moonset - 0.75;
    $minor2End = $moonset + 0.75;
    
    return [
        'major1' => ['start' => $major1Start, 'end' => $major1End, 'peak' => $lunarNoon],
        'major2' => ['start' => $major2Start, 'end' => $major2End, 'peak' => $lunarMidnight],
        'minor1' => ['start' => $minor1Start, 'end' => $minor1End, 'peak' => $moonrise],
        'minor2' => ['start' => $minor2Start, 'end' => $minor2End, 'peak' => $moonset]
    ];
}

// Calculate solunar score based on current time vs periods
function calculateSolunarScore($currentTime, $periods, $moonData) {
    $inMajor = false;
    $inMinor = false;
    $nearPeak = false;
    $minutesToPeak = 999;
    
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
    }
    
    // Score based on solunar period + moon phase strength
    $moonPhaseMultiplier = $moonData['score'] / 100;
    
    if ($inMajor && $nearPeak) {
        return [
            'score' => 95 * $moonPhaseMultiplier,
            'impact' => "🌕 MAJOR Solunar Period - пик на активност!"
        ];
    }
    if ($inMajor) {
        return [
            'score' => 85 * $moonPhaseMultiplier,
            'impact' => "🌕 Major Period - висока активност"
        ];
    }
    if ($inMinor) {
        return [
            'score' => 70 * $moonPhaseMultiplier,
            'impact' => "🌗 Minor Period - добра активност"
        ];
    }
    
    // Outside periods - check proximity
    // Find nearest period
    // Simplified: return medium score
    return [
        'score' => 50,
        'impact' => "⏱️ Извън solunar период - средна активност"
    ];
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
