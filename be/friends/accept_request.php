<?php
/**
 * Accept friend request — delegates to FriendService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$container = $GLOBALS['container'];
$friendService = $container->get(App\Services\FriendService::class);
$logger = $container->get(App\Core\Logger::class);

$userId = $_SESSION['user_id'];
$requestId = (int)$_POST['request_id'];

if (!$friendService->acceptRequest($requestId, $userId)) {
    die("Invalid request");
}

$logger->info("Friend request {$requestId} accepted by user {$userId}");

header("Location: list_requests.php");
exit;
