/**
 * Fish Activity Feed — Enhanced UI
 * Uses the new /api/fish-activity endpoint with species selection,
 * factor breakdown cards, data-driven 24h chart, and best-times display.
 */

// ── State ────────────────────────────────────────────────
let map;
let marker;
let selectedLat  = null;
let selectedLon  = null;
let selectedLocationName = null;
let activityData = null;
let userLocationObtained = false;
let selectedSpecies = 'general';
let speciesList = {};

const FACTOR_ICONS = {
    solunar:       'fa-moon',
    time:          'fa-clock',
    pressure:      'fa-tachometer-alt',
    temperature:   'fa-thermometer-half',
    wind_cloud:    'fa-wind',
    humidity:      'fa-tint',
    precipitation: 'fa-cloud-rain',
    moon_light:    'fa-adjust',
};

const FACTOR_COLORS = {
    solunar:       '#6366f1',
    time:          '#f59e0b',
    pressure:      '#10b981',
    temperature:   '#ef4444',
    wind_cloud:    '#3b82f6',
    humidity:      '#06b6d4',
    precipitation: '#8b5cf6',
    moon_light:    '#64748b',
};

// ── Initialization ────────────────────────────────────────

window.addEventListener('load', function () {
    initCalendar();
    initMap();
    loadSpeciesList();
    getUserLocation();
});

// ── Species ───────────────────────────────────────────────

async function loadSpeciesList() {
    try {
        const res = await fetch(resolvePath('api/fish-activity/species'));
        const data = await res.json();
        if (data.success && data.species) {
            speciesList = data.species;
            renderSpeciesChips();
        }
    } catch (e) {
        // Fallback: just show general
        speciesList = { general: { en: 'All species', bg: 'Всички' } };
        renderSpeciesChips();
    }
}

function renderSpeciesChips() {
    const container = document.getElementById('speciesChips');
    if (!container) return;
    container.innerHTML = '';

    const lang = document.documentElement.lang || 'en';

    for (const [key, labels] of Object.entries(speciesList)) {
        const chip = document.createElement('button');
        chip.type = 'button';
        chip.className = 'species-chip' + (key === selectedSpecies ? ' active' : '');
        chip.textContent = lang === 'bg' ? labels.bg : labels.en;
        chip.dataset.species = key;
        chip.addEventListener('click', () => {
            selectedSpecies = key;
            document.querySelectorAll('.species-chip').forEach(c => c.classList.remove('active'));
            chip.classList.add('active');
            if (selectedLat && selectedLon) calculateActivity();
        });
        container.appendChild(chip);
    }
}

// ── Calendar ─────────────────────────────────────────────

function initCalendar() {
    const container = document.getElementById('calendarDays');
    if (!container) return;
    const today = new Date();
    const t = window.fishingTranslations || {};

    for (let i = -3; i <= 3; i++) {
        const d = new Date(today);
        d.setDate(today.getDate() + i);

        const el = document.createElement('div');
        el.className = 'day-item' + (i === 0 ? ' active' : '');
        const dayName = i === 0
            ? (t.today || 'Today')
            : (t.days && t.days[d.getDay()] ? t.days[d.getDay()] : ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][d.getDay()]);
        el.innerHTML = `<span class="day-number">${d.getDate()}</span><span class="day-name">${dayName}</span>`;
        container.appendChild(el);
    }
}

// ── Geolocation ──────────────────────────────────────────

function getUserLocation() {
    if (!navigator.geolocation) {
        document.getElementById('currentLocation').textContent = 'Geolocation not supported';
        showLocationFallbackMessage();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        pos => {
            const { latitude: lat, longitude: lon } = pos.coords;
            userLocationObtained = true;

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&accept-language=bg`)
                .then(r => r.json())
                .then(data => {
                    selectLocation(lat, lon, data.display_name || 'Your Location');
                    calculateActivity();
                })
                .catch(() => {
                    selectLocation(lat, lon, 'Your Location');
                    calculateActivity();
                });
        },
        () => {
            document.getElementById('currentLocation').textContent = 'Location access denied';
            showLocationFallbackMessage();
        }
    );
}

function showLocationFallbackMessage() {
    document.getElementById('loadingActivity').innerHTML = `
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            Unable to get location. Click "Change Location" to select manually.
        </div>`;
}

// ── Map ──────────────────────────────────────────────────

function toggleMapSection() {
    const card = document.getElementById('mapCard');
    if (card.style.display === 'none') {
        card.style.display = 'block';
        card.scrollIntoView({ behavior: 'smooth' });
        setTimeout(() => map.invalidateSize(), 100);
    } else {
        card.style.display = 'none';
    }
}

function initMap() {
    map = L.map('map').setView([42.7339, 25.4858], 7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    map.on('click', e => selectLocation(e.latlng.lat, e.latlng.lng, 'Custom Location'));
}

async function searchLocation() {
    const q = document.getElementById('locationSearch').value.trim();
    if (!q) return;

    const res = document.getElementById('searchResults');
    res.innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i></p>';
    res.style.display = 'block';

    try {
        const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&limit=5&accept-language=bg`);
        const data = await response.json();
        if (!data.length) { res.innerHTML = '<div class="alert alert-warning">No results</div>'; return; }

        res.innerHTML = '<div class="list-group">' + data.map(r => {
            const safe = escapeHtml(r.display_name);
            return `<a href="#" class="list-group-item list-group-item-action" onclick="selectSearchResult(${r.lat},${r.lon},'${safe.replace(/'/g, "\\'")}');return false;">
                        <i class="fas fa-map-marker-alt text-primary"></i> ${safe}</a>`;
        }).join('') + '</div>';
    } catch {
        res.innerHTML = '<div class="alert alert-danger">Search error</div>';
    }
}

