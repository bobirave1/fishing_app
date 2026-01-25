<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$userId = $_SESSION['user_id'];
$requestId = (int)$_POST['request_id'];

$stmt = $pdo->prepare(
    "SELECT sender_id FROM friend_requests
     WHERE id = ? AND receiver_id = ? AND status = 'pending'"
);
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch();

if (!$request) {
    die("Invalid request");
}

$senderId = $request['sender_id'];

$pdo->beginTransaction();

$pdo->prepare(
    "UPDATE friend_requests SET status = 'accepted' WHERE id = ?"
)->execute([$requestId]);

$pdo->prepare(
    "INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)"
)->execute([$userId, $senderId, $senderId, $userId]);

$pdo->commit();

header("Location: ../../fe/friends/list_requests.php");
exit;
