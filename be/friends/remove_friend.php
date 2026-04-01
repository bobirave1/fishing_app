<?php
/**
 * Remove friend — delegates to FriendService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: list_friends.php?user_id=' . (int) $_SESSION['user_id']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $_SESSION['friend_flash_error'] = __('invalid_csrf_token');
    header('Location: list_friends.php?user_id=' . (int) $_SESSION['user_id']);
    exit;
}

$container = $GLOBALS['container'];
$friendService = $container->get(App\Services\FriendService::class);

$currentUserId = (int) $_SESSION['user_id'];
$friendId = (int) ($_POST['friend_id'] ?? 0);
$returnTo = $_POST['return_to'] ?? 'friends_list';

if ($friendId <= 0 || $friendId === $currentUserId) {
    $_SESSION['friend_flash_error'] = __('friend_not_found');
    header('Location: list_friends.php?user_id=' . $currentUserId);
    exit;
}

if ($friendService->removeFriend($currentUserId, $friendId)) {
    $_SESSION['friend_flash_success'] = __('friend_removed_success');
} else {
    $_SESSION['friend_flash_error'] = __('friend_not_found');
}

if ($returnTo === 'profile') {
    header('Location: ../users/profile.php?id=' . $friendId);
    exit;
}

header('Location: list_friends.php?user_id=' . $currentUserId);
exit;