function selectSearchResult(lat, lon, name) {
    selectLocation(lat, lon, name);
    document.getElementById('searchResults').style.display = 'none';
    document.getElementById('locationSearch').value = '';
    map.setView([lat, lon], 13);
}

document.addEventListener('DOMContentLoaded', () => {
    const si = document.getElementById('locationSearch');
    if (si) si.addEventListener('keypress', e => { if (e.key === 'Enter') searchLocation(); });
});

function selectLocation(lat, lon, name) {
    selectedLat = lat;
    selectedLon = lon;
    selectedLocationName = name;

    if (marker) map.removeLayer(marker);
    marker = L.marker([lat, lon], {
        icon: L.divIcon({
            html: '<div style="font-size:32px;text-shadow:2px 2px 4px rgba(0,0,0,.5)">🐟</div>',
            className: 'fish-marker', iconSize: [32, 32], iconAnchor: [16, 32]
        })
    }).addTo(map).bindPopup(`<strong>${escapeHtml(name)}</strong><br>Lat: ${lat.toFixed(4)}, Lon: ${lon.toFixed(4)}`).openPopup();

    document.getElementById('currentLocation').textContent = name.length > 50 ? name.substring(0, 50) + '...' : name;
    document.getElementById('calculateBtn').disabled = false;
}

// ── Calculate Activity ───────────────────────────────────

