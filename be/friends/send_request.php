<?php
/**
 * Send friend request — delegates to FriendService.
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

$sender = $_SESSION['user_id'];
$receiver = (int)$_POST['receiver_id'];

if ($sender === $receiver) {
    http_response_code(400);
    exit('You cannot add yourself.');
}

try {
    $friendService->sendRequest($sender, $receiver);
    $logger->info("Friend request sent from {$sender} to {$receiver}");
    header("Location: ../users/profile.php?id=$receiver");
} catch (\Throwable $e) {
    $logger->warning("Friend request failed: " . $e->getMessage());
    http_response_code(409);
    exit($e->getMessage());
}
