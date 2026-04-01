<?php
/**
 * Search endpoint — delegates to SearchService.
 */
require_once __DIR__ . '/../config/bootstrap.php';

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all';

header('Content-Type: application/json');

if (strlen($query) < 2) {
    exit(json_encode(['success' => false, 'error' => 'Search query too short']));
}

if (strlen($query) > 100) {
    exit(json_encode(['success' => false, 'error' => 'Search query too long']));
}

$container = $GLOBALS['container'];
$searchService = $container->get(App\Services\SearchService::class);

$currentUserId = $_SESSION['user_id'] ?? 0;
$results = $searchService->search($query, $currentUserId, $type);

exit(json_encode([
    'success' => true,
    'query' => htmlspecialchars($query),
    'results' => $results
]));
