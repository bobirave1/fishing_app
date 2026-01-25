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

$sender = $_SESSION['user_id'];
$receiver = (int)$_POST['receiver_id'];

if ($sender === $receiver) {
    http_response_code(400);
    exit('You cannot add yourself.');
}

// Check if already friends or request exists
$check = $pdo->prepare(
    'SELECT 1 FROM friend_requests 
     WHERE (sender_id = ? AND receiver_id = ?) 
     OR (sender_id = ? AND receiver_id = ?)'
);
$check->execute([$sender, $receiver, $receiver, $sender]);
if ($check->fetch()) {
    http_response_code(409);
    exit('Friend request already exists.');
}

$stmt = $pdo->prepare(
    "INSERT INTO friend_requests (sender_id, receiver_id)
     VALUES (?, ?)"
);

try {
    $stmt->execute([$sender, $receiver]);
    header("Location: ../../profile.php?id=$receiver");
} catch (PDOException $e) {
    die("Request already sent.");
}
