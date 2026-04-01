<?php
/**
 * Follow/Unfollow endpoint — delegates to UserService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$container = $GLOBALS['container'];
$userService = $container->get(App\Services\UserService::class);

$userId = $_SESSION['user_id'];
$targetId = $_POST['target_id'] ?? null;
$action = $_POST['action'] ?? 'follow';

header('Content-Type: application/json');

if (!$targetId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Target user ID required']));
}

if ($userId == $targetId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Cannot follow yourself']));
}

$result = $userService->toggleFollow($userId, (int)$targetId, $action);

exit(json_encode(array_merge(['success' => true], $result)));
