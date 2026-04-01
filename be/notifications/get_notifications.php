<?php
/**
 * Get notifications — delegates to NotificationService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

try {
    $container = $GLOBALS['container'];
    $notifService = $container->get(App\Services\NotificationService::class);

    $userId = $_SESSION['user_id'];
    $limit = intval($_GET['limit'] ?? 10);
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'];

    $result = $notifService->getForUser($userId, $limit, $unreadOnly);

    exit(json_encode(array_merge(['success' => true], $result)));

} catch (\Exception $e) {
    $container->get(App\Core\Logger::class)->error("Notifications error: " . $e->getMessage());
    exit(json_encode([
        'success' => true,
        'notifications' => [],
        'unread_count' => 0
    ]));
}
