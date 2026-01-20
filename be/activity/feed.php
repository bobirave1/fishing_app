<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'get_feed';
$limit = $_GET['limit'] ?? 20;
$offset = $_GET['offset'] ?? 0;

header('Content-Type: application/json');

if ($action === 'get_feed') {
    // Get activity of people the user follows
    $stmt = $pdo->prepare("
        SELECT 
            af.id, af.user_id, af.action_type, af.description, 
            af.related_id, af.post_id, af.created_at,
            u.username, up.avatar_url,
            p.title as post_title
        FROM activity_feed af
        JOIN users u ON af.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN posts p ON af.post_id = p.id
        WHERE af.user_id IN (
            SELECT following_id FROM follows WHERE follower_id = ?
        ) OR af.user_id = ?
        ORDER BY af.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$userId, $userId, $limit, $offset]);
    $activities = $stmt->fetchAll();
    
    exit(json_encode([
        'success' => true,
        'activities' => $activities
    ]));
    
} else if ($action === 'add_activity') {
    // Add activity (called internally after post, comment, like, etc.)
    $postId = $_POST['post_id'] ?? null;
    $actionType = $_POST['action_type'] ?? null;
    $description = $_POST['description'] ?? null;
    
    if (!$actionType) {
        http_response_code(400);
        exit(json_encode(['error' => 'Action type required']));
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO activity_feed (user_id, action_type, related_id, post_id, description, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $actionType, $userId, $postId, $description]);
    
    exit(json_encode(['success' => true]));
    
} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid action']));
}
