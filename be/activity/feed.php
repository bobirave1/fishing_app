<?php
/**
 * Activity feed endpoint — uses bootstrap + services.
 */
require_once __DIR__ . '/../../config/bootstrap.php';
require_once __DIR__ . '/fish_calculator.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$container = $GLOBALS['container'];
$pdo = $container->get(\PDO::class);
$logger = $container->get(App\Core\Logger::class);

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get_feed';

header('Content-Type: application/json');

if ($action === 'calculate_fish_activity') {
    $location = $_GET['location'] ?? '';
    $lat = $_GET['lat'] ?? 42.7;
    $lon = $_GET['lon'] ?? 25.5;

    if (empty($location)) {
        http_response_code(400);
        exit(json_encode(['error' => 'Location required']));
    }

    try {
        $lang = $_SESSION['lang'] ?? 'en';
        $weatherService = $container->get(App\Services\WeatherService::class);
        $weatherData = $weatherService->getWeather((float)$lat, (float)$lon, $lang);

        $activityScore = calculateFishActivityScore($weatherData, $lat, $lon);

        exit(json_encode([
            'success' => true,
            'activity_score' => $activityScore['total_score'],
            'factors' => $activityScore['factors'],
            'location' => $location
        ]));
    } catch (\Exception $e) {
        $logger->error("Fish activity calculation error: " . $e->getMessage());
        exit(json_encode([
            'success' => false,
            'error' => 'Calculation error: ' . $e->getMessage()
        ]));
    }

} else if ($action === 'get_fish_activity') {
    $limit = isset($_GET['limit']) ? max(1, min(50, (int)$_GET['limit'])) : 20;
    $offset = isset($_GET['offset']) ? max(0, (int)$_GET['offset']) : 0;
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
    exit(json_encode(['success' => true, 'message' => 'Activity logging disabled']));

} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid action']));
}
