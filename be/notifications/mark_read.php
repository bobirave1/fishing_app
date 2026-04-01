<?php
/**
 * Mark notifications read — delegates to NotificationService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$container = $GLOBALS['container'];
$notifService = $container->get(App\Services\NotificationService::class);

$userId = $_SESSION['user_id'];
$notificationId = $_POST['notification_id'] ?? null;
$action = $_POST['action'] ?? 'mark_read';

header('Content-Type: application/json');

if ($action === 'mark_read' && $notificationId) {
    $notifService->markRead((int)$notificationId, $userId);
    exit(json_encode(['success' => true]));

} else if ($action === 'mark_all_read') {
    $notifService->markAllRead($userId);
    exit(json_encode(['success' => true]));

} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid action']));
}
