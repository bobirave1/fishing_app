<?php
/**
 * Fish Activity Calculator — Solunar-based scoring algorithm for FISHINGLORY.
 *
 * Extracted from be/activity/feed.php for maintainability.
 * Provides: calculateFishActivityScore(), getMoonPhase(), and all supporting helpers.
 */

function calculateFishActivityScore($weather, $lat = 42.7, $lon = 25.5) {
    $factors = [];
    $hour = (int)date('H');
    $minute = (int)date('i');
    $currentTime = $hour + ($minute / 60);

    $moonData = getMoonPhase();
    $solunarPeriods = calculateSolunarPeriods($moonData, $lat, $lon);

    $month = (int)date('n');
    $season = getSeason($month);

    // 1. Solunar Score (35% weight)
    $solunarData = calculateSolunarScore($currentTime, $solunarPeriods, $moonData);
    $solunarScore = $solunarData['score'];
    $solunarImpact = $solunarData['impact'];

    // 2. Time of Day (25% weight)
    $timeData = calculateTimeScore($currentTime, $season);
    $timeScore = $timeData['score'];
    $timeImpact = $timeData['impact'];

    // 3. Barometric Pressure (20% weight)
    $pressure = $weather['pressure'];
    $pressureData = calculatePressureScore($pressure);
    $pressureScore = $pressureData['score'];
    $pressureImpact = $pressureData['impact'];

    // 4. Temperature (12% weight)
    $temp = $weather['temperature'];
    $tempScore = calculateTemperatureScore($temp, $season);

    // 5. Wind & Cloud Cover (8% weight)
    $wind = $weather['wind_speed'];
    $clouds = $weather['clouds'];
    $windCloudData = calculateWindCloudScore($wind, $clouds, $currentTime);
    $windScore = $windCloudData['score'];
    $windImpact = $windCloudData['impact'];

    $baseScore = (
        ($solunarScore * 0.35) +
        ($timeScore * 0.25) +
        ($pressureScore * 0.20) +
        ($tempScore['score'] * 0.12) +
        ($windScore * 0.08)
    );

    $multiplier = 1.0;

    if ($solunarScore > 85 && $timeScore > 85 && $pressureScore > 80) {
        $multiplier = 1.20;
    } else if ($solunarScore > 85 && ($timeScore > 80 || $pressureScore > 75)) {
        $multiplier = 1.12;
    } else if ($solunarScore > 70 && $timeScore > 80) {
        $multiplier = 1.08;
    } else if ($solunarScore > 70 || $timeScore > 75) {
        $multiplier = 1.03;
    }

    if ($weather['weather'] === 'Rain' && $wind < 4) {
        $multiplier *= 1.04;
    } else if ($weather['weather'] === 'Rain' && $wind > 7) {
        $multiplier *= 0.88;
    } else if ($weather['weather'] === 'Thunderstorm') {
        $multiplier *= 0.75;
    }

    if ($solunarScore < 30 && $timeScore < 40 && $pressureScore < 50) {
        $multiplier *= 0.85;
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

function getSeason($month) {
    if ($month >= 3 && $month <= 5) return 'spring';
    if ($month >= 6 && $month <= 8) return 'summer';
    if ($month >= 9 && $month <= 11) return 'autumn';
    return 'winter';
}

function calculateTemperatureScore($temp, $season) {
    $optimalRanges = [
        'spring' => ['min' => 12, 'ideal' => 16, 'max' => 20],
        'summer' => ['min' => 18, 'ideal' => 22, 'max' => 26],
        'autumn' => ['min' => 14, 'ideal' => 18, 'max' => 22],
        'winter' => ['min' => 6, 'ideal' => 10, 'max' => 14]
    ];

    $range = $optimalRanges[$season];
    $ideal = $range['ideal'];

    if (abs($temp - $ideal) < 2) {
        return ['score' => 100, 'impact' => "🌡️ Идеална температура за {$season} ({$temp}°C)"];
    }
    if ($temp >= $range['min'] && $temp <= $range['max']) {
        $deviation = abs($temp - $ideal);
        $score = 100 - ($deviation * 8);
        return ['score' => max(70, $score), 'impact' => "🌡️ Отлична температура - рибата е активна"];
    }
    if ($temp > $range['max'] && $temp <= $range['max'] + 6) {
        $score = 70 - (($temp - $range['max']) * 5);
        return ['score' => max(30, $score), 'impact' => "🌡️ Топло - рибата търси по-дълбоки води"];
    }
    if ($temp < $range['min'] && $temp >= $range['min'] - 6) {
        $score = 70 - (($range['min'] - $temp) * 5);
        return ['score' => max(30, $score), 'impact' => "🌡️ Прохладно - забавена активност"];
    }
    if ($temp > 30) {
        return ['score' => 15, 'impact' => "🔥 Прекалено горещо - минимална активност"];
    }
    if ($temp < 4) {
        return ['score' => 20, 'impact' => "❄️ Студено - много ниска активност"];
    }
    return ['score' => 25, 'impact' => "⚠️ Неблагоприятна температура"];
}

function calculatePressureScore($pressure) {
    if ($pressure >= 1020 && $pressure <= 1030) {
        return ['score' => 75, 'impact' => "📈 Високо и стабилно - добри условия"];
    }
    if ($pressure >= 1010 && $pressure < 1020) {
        if ($pressure >= 1012 && $pressure <= 1016) {
            return ['score' => 100, 'impact' => "📊 Перфектно стабилно налягане - пик активност!"];
        }
        return ['score' => 90, 'impact' => "📊 Стабилно налягане - отлични условия"];
    }
    if ($pressure >= 1005 && $pressure < 1010) {
        return ['score' => 80, 'impact' => "📉 Леко падащо - рибата усеща промяна (добре!)"];
    }
    if ($pressure >= 995 && $pressure < 1005) {
        return ['score' => 70, 'impact' => "📉 Падащо налягане - активно хранене преди буря"];
    }
    if ($pressure < 995) {
        return ['score' => 40, 'impact' => "⛈️ Много ниско - буря, слаба активност"];
    }
    if ($pressure > 1030) {
        return ['score' => 55, 'impact' => "📈 Много високо - рибата е пасивна"];
    }
    return ['score' => 60, 'impact' => "📊 Средно налягане"];
}

function calculateSolunarPeriods($moonData, $lat = 42.7, $lon = 25.5) {
    try {
        $year = (int)date('Y');
        $month = (int)date('n');
        $day = (int)date('j');

        $a = floor((14 - $month) / 12);
        $y = $year + 4800 - $a;
        $m = $month + (12 * $a) - 3;
        $jd = $day + floor((153 * $m + 2) / 5) + (365 * $y) + floor($y / 4) - floor($y / 100) + floor($y / 400) - 32045;

        $moonTransit = calculateMoonTransit($jd, $lon);
        $moonTimes = calculateMoonRiseSet($jd, $lat, $lon);
        $moonrise = $moonTimes['rise'];
        $moonset = $moonTimes['set'];

        $moonUnderfoot = fmod($moonTransit + 12, 24);
        if ($moonUnderfoot < 0) $moonUnderfoot += 24;

        return [
            'major1' => ['start' => $moonTransit - 1.0, 'end' => $moonTransit + 1.0, 'peak' => $moonTransit],
            'major2' => ['start' => $moonUnderfoot - 1.0, 'end' => $moonUnderfoot + 1.0, 'peak' => $moonUnderfoot],
            'minor1' => ['start' => $moonrise - 0.5, 'end' => $moonrise + 0.5, 'peak' => $moonrise],
            'minor2' => ['start' => $moonset - 0.5, 'end' => $moonset + 0.5, 'peak' => $moonset]
        ];
    } catch (Exception $e) {
        error_log("Solunar calculation error: " . $e->getMessage());
        return calculateSolunarPeriodsSimplified($moonData);
    }
}

function calculateSolunarPeriodsSimplified($moonData) {
    $moonAge = $moonData['age'];
    $lunarNoon = fmod(12 + ($moonAge * 0.8), 24);
    if ($lunarNoon < 0) $lunarNoon += 24;

    $lunarMidnight = fmod($lunarNoon + 12, 24);
    if ($lunarMidnight < 0) $lunarMidnight += 24;

    $moonrise = fmod($lunarNoon - 6 + 24, 24);
    $moonset = fmod($lunarNoon + 6, 24);

    return [
        'major1' => ['start' => $lunarNoon - 1.0, 'end' => $lunarNoon + 1.0, 'peak' => $lunarNoon],
        'major2' => ['start' => $lunarMidnight - 1.0, 'end' => $lunarMidnight + 1.0, 'peak' => $lunarMidnight],
        'minor1' => ['start' => $moonrise - 0.5, 'end' => $moonrise + 0.5, 'peak' => $moonrise],
        'minor2' => ['start' => $moonset - 0.5, 'end' => $moonset + 0.5, 'peak' => $moonset]
    ];
}

function calculateMoonTransit($jd, $lon) {
    $L = fmod(218.316 + 13.176396 * ($jd - 2451545.0), 360);
    $Mm = fmod(134.963 + 13.064993 * ($jd - 2451545.0), 360);
    $RA = $L + 6.289 * sin(deg2rad($Mm));

    $LST = fmod(280.46061837 + 360.98564736629 * ($jd - 2451545.0) + $lon, 360);
    $HA = $LST - $RA;
    if ($HA < 0) $HA += 360;
    if ($HA > 180) $HA -= 360;

    $transitTime = 12.0 - ($HA / 15.0);
    if ($transitTime < 0) $transitTime += 24;
    if ($transitTime >= 24) $transitTime -= 24;
    return $transitTime;
}

function calculateMoonRiseSet($jd, $lat, $lon) {
    $L = fmod(218.316 + 13.176396 * ($jd - 2451545.0), 360);
    $Mm = fmod(134.963 + 13.064993 * ($jd - 2451545.0), 360);

    $lambda = $L + 6.289 * sin(deg2rad($Mm));
    $beta = 5.128 * sin(deg2rad($Mm));
    $epsilon = 23.439 - 0.0000004 * ($jd - 2451545.0);

    $RA = rad2deg(atan2(
        sin(deg2rad($lambda)) * cos(deg2rad($epsilon)) - tan(deg2rad($beta)) * sin(deg2rad($epsilon)),
        cos(deg2rad($lambda))
    ));
    if ($RA < 0) $RA += 360;

    $decValue = max(-1, min(1,
        sin(deg2rad($beta)) * cos(deg2rad($epsilon)) +
        cos(deg2rad($beta)) * sin(deg2rad($epsilon)) * sin(deg2rad($lambda))
    ));
    $dec = rad2deg(asin($decValue));

    $LST0 = fmod(280.46061837 + 360.98564736629 * ($jd - 2451545.0) + $lon, 360);

    $h0 = -0.833;
    $cosHValue = max(-1, min(1,
        (sin(deg2rad($h0)) - sin(deg2rad($lat)) * sin(deg2rad($dec))) /
        (cos(deg2rad($lat)) * cos(deg2rad($dec)))
    ));

    if ($cosHValue >= 0.9999 || $cosHValue <= -0.9999) {
        $transit = calculateMoonTransit($jd, $lon);
        return ['rise' => fmod($transit - 6 + 24, 24), 'set' => fmod($transit + 6, 24)];
    }

    $H = rad2deg(acos($cosHValue));
    $transit = ($RA - $LST0) / 15.0;
    if ($transit < 0) $transit += 24;
    if ($transit >= 24) $transit -= 24;

    $rise = $transit - ($H / 15.0);
    $set = $transit + ($H / 15.0);
    if ($rise < 0) $rise += 24;
    if ($rise >= 24) $rise -= 24;
    if ($set < 0) $set += 24;
    if ($set >= 24) $set -= 24;

    return ['rise' => $rise, 'set' => $set];
}

function calculateSolunarScore($currentTime, $periods, $moonData) {
    $inMajor = false;
    $inMinor = false;
    $nearPeak = false;
    $minutesToPeak = 999;
    $closestPeriod = null;
    $distanceToClosest = 999;

    foreach ($periods as $key => $period) {
        $isMajor = strpos($key, 'major') !== false;
        $start = $period['start'];
        $end = $period['end'];
        $peak = $period['peak'];

        if ($start < 0) $start += 24;
        if ($end > 24) $end -= 24;
        if ($peak < 0) $peak += 24;
        if ($peak > 24) $peak -= 24;

        $inPeriod = ($start < $end)
            ? ($currentTime >= $start && $currentTime <= $end)
            : ($currentTime >= $start || $currentTime <= $end);

        if ($inPeriod) {
            if ($isMajor) {
                $inMajor = true;
                $dist = abs($currentTime - $peak);
                if ($dist < $minutesToPeak) {
                    $minutesToPeak = $dist;
                    $nearPeak = ($dist < 0.5);
                }
            } else {
                $inMinor = true;
            }
        }

        $distToPeriod = min(abs($currentTime - $start), abs($currentTime - $end), abs($currentTime - $peak));
        if ($distToPeriod < $distanceToClosest) {
            $distanceToClosest = $distToPeriod;
            $closestPeriod = $isMajor ? 'major' : 'minor';
        }
    }

    $moonPhaseMultiplier = $moonData['score'] / 100;
    $moonDistanceBonus = ($moonData['score'] > 90) ? 1.15 : (($moonData['score'] < 40) ? 0.85 : 1.0);

    if ($inMajor && $nearPeak) {
        return ['score' => min(100, 98 * $moonPhaseMultiplier * $moonDistanceBonus), 'impact' => "🌕 MAJOR Period Peak - максимална активност!"];
    }
    if ($inMajor) {
        return ['score' => min(100, 88 * $moonPhaseMultiplier * $moonDistanceBonus), 'impact' => "🌕 Major Solunar Period - висока активност"];
    }
    if ($inMinor) {
        return ['score' => min(100, 72 * $moonPhaseMultiplier * $moonDistanceBonus), 'impact' => "🌗 Minor Period - добра активност"];
    }

    if ($distanceToClosest < 1.0) {
        $score = 60 - ($distanceToClosest * 10);
        return ['score' => max(40, $score * $moonPhaseMultiplier), 'impact' => "⏱️ Близо до {$closestPeriod} период"];
    }
    if ($distanceToClosest < 2.0) {
        $score = 50 - ($distanceToClosest * 5);
        return ['score' => max(30, $score * $moonPhaseMultiplier), 'impact' => "⏱️ Между solunar периоди"];
    }
    return ['score' => max(15, 35 * $moonPhaseMultiplier), 'impact' => "⏱️ Извън solunar период - ниска активност"];
}

function calculateWindCloudScore($wind, $clouds, $currentTime) {
    if ($wind <= 2) { $windScore = 95; $windDesc = "тихо"; }
    else if ($wind <= 4) { $windScore = 85; $windDesc = "лек вятър"; }
    else if ($wind <= 7) { $windScore = 65; $windDesc = "умерен вятър"; }
    else if ($wind <= 10) { $windScore = 40; $windDesc = "силен вятър"; }
    else { $windScore = 20; $windDesc = "буря"; }

    $isDaytime = ($currentTime >= 6 && $currentTime <= 20);
    if ($isDaytime) {
        if ($clouds <= 20) { $lightFactor = 70; $cloudDesc = "ясно - рибата може да е дълбоко"; }
        else if ($clouds <= 50) { $lightFactor = 100; $cloudDesc = "разсеяна облачност - идеално!"; }
        else if ($clouds <= 80) { $lightFactor = 90; $cloudDesc = "облачно - добри условия"; }
        else { $lightFactor = 75; $cloudDesc = "мрачно"; }
    } else {
        $lightFactor = 85;
        $cloudDesc = "нощ";
    }

    return ['score' => ($windScore * 0.6) + ($lightFactor * 0.4), 'impact' => "💨 $windDesc, $cloudDesc"];
}

function calculateTimeScore($currentTime, $season) {
    $sunTimes = [
        'spring' => ['sunrise' => 6.0, 'sunset' => 19.0],
        'summer' => ['sunrise' => 5.5, 'sunset' => 20.5],
        'autumn' => ['sunrise' => 6.5, 'sunset' => 18.0],
        'winter' => ['sunrise' => 7.5, 'sunset' => 17.0]
    ];

    $sunrise = $sunTimes[$season]['sunrise'];
    $sunset = $sunTimes[$season]['sunset'];
    $dawnStart = $sunrise - 1.0;
    $dawnEnd = $sunrise + 1.5;
    $duskStart = $sunset - 1.5;
    $duskEnd = $sunset + 1.0;

    if (($currentTime >= $dawnStart && $currentTime <= $dawnEnd) ||
        ($currentTime >= $duskStart && $currentTime <= $duskEnd)) {
        if (abs($currentTime - $sunrise) < 0.5 || abs($currentTime - $sunset) < 0.5) {
            return ['score' => 100, 'impact' => "🌅 ЗЛАТЕН ЧАС - пик на хранене!"];
        }
        return ['score' => 90, 'impact' => "🌄 Зазоряване/Залез - най-добро време"];
    }

    if (($currentTime > $dawnEnd && $currentTime < $sunrise + 3) ||
        ($currentTime < $duskStart && $currentTime > $sunset - 3)) {
        return ['score' => 75, 'impact' => "⏰ Добро време за хранене"];
    }

    if ($currentTime < $sunrise - 1 || $currentTime > $sunset + 1) {
        return ['score' => 60, 'impact' => "🌙 Нощно риболов - умерена активност"];
    }

    $noon = ($sunrise + $sunset) / 2;
    if (abs($currentTime - $noon) < 2) {
        return ['score' => 30, 'impact' => "☀️ Обяд - най-слаба активност"];
    }

    return ['score' => 55, 'impact' => "☀️ Дневно време - средна активност"];
}

function getMoonPhase() {
    $year = date('Y');
    $month = date('n');
    $day = date('j');

    $baseDate = mktime(0, 0, 0, 1, 6, 2000);
    $currentDate = mktime(0, 0, 0, $month, $day, $year);
    $daysSince = ($currentDate - $baseDate) / 86400;
    $moonCycle = 29.53;
    $phase = fmod($daysSince, $moonCycle);
    $phasePercent = ($phase / $moonCycle) * 100;
    $moonAge = $phase;

    if ($phasePercent < 6.25 || $phasePercent >= 93.75) {
        return ['name' => '🌑 Новолуние', 'score' => 100, 'impact' => '🌑 Новолуние - максимална гравитация!', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    if ($phasePercent < 18.75) {
        return ['name' => '🌒 Растящ полумесец', 'score' => 70, 'impact' => '🌒 Растяща луна - добра активност', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    if ($phasePercent < 31.25) {
        return ['name' => '🌓 Първа четвърт', 'score' => 75, 'impact' => '🌓 Първа четвърт - добра активност', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    if ($phasePercent < 43.75) {
        return ['name' => '🌔 Растяща луна', 'score' => 85, 'impact' => '🌔 Към пълнолуние - висока активност', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    if ($phasePercent < 56.25) {
        return ['name' => '🌕 Пълнолуние', 'score' => 100, 'impact' => '🌕 Пълнолуние - максимална активност!', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    if ($phasePercent < 68.75) {
        return ['name' => '🌖 Намаляваща луна', 'score' => 85, 'impact' => '🌖 След пълнолуние - висока активност', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    if ($phasePercent < 81.25) {
        return ['name' => '🌗 Последна четвърт', 'score' => 75, 'impact' => '🌗 Последна четвърт - добра активност', 'age' => $moonAge, 'illumination' => round($phasePercent)];
    }
    return ['name' => '🌘 Намаляващ полумесец', 'score' => 70, 'impact' => '🌘 Намаляваща луна - добра активност', 'age' => $moonAge, 'illumination' => round($phasePercent)];
}
