<?php
require '../../config/security.php';
secureSession();
setSecurityHeaders();

require '../../config/database.php';
require '../../config/avatar_helper.php';
// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require '../../config/languages.php'; // Add language support

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
    <title>Fish Activity - FISHINGLORY</title>
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

<?php include '../components/navbar.php'; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Fish Activity Card -->
            <div class="activity-card" id="activityCard">
                <div class="activity-header-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <button class="btn btn-light btn-sm" onclick="toggleMapSection()" title="<?= __('change_location') ?>">
                            <i class="fas fa-map-marker-alt"></i> <?= __('change_location') ?>
                        </button>
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
                        <div class="activity-score-circle">
                            <svg class="circle-progress" width="200" height="200">
                                <circle class="circle-bg" cx="100" cy="100" r="90"></circle>
                                <circle class="circle-progress-bar" id="progressCircle" cx="100" cy="100" r="90" 
                                        stroke-dasharray="565.48" stroke-dashoffset="565.48"></circle>
                            </svg>
                            <div class="score-number" id="scoreNumber">0</div>
                        </div>
                        <div class="activity-level-text" id="activityLevelText"><?= __('calculating') ?></div>

                        <!-- Activity Chart -->
                        <div class="activity-chart">
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
        days: {
            0: "<?= __('sun') ?>", 1: "<?= __('mon') ?>", 2: "<?= __('tue') ?>", 
            3: "<?= __('wed') ?>", 4: "<?= __('thu') ?>", 5: "<?= __('fri') ?>", 6: "<?= __('sat') ?>"
        }
    };
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="../assets/js/activity_feed.js?v=<?= assetVersion('fe/assets/js/activity_feed.js') ?>"></script>

<?php include '../components/footer.php'; ?>

</body>
</html>