function calculateActivity() {
    if (!selectedLat || !selectedLon) return;

    document.getElementById('loadingActivity').style.display = 'block';
    document.getElementById('activityResults').style.display = 'none';
    document.getElementById('mapCard').style.display = 'none';

    const url = resolvePath('api/fish-activity') +
        `?lat=${selectedLat}&lon=${selectedLon}&species=${selectedSpecies}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                activityData = data;
                displayActivityData(data);
            } else {
                document.getElementById('loadingActivity').innerHTML = `
                    <div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ${data.error || 'Calculation error'}</div>`;
            }
        })
        .catch(() => {
            document.getElementById('loadingActivity').innerHTML = `
                <div class="alert alert-danger"><i class="fas fa-times-circle"></i> Failed to calculate. Try again.</div>`;
        });
}

// ── Display Results ──────────────────────────────────────

function displayActivityData(data) {
    const score = data.total_score;
    const factors = data.factors;
    const t = window.fishingTranslations || {};

    document.getElementById('loadingActivity').style.display = 'none';
    document.getElementById('activityResults').style.display = 'block';

    // Score circle animation
    animateScore(score);

    // Level text
    let lvl = t.very_low_activity || 'Very low';
    if (score >= 80)      lvl = t.excellent_activity || 'Excellent activity';
    else if (score >= 60) lvl = t.good_activity || 'Good activity';
    else if (score >= 40) lvl = t.moderate_activity || 'Moderate activity';
    else if (score >= 20) lvl = t.low_activity || 'Low activity';
    document.getElementById('activityLevelText').textContent = lvl;

    // Moon phase
    if (data.moon_phase) {
        const mp = data.moon_phase;
        document.getElementById('moonIcon').textContent = mp.icon || '🌕';
        const moonKey = 'moon_' + mp.name;
        document.getElementById('moonName').textContent = t[moonKey] || mp.name.replace(/_/g, ' ');
        document.getElementById('moonIllum').textContent = `${t.illumination || 'Illumination'}: ${mp.illumination}%`;
    }

    // Water temp
    if (data.water_temp_est !== undefined) {
        document.getElementById('waterTempValue').textContent = data.water_temp_est;
    }

    // Factor cards
    renderFactorCards(factors);

    // Best times
    renderBestTimes(data.best_times || []);

    // 24h chart from real data
    renderHourlyChart(data.hourly_curve || {});

    // Solunar times
    updateSolunarTimes(data.solunar_periods || {});
}

function animateScore(target) {
    const numEl = document.getElementById('scoreNumber');
    const circle = document.getElementById('progressCircle');
    const circumference = 502.65;

    // Color
    let color;
    if (target >= 80)      color = '#059669';
    else if (target >= 60) color = '#10b981';
    else if (target >= 40) color = '#f59e0b';
    else if (target >= 20) color = '#f97316';
    else                   color = '#ef4444';

    circle.style.stroke = color;
    numEl.style.color = color;

    // Animate number + circle
    let current = 0;
    const step = Math.ceil(target / 40);
    const timer = setInterval(() => {
        current = Math.min(current + step, target);
        numEl.textContent = current;
        const offset = circumference - (current / 100) * circumference;
        circle.style.strokeDashoffset = offset;
        if (current >= target) clearInterval(timer);
    }, 25);
}

// ── Factor Cards ─────────────────────────────────────────

function renderFactorCards(factors) {
    const grid = document.getElementById('factorsGrid');
    if (!grid) return;
    const t = window.fishingTranslations || {};

    grid.innerHTML = '';
    const order = ['solunar', 'time', 'pressure', 'temperature', 'wind_cloud', 'humidity', 'precipitation', 'moon_light'];

    for (const key of order) {
        const f = factors[key];
        if (!f) continue;

        const label = t['factor_' + key] || key.replace(/_/g, ' ');
        const icon = FACTOR_ICONS[key] || 'fa-circle';
        const clr = FACTOR_COLORS[key] || '#666';
        const pct = Math.min(100, Math.max(0, f.score));
        const weight = Math.round((f.weight || 0) * 100);

        const card = document.createElement('div');
        card.className = 'factor-card';
        card.innerHTML = `
            <div class="factor-header">
                <i class="fas ${icon}" style="color:${clr}"></i>
                <span class="factor-label">${label}</span>
                <span class="factor-weight">${weight}%</span>
            </div>
            <div class="factor-bar-bg">
                <div class="factor-bar" style="width:${pct}%;background:${clr}"></div>
            </div>
            <div class="factor-score">${Math.round(pct)}/100</div>
        `;
        grid.appendChild(card);
    }
}

// ── Best Times ───────────────────────────────────────────

function renderBestTimes(bestTimes) {
    const list = document.getElementById('bestTimesList');
    if (!list) return;
    const t = window.fishingTranslations || {};

    if (!bestTimes.length) {
        list.innerHTML = '<p class="text-muted">—</p>';
        return;
    }

    list.innerHTML = bestTimes.map((w, i) => {
        const medal = i === 0 ? '🥇' : i === 1 ? '🥈' : '🥉';
        return `<div class="best-time-item">
            <span class="best-time-medal">${medal}</span>
            <span class="best-time-range">${w.start} — ${w.end}</span>
            <span class="best-time-score">${t.peak || 'Peak'}: ${w.peak_score}</span>
        </div>`;
    }).join('');
}

// ── 24h Chart ────────────────────────────────────────────

function renderHourlyChart(curve) {
    const svgWidth = 480;
    const svgHeight = 140;
    const padding = 5;

    const points = [];
    for (let h = 0; h < 24; h++) {
        const x = (h / 23) * svgWidth;
        const score = curve[h] !== undefined ? curve[h] : 50;
        const y = svgHeight - padding - ((score / 100) * (svgHeight - 2 * padding));
        points.push({ x, y, h, score });
    }

    // Smooth curve using cubic Bezier
    let linePath = `M${points[0].x},${points[0].y}`;
    let fillPath = `M0,${svgHeight} L${points[0].x},${points[0].y}`;

    for (let i = 1; i < points.length; i++) {
        const prev = points[i - 1];
        const curr = points[i];
        const cpx1 = prev.x + (curr.x - prev.x) / 3;
        const cpx2 = prev.x + 2 * (curr.x - prev.x) / 3;
        linePath += ` C${cpx1},${prev.y} ${cpx2},${curr.y} ${curr.x},${curr.y}`;
        fillPath += ` C${cpx1},${prev.y} ${cpx2},${curr.y} ${curr.x},${curr.y}`;
    }
    fillPath += ` L${svgWidth},${svgHeight} Z`;

    document.getElementById('chartPath').setAttribute('d', linePath);
    document.getElementById('chartFill').setAttribute('d', fillPath);

    // Current hour dot
    const now = new Date().getHours();
    const dot = document.getElementById('chartNowDot');
    if (dot && points[now]) {
        dot.setAttribute('cx', points[now].x);
        dot.setAttribute('cy', points[now].y);
        dot.style.display = 'block';
    }
}

// ── Solunar Times ────────────────────────────────────────

function updateSolunarTimes(periods) {
    if (periods.major1) {
        document.getElementById('majorTimes').innerHTML = `
            <div class="time-item"><i class="fas fa-sun text-warning"></i> ${fmtTime(periods.major1.start)} — ${fmtTime(periods.major1.end)}</div>
            <div class="time-item"><i class="fas fa-moon text-info"></i> ${fmtTime(periods.major2.start)} — ${fmtTime(periods.major2.end)}</div>`;
        document.getElementById('minorTimes').innerHTML = `
            <div class="time-item"><i class="fas fa-arrow-up text-success"></i> ${fmtTime(periods.minor1.start)} — ${fmtTime(periods.minor1.end)}</div>
            <div class="time-item"><i class="fas fa-arrow-down text-danger"></i> ${fmtTime(periods.minor2.start)} — ${fmtTime(periods.minor2.end)}</div>`;
    }
}

function fmtTime(val) {
    if (typeof val !== 'number') return val;
    let h = val;
    if (h < 0) h += 24;
    if (h >= 24) h -= 24;
    const hours = Math.floor(h);
    const mins = Math.round((h - hours) * 60);
    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}
