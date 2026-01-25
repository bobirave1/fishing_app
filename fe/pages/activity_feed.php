<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .activity-item:hover {
            background: #f8f9fa;
        }
        #map {
            height: 450px;
            width: 100%;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .map-instructions {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .location-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        .activity-like {
            background: #dc3545;
        }
        .activity-comment {
            background: #0d6efd;
        }
        .activity-post {
            background: #198754;
        }
        .activity-follow {
            background: #6f42c1;
        }
    </style>
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>">

<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Get user avatar for navbar
$stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();
$avatar = $profile['avatar_url'] ?? '../assets/img/default-avatar.png';
?>

<!-- HEADER -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="../../index.php">
            <img src="../assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
            <span class="fw-bold fs-4 brand-color">FISHINGLORY</span>
        </a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <img src="<?= htmlspecialchars($avatar) ?>" width="32" height="32" class="rounded-circle" style="object-fit: cover;">
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../../be/users/profile.php?id=<?= $_SESSION['user_id'] ?>">
                        <i class="fas fa-user"></i> My Profile
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../../be/auth/logout.php">
                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                    </a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Map Selection -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-map-marked-alt"></i> Select Fishing Location on Map</h6>
                </div>
                <div class="card-body">
                    <div class="map-instructions">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Instructions:</strong> Click anywhere on the map to select your fishing location. 
                        Popular spots are pre-marked with 🎣 icons.
                    </div>
                    <div id="map"></div>
                    <div class="location-info mt-3" id="locationInfo" style="display: none;">
                        <strong><i class="fas fa-map-pin"></i> Selected Location:</strong>
                        <span id="selectedLocationName">None</span><br>
                        <small class="text-muted">
                            Coordinates: <span id="selectedCoords"></span>
                        </small>
                    </div>
                    <div class="mt-3">
                        <button class="btn btn-success w-100" id="calculateBtn" onclick="loadFishActivity()" disabled>
                            <i class="fas fa-calculator"></i> Calculate Fish Activity for Selected Location
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-fish"></i> Fish Activity Prediction</h5>
                    <small>Real-time fish activity forecast based on environmental conditions</small>
                </div>
                <div class="card-body" id="activityFeed">
                    <p class="text-center text-muted p-4">
                        <i class="fas fa-mouse-pointer"></i> Click on the map above to select a fishing location
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
    // Global variables
    let map;
    let marker;
    let selectedLat = null;
    let selectedLon = null;
    let selectedLocationName = null;

    // Popular fishing spots in Bulgaria
    const popularSpots = [
        { name: "Варненско езеро", lat: 43.2167, lon: 27.9167, icon: "🎣" },
        { name: "Бургаско езеро", lat: 42.5000, lon: 27.4833, icon: "🎣" },
        { name: "Река Дунав (Русе)", lat: 43.8500, lon: 25.9667, icon: "🎣" },
        { name: "Язовир Искър", lat: 42.8167, lon: 23.9500, icon: "🎣" },
        { name: "Язовир Батак", lat: 41.9833, lon: 24.0667, icon: "🎣" },
        { name: "Черно море (Варна)", lat: 43.2050, lon: 28.0350, icon: "🎣" },
        { name: "Язовир Панчарево", lat: 42.5833, lon: 23.4500, icon: "🎣" }
    ];

    // Initialize map centered on Bulgaria
    function initMap() {
        map = L.map('map').setView([42.7339, 25.4858], 7);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add popular fishing spots
        popularSpots.forEach(spot => {
            const popupMarker = L.marker([spot.lat, spot.lon], {
                icon: L.divIcon({
                    html: `<div style="font-size: 24px;">${spot.icon}</div>`,
                    className: 'custom-marker',
                    iconSize: [30, 30]
                })
            }).addTo(map);
            
            popupMarker.bindPopup(`<strong>${spot.name}</strong><br><a href="#" onclick="selectPopularSpot(${spot.lat}, ${spot.lon}, '${spot.name}'); return false;">Select this location</a>`);
        });

        // Add click event to map
        map.on('click', function(e) {
            selectLocation(e.latlng.lat, e.latlng.lng, 'Custom Location');
        });
    }

    function selectPopularSpot(lat, lon, name) {
        selectLocation(lat, lon, name);
        map.closePopup();
    }

    function selectLocation(lat, lon, name) {
        selectedLat = lat;
        selectedLon = lon;
        selectedLocationName = name;

        // Remove old marker
        if (marker) {
            map.removeLayer(marker);
        }

        // Add new marker
        marker = L.marker([lat, lon], {
            icon: L.divIcon({
                html: '<div style="font-size: 32px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">📍</div>',
                className: 'selected-marker',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            })
        }).addTo(map);

        marker.bindPopup(`<strong>${name}</strong><br>Lat: ${lat.toFixed(4)}, Lon: ${lon.toFixed(4)}`).openPopup();

        // Update location info
        document.getElementById('locationInfo').style.display = 'block';
        document.getElementById('selectedLocationName').textContent = name;
        document.getElementById('selectedCoords').textContent = `${lat.toFixed(4)}°N, ${lon.toFixed(4)}°E`;
        document.getElementById('calculateBtn').disabled = false;

        // Scroll to button
        document.getElementById('calculateBtn').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function loadFishActivity() {
        if (!selectedLat || !selectedLon) {
            alert('Please select a location on the map first');
            return;
        }
        
        document.getElementById('activityFeed').innerHTML = '<p class="text-center p-4"><i class="fas fa-spinner fa-spin fa-2x"></i><br><br>Calculating fish activity for selected location...</p>';
        
        fetch(`../../be/activity/feed.php?action=calculate_fish_activity&location=${encodeURIComponent(selectedLocationName)}&lat=${selectedLat}&lon=${selectedLon}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayFishActivityScore(data, selectedLocationName);
                    // Scroll to results
                    document.getElementById('activityFeed').scrollIntoView({ behavior: 'smooth' });
                } else {
                    document.getElementById('activityFeed').innerHTML = `<p class="text-warning text-center p-4"><i class="fas fa-exclamation-triangle"></i><br>${data.error || 'Unable to calculate activity'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('activityFeed').innerHTML = '<p class="text-danger text-center p-4"><i class="fas fa-times-circle"></i><br>Failed to calculate fish activity. Please try again.</p>';
            });
    }

    function displayFishActivityScore(data, location) {
        const score = data.activity_score;
        const factors = data.factors;
        
        // Determine activity level and color
        let activityLevel, activityColor, activityIcon, recommendation;
        if (score >= 80) {
            activityLevel = 'EXCELLENT';
            activityColor = '#198754';
            activityIcon = '🟢';
            recommendation = 'Perfect conditions! Fish are very active. Great time to go fishing!';
        } else if (score >= 60) {
            activityLevel = 'GOOD';
            activityColor = '#28a745';
            activityIcon = '🟡';
            recommendation = 'Good conditions. Fish activity is above average. Recommended for fishing.';
        } else if (score >= 40) {
            activityLevel = 'MODERATE';
            activityColor = '#ffc107';
            activityIcon = '🟠';
            recommendation = 'Moderate conditions. Fish may bite but less actively. Worth trying.';
        } else if (score >= 20) {
            activityLevel = 'LOW';
            activityColor = '#fd7e14';
            activityIcon = '🔴';
            recommendation = 'Low activity. Fish are less active. Try deeper waters or different baits.';
        } else {
            activityLevel = 'VERY LOW';
            activityColor = '#dc3545';
            activityIcon = '⛔';
            recommendation = 'Poor conditions. Fish activity is minimal. Consider waiting for better conditions.';
        }

        const html = `
            <div class="p-4">
                <!-- Location and Score Header -->
                <div class="text-center mb-4">
                    <h4><i class="fas fa-map-marker-alt"></i> ${location}</h4>
                    <div class="mt-3">
                        <div style="font-size: 4rem; line-height: 1;">${activityIcon}</div>
                        <h2 style="color: ${activityColor}; font-weight: bold; margin: 10px 0;">
                            ${Math.round(score)}%
                        </h2>
                        <h5 style="color: ${activityColor};">${activityLevel} ACTIVITY</h5>
                        <p class="text-muted">${new Date().toLocaleString()}</p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="mb-4">
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar" role="progressbar" 
                             style="width: ${score}%; background-color: ${activityColor};"
                             aria-valuenow="${score}" aria-valuemin="0" aria-valuemax="100">
                            ${Math.round(score)}%
                        </div>
                    </div>
                </div>

                <!-- Recommendation -->
                <div class="alert alert-info">
                    <h6><i class="fas fa-lightbulb"></i> Recommendation</h6>
                    <p class="mb-0">${recommendation}</p>
                </div>

                <!-- Weather Conditions -->
                ${factors.weather ? `
                <div class="card mb-3">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <strong><i class="fas fa-cloud-sun"></i> Weather Conditions</strong>
                        ${factors.weather.source ? `<small class="badge bg-success">${factors.weather.source}</small>` : ''}
                    </div>
                    <div class="card-body">
                        ${factors.weather.location_name && factors.weather.location_name !== 'Unknown' ? `
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${factors.weather.location_name}${factors.weather.country ? `, ${factors.weather.country}` : ''}<br>
                            <small class="text-muted">Coordinates: ${selectedLat.toFixed(4)}°N, ${selectedLon.toFixed(4)}°E</small>
                        </div>
                        ` : ''}
                        <div class="row text-center">
                            <div class="col-lg-2 col-md-3 col-6 mb-3">
                                <i class="fas fa-thermometer-half fa-2x text-danger"></i>
                                <p class="mb-0"><strong>${factors.weather.temperature}°C</strong></p>
                                <small class="text-muted">Temperature</small>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6 mb-3">
                                <i class="fas fa-wind fa-2x text-info"></i>
                                <p class="mb-0"><strong>${factors.weather.wind_speed} m/s</strong></p>
                                <small class="text-muted">Wind Speed</small>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6 mb-3">
                                <i class="fas fa-gauge fa-2x text-warning"></i>
                                <p class="mb-0"><strong>${factors.weather.pressure} hPa</strong></p>
                                <small class="text-muted">Pressure</small>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6 mb-3">
                                <i class="fas fa-tint fa-2x text-primary"></i>
                                <p class="mb-0"><strong>${factors.weather.humidity}%</strong></p>
                                <small class="text-muted">Humidity</small>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6 mb-3">
                                <i class="fas fa-cloud fa-2x text-secondary"></i>
                                <p class="mb-0"><strong>${factors.weather.clouds}%</strong></p>
                                <small class="text-muted">Cloud Cover</small>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6 mb-3">
                                <i class="fas fa-eye fa-2x text-success"></i>
                                <p class="mb-0"><strong>${factors.weather.visibility || 'N/A'} km</strong></p>
                                <small class="text-muted">Visibility</small>
                            </div>
                        </div>
                        ${factors.weather.weather_description ? `
                        <div class="text-center mt-2">
                            <span class="badge bg-light text-dark">${getWeatherIcon(factors.weather.weather)} ${factors.weather.weather_description}</span>
                        </div>
                        ` : ''}
                    </div>
                </div>
                ` : ''}

                <!-- Activity Factors -->
                <div class="card">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-list-check"></i> Activity Factors</strong>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-thermometer-half"></i> Temperature Impact</span>
                                <strong>${factors.temperature_score}%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-primary" style="width: ${factors.temperature_score}%"></div>
                            </div>
                            <small class="text-muted">${factors.temperature_impact}</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-wind"></i> Wind Conditions</span>
                                <strong>${factors.wind_score}%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-info" style="width: ${factors.wind_score}%"></div>
                            </div>
                            <small class="text-muted">${factors.wind_impact}</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-gauge"></i> Barometric Pressure</span>
                                <strong>${factors.pressure_score}%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-warning" style="width: ${factors.pressure_score}%"></div>
                            </div>
                            <small class="text-muted">${factors.pressure_impact}</small>
                        </div>
                        
                        ${factors.solunar_score !== undefined ? `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-star"></i> Solunar Period</span>
                                <strong>${factors.solunar_score}%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-danger" style="width: ${factors.solunar_score}%"></div>
                            </div>
                            <small class="text-muted">${factors.solunar_impact || 'Moon position effect'}</small>
                        </div>
                        ` : `
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-moon"></i> Moon Phase</span>
                                <strong>${factors.moon_score || 50}%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-secondary" style="width: ${factors.moon_score || 50}%"></div>
                            </div>
                            <small class="text-muted">${factors.moon_phase || 'Moon effect'}</small>
                        </div>
                        `}
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span><i class="fas fa-clock"></i> Time of Day</span>
                                <strong>${factors.time_score}%</strong>
                            </div>
                            <div class="progress">
                                <div class="progress-bar bg-success" style="width: ${factors.time_score}%"></div>
                            </div>
                            <small class="text-muted">${factors.time_impact}</small>
                        </div>
                    </div>
                </div>

                <!-- Solunar Periods Table (if available) -->
                ${factors.solunar_periods ? `
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <strong><i class="fas fa-calendar-alt"></i> Today's Solunar Periods</strong>
                        <small class="d-block text-muted mt-1">Major and minor feeding times based on moon position</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-danger mb-2">
                                    <strong>🔴 Major Period 1</strong><br>
                                    <small>${formatSolunarTime(factors.solunar_periods.major1.start)} - ${formatSolunarTime(factors.solunar_periods.major1.end)}</small><br>
                                    <small class="text-muted">Peak: ${formatSolunarTime(factors.solunar_periods.major1.peak)}</small>
                                </div>
                                <div class="alert alert-warning mb-2">
                                    <strong>🟡 Minor Period 1</strong><br>
                                    <small>${formatSolunarTime(factors.solunar_periods.minor1.start)} - ${formatSolunarTime(factors.solunar_periods.minor1.end)}</small><br>
                                    <small class="text-muted">Peak: ${formatSolunarTime(factors.solunar_periods.minor1.peak)}</small>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="alert alert-danger mb-2">
                                    <strong>🔴 Major Period 2</strong><br>
                                    <small>${formatSolunarTime(factors.solunar_periods.major2.start)} - ${formatSolunarTime(factors.solunar_periods.major2.end)}</small><br>
                                    <small class="text-muted">Peak: ${formatSolunarTime(factors.solunar_periods.major2.peak)}</small>
                                </div>
                                <div class="alert alert-warning mb-2">
                                    <strong>🟡 Minor Period 2</strong><br>
                                    <small>${formatSolunarTime(factors.solunar_periods.minor2.start)} - ${formatSolunarTime(factors.solunar_periods.minor2.end)}</small><br>
                                    <small class="text-muted">Peak: ${formatSolunarTime(factors.solunar_periods.minor2.peak)}</small>
                                </div>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-info-circle"></i> <strong>Major periods</strong> (2-3 hours): Best fishing times when moon is overhead or underfoot.<br>
                            <i class="fas fa-info-circle"></i> <strong>Minor periods</strong> (1-2 hours): Good fishing times at moonrise and moonset.
                        </small>
                    </div>
                </div>
                ` : ''}

                ${factors.weather && factors.weather.clouds !== undefined ? `
                <div class="alert alert-light mt-3">
                    <small><i class="fas fa-cloud"></i> <strong>Cloud Cover:</strong> ${factors.weather.clouds}% - ${getCloudDescription(factors.weather.clouds)}</small><br>
                    <small><i class="fas fa-info-circle"></i> <strong>Conditions:</strong> ${factors.weather.conditions || 'Clear'}</small>
                </small>
                </div>
                ` : ''}

                <!-- Refresh Button -->
                <div class="text-center mt-4">
                    <button class="btn btn-primary" onclick="loadFishActivity()">
                        <i class="fas fa-sync"></i> Refresh Activity
                    </button>
                </div>
            </div>
        `;

        document.getElementById('activityFeed').innerHTML = html;
    }

    function formatSolunarTime(time) {
        // Convert decimal time (e.g., 6.5) to HH:MM format
        if (time < 0) time += 24;
        if (time >= 24) time -= 24;
        const hours = Math.floor(time);
        const minutes = Math.round((time - hours) * 60);
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function getCloudDescription(clouds) {
        if (clouds <= 20) return 'Clear sky - bright conditions';
        if (clouds <= 50) return 'Partly cloudy - optimal light';
        if (clouds <= 80) return 'Mostly cloudy - good diffused light';
        return 'Overcast - low light conditions';
    }

    function getWeatherIcon(weather) {
        const icons = {
            'Clear': '☀️',
            'Clouds': '☁️',
            'Rain': '🌧️',
            'Drizzle': '🌦️',
            'Thunderstorm': '⛈️',
            'Snow': '❄️',
            'Mist': '🌫️',
            'Fog': '🌫️',
            'Haze': '🌫️'
        };
        return icons[weather] || '🌤️';
    }

    // Initialize map when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initMap();
    });
</script>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>
