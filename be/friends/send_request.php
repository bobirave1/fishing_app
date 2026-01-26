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

// Check if already friends
$friendCheck = $pdo->prepare(
    'SELECT 1 FROM friends 
     WHERE (user_id = ? AND friend_id = ?) 
     OR (user_id = ? AND friend_id = ?)'
);
$friendCheck->execute([$sender, $receiver, $receiver, $sender]);
if ($friendCheck->fetch()) {
    http_response_code(409);
    exit('Already friends.');
}

// Check if pending request exists
$check = $pdo->prepare(
    'SELECT 1 FROM friend_requests 
     WHERE ((sender_id = ? AND receiver_id = ?) 
     OR (sender_id = ? AND receiver_id = ?))
     AND status = "pending"'
);
$check->execute([$sender, $receiver, $receiver, $sender]);
if ($check->fetch()) {
    http_response_code(409);
    exit('Friend request already sent.');
}

$stmt = $pdo->prepare(
    "INSERT INTO friend_requests (sender_id, receiver_id)
     VALUES (?, ?)"
);

try {
    $stmt->execute([$sender, $receiver]);
    
    // Create notification for friend request
    $notifStmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, from_user_id, related_id, created_at)
         VALUES (?, 'friend_request', ?, ?, NOW())"
    );
    $notifStmt->execute([$receiver, $sender, $sender]);
    
    header("Location: ../users/profile.php?id=$receiver");
} catch (PDOException $e) {
    die("Request already sent.");
}
