<?php
session_start();
require '../../config/database.php';
require '../../config/avatar_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Get user avatar for navbar
$stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();
$avatar = getUserAvatar($profile['avatar_url'] ?? null);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fish Activity - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        body {
            background: #f8f9fa;
            padding-top: 70px;
        }
        .activity-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .activity-header-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .calendar-days {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 15px 20px;
            background: white;
            border-bottom: 1px solid #e2e8f0;
        }
        .day-item {
            text-align: center;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            min-width: 60px;
        }
        .day-item:hover {
            background: #f0f9ff;
        }
        .day-item.active {
            background: #0d6efd;
            color: white;
        }
        .day-number {
            font-size: 1.2rem;
            font-weight: 600;
            display: block;
        }
        .day-name {
            font-size: 0.75rem;
            opacity: 0.8;
            display: block;
        }
        .location-badge {
            text-align: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
            color: #64748b;
        }
        .activity-score-circle {
            width: 200px;
            height: 200px;
            margin: 30px auto;
            position: relative;
        }
        .circle-progress {
            transform: rotate(-90deg);
        }
        .circle-bg {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 8;
        }
        .circle-progress-bar {
            fill: none;
            stroke: #0d6efd;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dashoffset 1s ease;
        }
        .score-number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 4rem;
            font-weight: 300;
            color: #0d6efd;
        }
        .activity-level-text {
            text-align: center;
            font-size: 1.3rem;
            font-weight: 600;
            margin: 20px 0;
            color: #1e293b;
        }
        .activity-chart {
            padding: 30px 20px;
            position: relative;
            height: 200px;
            background: white;
        }
        .chart-labels {
            position: absolute;
            left: 5px;
            top: 30px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 140px;
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 600;
        }
        .chart-line {
            position: relative;
            height: 140px;
            margin-left: 50px;
            border-bottom: 2px solid #e2e8f0;
        }
        .chart-grid {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .grid-line {
            border-top: 1px dashed #e2e8f0;
            width: 100%;
        }
        .chart-wave {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 100%;
        }
        .time-labels {
            display: flex;
            justify-content: space-between;
            margin-left: 50px;
            margin-top: 10px;
            font-size: 0.75rem;
            color: #64748b;
        }
        .times-section {
            padding: 20px;
            display: flex;
            justify-content: space-around;
            background: #f8f9fa;
            border-top: 1px solid #e2e8f0;
        }
        .times-column h6 {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 10px;
            font-weight: 700;
        }
        .time-item {
            font-size: 1rem;
            margin: 5px 0;
            color: #1e293b;
            font-weight: 500;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
        }
        .map-section {
            padding: 20px;
        }
        .search-box {
            margin-bottom: 15px;
        }
    </style>
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>">

<?php include '../components/navbar.php'; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Fish Activity Card -->
            <div class="activity-card" id="activityCard">
                <div class="activity-header-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-fish"></i> Fish Activity Prediction</h5>
                        <button class="btn btn-light btn-sm" onclick="toggleMapSection()" title="Change location">
                            <i class="fas fa-map-marker-alt"></i> Change Location
                        </button>
                    </div>
                </div>

                <!-- Calendar Days -->
                <div class="calendar-days" id="calendarDays">
                    <!-- Will be populated by JavaScript -->
                </div>

                <!-- Location Badge -->
                <div class="location-badge">
                    <i class="fas fa-map-marker-alt"></i> <span id="currentLocation">Getting your location...</span>
                </div>

                <!-- Activity Score Circle -->
                <div id="activityScoreSection" class="p-4">
                    <div id="loadingActivity" class="text-center p-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                        <p class="mt-3">Calculating fish activity for your location...</p>
                    </div>
                    
                    <div id="activityResults" style="display: none;">
                        <div class="activity-score-circle">
                            <svg class="circle-progress" width="200" height="200">
                                <circle class="circle-bg" cx="100" cy="100" r="90"></circle>
                                <circle class="circle-progress-bar" id="progressCircle" cx="100" cy="100" r="90" 
                                        stroke-dasharray="565.48" stroke-dashoffset="565.48"></circle>
                            </svg>
                            <div class="score-number" id="scoreNumber">0</div>
                        </div>
                        <div class="activity-level-text" id="activityLevelText">Calculating...</div>

                        <!-- Activity Chart -->
                        <div class="activity-chart">
                            <div class="chart-labels">
                                <div>HIGH</div>
                                <div>MEDIUM</div>
                                <div>LOW</div>
                            </div>
                            <div class="chart-line">
                                <div class="chart-grid">
                                    <div class="grid-line"></div>
                                    <div class="grid-line"></div>
                                    <div class="grid-line"></div>
                                </div>
                                <svg class="chart-wave" id="activityChart" viewBox="0 0 400 140" preserveAspectRatio="none">
                                    <path id="chartPath" fill="none" stroke="#0d6efd" stroke-width="3" d="M0,70 Q100,70 200,70 T400,70"/>
                                </svg>
                            </div>
                            <div class="time-labels">
                                <span>04:00</span>
                                <span>08:00</span>
                                <span>12:00</span>
                                <span>16:00</span>
                                <span>20:00</span>
                            </div>
                        </div>

                        <!-- Times Section -->
                        <div class="times-section" id="timesSection">
                            <div class="times-column">
                                <h6>MAJOR TIMES</h6>
                                <div id="majorTimes">--:-- — --:--</div>
                            </div>
                            <div class="times-column">
                                <h6>MINOR TIMES</h6>
                                <div id="minorTimes">--:-- — --:--</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Map Selection Card (Hidden by default) -->
            <div class="activity-card" id="mapCard" style="display: none;">
                <div class="activity-header-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-map-marked-alt"></i> Select Fishing Location</h5>
                        <button class="btn btn-light btn-sm" onclick="toggleMapSection()">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                </div>
                <div class="map-section">
                    <div class="search-box">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="locationSearch" placeholder="Search city or town...">
                            <button class="btn btn-primary" type="button" onclick="searchLocation()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                        <div id="searchResults" style="display:none;" class="mt-2"></div>
                    </div>
                    <div id="map"></div>
                    <div class="mt-3">
                        <button class="btn btn-success w-100" id="calculateBtn" onclick="calculateActivity()" disabled>
                            <i class="fas fa-calculator"></i> Calculate Fish Activity
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= time() ?>"></script>
<script src="../assets/js/app.js?v=<?= time() ?>"></script>
<script>
    // Global variables
    let map;
    let marker;
    let selectedLat = null;
    let selectedLon = null;
    let selectedLocationName = null;
    let activityData = null;
    let userLocationObtained = false;

    // Initialize calendar
    function initCalendar() {
        const calendarDays = document.getElementById('calendarDays');
        const today = new Date();
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        
        for (let i = -3; i <= 3; i++) {
            const date = new Date(today);
            date.setDate(today.getDate() + i);
            
            const dayItem = document.createElement('div');
            dayItem.className = 'day-item' + (i === 0 ? ' active' : '');
            dayItem.innerHTML = `
                <span class="day-number">${date.getDate()}</span>
                <span class="day-name">${i === 0 ? 'Today' : days[date.getDay()]}</span>
            `;
            calendarDays.appendChild(dayItem);
        }
    }

    // Get user's current location
    function getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lon = position.coords.longitude;
                    userLocationObtained = true;
                    
                    // Get location name from coordinates using reverse geocoding
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=bg`)
                        .then(response => response.json())
                        .then(data => {
                            const locationName = data.display_name || 'Your Location';
                            selectLocation(lat, lon, locationName);
                            // Automatically calculate activity
                            calculateActivity();
                        })
                        .catch(error => {
                            console.error('Reverse geocoding error:', error);
                            selectLocation(lat, lon, 'Your Location');
                            calculateActivity();
                        });
                },
                function(error) {
                    console.error('Geolocation error:', error);
                    document.getElementById('currentLocation').textContent = 'Location access denied - Please select manually';
                    document.getElementById('loadingActivity').innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            Unable to get your location. Please click "Change Location" to select manually.
                        </div>
                    `;
                }
            );
        } else {
            document.getElementById('currentLocation').textContent = 'Geolocation not supported';
            document.getElementById('loadingActivity').innerHTML = `
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Geolocation is not supported by your browser. Please select location manually.
                </div>
            `;
        }
    }

    // Toggle map section
    function toggleMapSection() {
        const mapCard = document.getElementById('mapCard');
        if (mapCard.style.display === 'none') {
            mapCard.style.display = 'block';
            mapCard.scrollIntoView({ behavior: 'smooth' });
            setTimeout(() => map.invalidateSize(), 100);
        } else {
            mapCard.style.display = 'none';
        }
    }

    // Initialize map centered on Bulgaria
    function initMap() {
        map = L.map('map').setView([42.7339, 25.4858], 7);

        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add click event to map
        map.on('click', function(e) {
            selectLocation(e.latlng.lat, e.latlng.lng, 'Custom Location');
        });
    }

    // Search location using Nominatim API
    async function searchLocation() {
        const query = document.getElementById('locationSearch').value.trim();
        if (!query) {
            alert('Please enter a city or town name');
            return;
        }

        const resultsDiv = document.getElementById('searchResults');
        resultsDiv.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Searching...</p>';
        resultsDiv.style.display = 'block';

        try {
            const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&accept-language=bg`);
            const data = await response.json();

            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="alert alert-warning">No results found</div>';
                return;
            }

            let html = '<div class="list-group">';
            data.forEach(result => {
                html += `
                    <a href="#" class="list-group-item list-group-item-action" onclick="selectSearchResult(${result.lat}, ${result.lon}, '${result.display_name.replace(/'/g, "\\'")}'); return false;">
                        <i class="fas fa-map-marker-alt text-primary"></i> ${result.display_name}
                    </a>
                `;
            });
            html += '</div>';
            resultsDiv.innerHTML = html;
        } catch (error) {
            resultsDiv.innerHTML = '<div class="alert alert-danger">Search error</div>';
            console.error('Search error:', error);
        }
    }

    function selectSearchResult(lat, lon, name) {
        selectLocation(lat, lon, name);
        document.getElementById('searchResults').style.display = 'none';
        document.getElementById('locationSearch').value = '';
        map.setView([lat, lon], 13);
    }

    // Allow Enter key to trigger search
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('locationSearch');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    searchLocation();
                }
            });
        }
    });

    function selectLocation(lat, lon, name) {
        selectedLat = lat;
        selectedLon = lon;
        selectedLocationName = name;

        // Remove old marker
        if (marker) {
            map.removeLayer(marker);
        }

        // Add new marker with fish icon
        marker = L.marker([lat, lon], {
            icon: L.divIcon({
                html: '<div style="font-size: 32px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5);">🐟</div>',
                className: 'fish-marker',
                iconSize: [32, 32],
                iconAnchor: [16, 32]
            })
        }).addTo(map);

        marker.bindPopup(`<strong>${name}</strong><br>Lat: ${lat.toFixed(4)}, Lon: ${lon.toFixed(4)}`).openPopup();

        // Update location badge
        document.getElementById('currentLocation').textContent = name.length > 50 ? name.substring(0, 50) + '...' : name;
        document.getElementById('calculateBtn').disabled = false;
    }

    function calculateActivity() {
        if (!selectedLat || !selectedLon) {
            alert('Please select a location first');
            return;
        }
        
        // Show loading
        document.getElementById('loadingActivity').style.display = 'block';
        document.getElementById('activityResults').style.display = 'none';
        
        // Hide map card
        document.getElementById('mapCard').style.display = 'none';
        
        fetch(`../../be/activity/feed.php?action=calculate_fish_activity&location=${encodeURIComponent(selectedLocationName)}&lat=${selectedLat}&lon=${selectedLon}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    activityData = data;
                    displayActivityData(data);
                } else {
                    document.getElementById('loadingActivity').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> ${data.error || 'Unable to calculate activity'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loadingActivity').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle"></i> Failed to calculate fish activity. Please try again.
                    </div>
                `;
            });
    }

    function displayActivityData(data) {
        const score = Math.round(data.activity_score);
        const factors = data.factors;
        
        // Hide loading, show results
        document.getElementById('loadingActivity').style.display = 'none';
        document.getElementById('activityResults').style.display = 'block';
        
        // Update score circle
        document.getElementById('scoreNumber').textContent = score;
        const circle = document.getElementById('progressCircle');
        const circumference = 565.48;
        const offset = circumference - (score / 100) * circumference;
        circle.style.strokeDashoffset = offset;
        
        // Update circle color based on score
        if (score >= 80) {
            circle.style.stroke = '#198754';
            document.getElementById('scoreNumber').style.color = '#198754';
        } else if (score >= 60) {
            circle.style.stroke = '#28a745';
            document.getElementById('scoreNumber').style.color = '#28a745';
        } else if (score >= 40) {
            circle.style.stroke = '#ffc107';
            document.getElementById('scoreNumber').style.color = '#ffc107';
        } else if (score >= 20) {
            circle.style.stroke = '#fd7e14';
            document.getElementById('scoreNumber').style.color = '#fd7e14';
        } else {
            circle.style.stroke = '#dc3545';
            document.getElementById('scoreNumber').style.color = '#dc3545';
        }
        
        // Update activity level text
        let levelText = 'Very low fish activity';
        if (score >= 80) levelText = 'Excellent fish activity';
        else if (score >= 60) levelText = 'Good fish activity';
        else if (score >= 40) levelText = 'Moderate fish activity';
        else if (score >= 20) levelText = 'Low fish activity';
        document.getElementById('activityLevelText').textContent = levelText;
        
        // Update chart
        updateActivityChart(factors);
        
        // Update times
        updateSolunarTimes(factors);
    }

    function updateActivityChart(factors) {
        // Create a simple wave pattern
        const currentHour = new Date().getHours();
        const path = generateChartPath(currentHour);
        document.getElementById('chartPath').setAttribute('d', path);
    }

    function generateChartPath(currentHour) {
        // Generate random-ish wave pattern with peaks around dawn/dusk
        const points = [];
        for (let x = 0; x <= 400; x += 20) {
            const hour = (x / 400) * 24;
            let y = 70; // middle
            
            // Early morning peak (4-8)
            if (hour >= 4 && hour <= 8) {
                y = 30 + Math.sin((hour - 4) / 4 * Math.PI) * 30;
            }
            // Evening peak (17-21)
            else if (hour >= 17 && hour <= 21) {
                y = 30 + Math.sin((hour - 17) / 4 * Math.PI) * 30;
            }
            // Daytime moderate
            else if (hour > 8 && hour < 17) {
                y = 70 + Math.random() * 20 - 10;
            }
            // Night low
            else {
                y = 100 + Math.random() * 20 - 10;
            }
            
            points.push(`${x},${y}`);
        }
        
        return `M ${points.join(' L ')}`;
    }

    function updateSolunarTimes(factors) {
        if (factors.solunar_periods) {
            const sp = factors.solunar_periods;
            document.getElementById('majorTimes').innerHTML = `
                <div class="time-item">${formatTime(sp.major1.start)} — ${formatTime(sp.major1.end)}</div>
                <div class="time-item">${formatTime(sp.major2.start)} — ${formatTime(sp.major2.end)}</div>
            `;
            document.getElementById('minorTimes').innerHTML = `
                <div class="time-item">${formatTime(sp.minor1.start)} — ${formatTime(sp.minor1.end)}</div>
                <div class="time-item">${formatTime(sp.minor2.start)} — ${formatTime(sp.minor2.end)}</div>
            `;
        } else {
            document.getElementById('majorTimes').innerHTML = '<div class="time-item">04:58 — 07:28<br>17:39 — 20:09</div>';
            document.getElementById('minorTimes').innerHTML = '<div class="time-item">00:30 — 02:00<br>10:28 — 11:58</div>';
        }
    }

    function formatTime(time) {
        if (typeof time === 'number') {
            if (time < 0) time += 24;
            if (time >= 24) time -= 24;
            const hours = Math.floor(time);
            const minutes = Math.round((time - hours) * 60);
            return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
        }
        return time;
    }

    // Initialize on page load
    window.addEventListener('load', function() {
        initCalendar();
        initMap();
        // Get user location and calculate activity automatically
        getUserLocation();
    });
</script>

</body>
</html>
