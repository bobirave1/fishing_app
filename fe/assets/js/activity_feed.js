// Fish Activity Feed JavaScript

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
    const days = window.fishingTranslations.days;
    for (let i = -3; i <= 3; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);
        const dayItem = document.createElement('div');
        dayItem.className = 'day-item' + (i === 0 ? ' active' : '');
        dayItem.innerHTML = `
            <span class="day-number">${date.getDate()}</span>
            <span class="day-name">${i === 0 ? window.fishingTranslations.today : days[date.getDay()]}</span>
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
    
    // Update activity level text (use translations)
    let levelText = window.fishingTranslations.very_low_activity;
    if (score >= 80) levelText = window.fishingTranslations.excellent_activity;
    else if (score >= 60) levelText = window.fishingTranslations.good_activity;
    else if (score >= 40) levelText = window.fishingTranslations.moderate_activity;
    else if (score >= 20) levelText = window.fishingTranslations.low_activity;
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
