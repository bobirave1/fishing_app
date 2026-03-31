<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require '../../config/languages.php';

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

$currentUserId = (int) $_SESSION['user_id'];
$friendId = (int) ($_POST['friend_id'] ?? 0);
$returnTo = $_POST['return_to'] ?? 'friends_list';

if ($friendId <= 0 || $friendId === $currentUserId) {
    $_SESSION['friend_flash_error'] = __('friend_not_found');
    header('Location: list_friends.php?user_id=' . $currentUserId);
    exit;
}

$deleteStmt = $pdo->prepare(
    'DELETE FROM friends
     WHERE (user_id = ? AND friend_id = ?)
        OR (user_id = ? AND friend_id = ?)'
);

$deleteStmt->execute([$currentUserId, $friendId, $friendId, $currentUserId]);

if ($deleteStmt->rowCount() > 0) {
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
