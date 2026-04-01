<?php
require __DIR__ . '/../../config/security.php';
secureSession();
setSecurityHeaders();

require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/avatar_helper.php';
require __DIR__ . '/../../config/languages.php';

// Handle language/theme switches via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        http_response_code(400);
        die('Invalid CSRF token');
    }

    $action = $_POST['action'];
    if ($action === 'switch_lang' && isset($_POST['lang'])) {
        $newLang = (string) $_POST['lang'];
        if (in_array($newLang, ['bg', 'en'], true)) {
            $_SESSION['lang'] = $newLang;
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }

    if ($action === 'switch_theme' && isset($_POST['theme'])) {
        $newTheme = (string) $_POST['theme'];
        if (in_array($newTheme, ['light', 'dark'], true)) {
            $_SESSION['theme'] = $newTheme;
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

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
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('fish_activity') ?> | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/activity.css?v=<?= assetVersion('fe/assets/css/activity.css') ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="../assets/img/logo_rounded.png">
    <!-- Leaflet CSS for Map -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include __DIR__ . '/../components/navbar.php'; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Fish Activity Card -->
            <div class="activity-card" id="activityCard">
                <div class="activity-header-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-fish"></i> <?= __('fish_activity_prediction') ?></h5>
                        <button class="btn btn-light btn-sm" onclick="toggleMapSection()" title="<?= __('change_location') ?>">
                            <i class="fas fa-map-marker-alt"></i> <?= __('change_location') ?>
                        </button>
                    </div>
                </div>

                <!-- Species Selector -->
                <div class="species-selector" id="speciesSelector">
                    <label class="species-label"><?= __('select_species') ?></label>
                    <div class="species-chips" id="speciesChips">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Calendar Days -->
                <div class="calendar-days" id="calendarDays">
                    <!-- Will be populated by JavaScript -->
                </div>

                <!-- Location Badge -->
                <div class="location-badge">
                    <i class="fas fa-map-marker-alt"></i> <span id="currentLocation"><?= __('getting_your_location') ?></span>
                </div>

                <!-- Activity Score Circle -->
                <div id="activityScoreSection" class="p-4">
                    <div id="loadingActivity" class="text-center p-5">
                        <i class="fas fa-spinner fa-spin fa-3x text-primary"></i>
                        <p class="mt-3"><?= __('calculating_fish_activity') ?></p>
                    </div>
                    
                    <div id="activityResults" style="display: none;">
                        <!-- Score Circle + Moon Phase Row -->
                        <div class="score-moon-row">
                            <div class="activity-score-circle">
                                <svg class="circle-progress" width="180" height="180">
                                    <circle class="circle-bg" cx="90" cy="90" r="80"></circle>
                                    <circle class="circle-progress-bar" id="progressCircle" cx="90" cy="90" r="80" 
                                            stroke-dasharray="502.65" stroke-dashoffset="502.65"></circle>
                                </svg>
                                <div class="score-number" id="scoreNumber">0</div>
                            </div>
                            <div class="moon-phase-panel" id="moonPhasePanel">
                                <div class="moon-icon" id="moonIcon">🌕</div>
                                <div class="moon-name" id="moonName">—</div>
                                <div class="moon-illumination" id="moonIllum">0%</div>
                                <div class="water-temp" id="waterTemp">
                                    <i class="fas fa-water"></i> <span id="waterTempValue">—</span>°C
                                </div>
                            </div>
                        </div>

                        <div class="activity-level-text" id="activityLevelText"><?= __('calculating') ?></div>

                        <!-- Factor Breakdown Cards -->
                        <div class="factors-grid" id="factorsGrid">
                            <!-- Populated by JS -->
                        </div>

                        <!-- Best Times -->
                        <div class="best-times-section" id="bestTimesSection">
                            <h6 class="section-title"><i class="fas fa-star"></i> <?= __('best_fishing_times') ?></h6>
                            <div class="best-times-list" id="bestTimesList">
                                <!-- Populated by JS -->
                            </div>
                        </div>

                        <!-- Activity Chart (24h) -->
                        <div class="activity-chart">
                            <h6 class="section-title"><i class="fas fa-chart-area"></i> <?= __('hourly_prediction') ?></h6>
                            <div class="chart-container">
                                <div class="chart-labels">
                                    <div><?= __('high') ?></div>
                                    <div><?= __('medium') ?></div>
                                    <div><?= __('low') ?></div>
                                </div>
                                <div class="chart-line">
                                    <div class="chart-grid">
                                        <div class="grid-line"></div>
                                        <div class="grid-line"></div>
                                        <div class="grid-line"></div>
                                    </div>
                                    <svg class="chart-wave" id="activityChart" viewBox="0 0 480 140" preserveAspectRatio="none">
                                        <defs>
                                            <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                                                <stop offset="0%" stop-color="var(--primary-color)" stop-opacity="0.3"/>
                                                <stop offset="100%" stop-color="var(--primary-color)" stop-opacity="0.02"/>
                                            </linearGradient>
                                        </defs>
                                        <path id="chartFill" fill="url(#chartGradient)" d="M0,140 L0,70 Q120,70 240,70 T480,70 L480,140 Z"/>
                                        <path id="chartPath" fill="none" stroke="var(--primary-color)" stroke-width="2.5" d="M0,70 Q120,70 240,70 T480,70"/>
                                        <circle id="chartNowDot" r="5" fill="var(--primary-color)" cx="0" cy="70" style="display:none"/>
                                    </svg>
                                </div>
                                <div class="time-labels">
                                    <span>00</span><span>04</span><span>08</span><span>12</span><span>16</span><span>20</span><span>24</span>
                                </div>
                            </div>
                        </div>

                        <!-- Solunar Times Section -->
                        <div class="times-section" id="timesSection">
                            <div class="times-column">
                                <h6><?= __('major_times') ?></h6>
                                <div id="majorTimes">--:-- — --:--</div>
                            </div>
                            <div class="times-column">
                                <h6><?= __('minor_times') ?></h6>
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
                        <h5 class="mb-0"><i class="fas fa-map-marked-alt"></i> <?= __('select_fishing_location') ?></h5>
                        <button class="btn btn-light btn-sm" onclick="toggleMapSection()">
                            <i class="fas fa-times"></i> <?= __('close') ?>
                        </button>
                    </div>
                </div>
                <div class="map-section">
                    <div class="search-box">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="locationSearch" placeholder="<?= __('search_city_town') ?>">
                            <button class="btn btn-primary" type="button" onclick="searchLocation()">
                                <i class="fas fa-search"></i> <?= __('search') ?>
                            </button>
                        </div>
                        <div id="searchResults" style="display:none;" class="mt-2"></div>
                    </div>
                    <div id="map"></div>
                    <div class="mt-3">
                        <button class="btn btn-success w-100" id="calculateBtn" onclick="calculateActivity()" disabled>
                            <i class="fas fa-calculator"></i> <?= __('calculate_fish_activity_btn') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    window.fishingTranslations = {
        excellent_activity: "<?= __('excellent_activity') ?>",
        good_activity: "<?= __('good_activity') ?>",
        moderate_activity: "<?= __('moderate_activity') ?>",
        low_activity: "<?= __('low_activity') ?>",
        very_low_activity: "<?= __('very_low_activity') ?>",
        today: "<?= __('today') ?>",
        best_fishing_times: "<?= __('best_fishing_times') ?>",
        hourly_prediction: "<?= __('hourly_prediction') ?>",
        peak: "<?= __('peak') ?>",
        score: "<?= __('score_label') ?>",
        factor_solunar: "<?= __('factor_solunar') ?>",
        factor_time: "<?= __('factor_time') ?>",
        factor_pressure: "<?= __('factor_pressure') ?>",
        factor_temperature: "<?= __('factor_temperature') ?>",
        factor_wind_cloud: "<?= __('factor_wind_cloud') ?>",
        factor_humidity: "<?= __('factor_humidity') ?>",
        factor_precipitation: "<?= __('factor_precipitation') ?>",
        factor_moon_light: "<?= __('factor_moon_light') ?>",
        moon_new_moon: "<?= __('moon_new_moon') ?>",
        moon_full_moon: "<?= __('moon_full_moon') ?>",
        moon_waxing_crescent: "<?= __('moon_waxing_crescent') ?>",
        moon_first_quarter: "<?= __('moon_first_quarter') ?>",
        moon_waxing_gibbous: "<?= __('moon_waxing_gibbous') ?>",
        moon_waning_gibbous: "<?= __('moon_waning_gibbous') ?>",
        moon_last_quarter: "<?= __('moon_last_quarter') ?>",
        moon_waning_crescent: "<?= __('moon_waning_crescent') ?>",
        illumination: "<?= __('illumination') ?>",
        water_temperature: "<?= __('water_temperature') ?>",
        days: {
            0: "<?= __('sun') ?>", 1: "<?= __('mon') ?>", 2: "<?= __('tue') ?>", 
            3: "<?= __('wed') ?>", 4: "<?= __('thu') ?>", 5: "<?= __('fri') ?>", 6: "<?= __('sat') ?>"
        }
    };
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../assets/js/helpers.js?v=<?= assetVersion('fe/assets/js/helpers.js') ?>"></script>
<script src="../assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="../assets/js/notifications.js?v=<?= assetVersion('fe/assets/js/notifications.js') ?>"></script>
<script src="../assets/js/activity_feed.js?v=<?= assetVersion('fe/assets/js/activity_feed.js') ?>"></script>

<?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>
