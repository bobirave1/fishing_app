<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$targetId = $_POST['target_id'] ?? null;
$action = $_POST['action'] ?? 'follow'; // follow or unfollow

header('Content-Type: application/json');

if (!$targetId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Target user ID required']));
}

if ($userId == $targetId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Cannot follow yourself']));
}

// Check if target user exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
$stmt->execute([$targetId]);

if (!$stmt->fetch()) {
    http_response_code(404);
    exit(json_encode(['error' => 'User not found']));
}

if ($action === 'follow') {
    // Check if already following
    $stmt = $pdo->prepare("SELECT id FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$userId, $targetId]);
    
    if ($stmt->fetch()) {
        exit(json_encode(['success' => true, 'message' => 'Already following']));
    }
    
    // Add follow
    $stmt = $pdo->prepare("INSERT INTO follows (follower_id, following_id) VALUES (?, ?)");
    $stmt->execute([$userId, $targetId]);
    
    // Create notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, sender_id) 
        VALUES (?, 'follow', ?)
    ");
    $stmt->execute([$targetId, $userId]);
    
    // Get target user name for activity
    $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch();
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_feed (user_id, action_type, related_id, description, created_at)
        VALUES (?, 'follow', ?, ?, NOW())
    ");
    $stmt->execute([$userId, $targetId, 'started following ' . $targetUser['username']]);
    
} else if ($action === 'unfollow') {
    // Remove follow
    $stmt = $pdo->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
    $stmt->execute([$userId, $targetId]);
}

// Get updated follow counts
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE following_id = ?");
$stmt->execute([$targetId]);
$followers = $stmt->fetch()['count'];

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM follows WHERE follower_id = ?");
$stmt->execute([$targetId]);
$following = $stmt->fetch()['count'];

// Check if user is following target
$stmt = $pdo->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
$stmt->execute([$userId, $targetId]);
$isFollowing = $stmt->fetch() ? true : false;

exit(json_encode([
    'success' => true,
    'followers' => $followers,
    'following' => $following,
    'is_following' => $isFollowing
]));
