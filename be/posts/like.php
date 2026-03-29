<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;
$action = $_POST['action'] ?? 'like'; // like or unlike

if (!$postId) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Post ID required']));
}

// Check if post exists
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Post not found']));
}

if ($action === 'like') {
    // Check if already liked
    $stmt = $pdo->prepare("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
    
    if (!$stmt->fetch()) {
        // Add like only if not already liked
        $stmt = $pdo->prepare("INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $userId]);
        
        // Create notification
        if ($post['user_id'] != $userId) {
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, related_id, from_user_id) 
                    VALUES (?, 'like', ?, ?)
                ");
            $stmt->execute([$post['user_id'], $postId, $userId]);
        }
    }
    
} else if ($action === 'unlike') {
    // Remove like
    $stmt = $pdo->prepare("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $userId]);
}

// Get updated like count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?");
$stmt->execute([$postId]);
$likeCount = $stmt->fetch()['count'];

// Check if user liked it
$stmt = $pdo->prepare("SELECT 1 FROM post_likes WHERE post_id = ? AND user_id = ?");
$stmt->execute([$postId, $userId]);
$liked = $stmt->fetch() ? true : false;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'like_count' => $likeCount,
    'liked' => $liked
]);
