<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
require '../../config/avatar_helper.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$action = $_POST['action'] ?? 'add'; // add or get

// CSRF Protection for write operations only (not for 'get' action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'get') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        header('Content-Type: application/json');
        exit(json_encode(['error' => 'Invalid CSRF token']));
    }
}

header('Content-Type: application/json');

if (!$postId) {
    http_response_code(400);
    exit(json_encode(['error' => 'Post ID required']));
}

// Check if post exists
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    exit(json_encode(['error' => 'Post not found']));
}

if ($action === 'add') {
    if (empty($content)) {
        http_response_code(400);
        exit(json_encode(['error' => 'Comment content required']));
    }
    
    if (strlen($content) > 1000) {
        http_response_code(400);
        exit(json_encode(['error' => 'Comment too long (max 1000 characters)']));
    }
    
    // Add comment
    $stmt = $pdo->prepare("
        INSERT INTO post_comments (post_id, user_id, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$postId, $userId, $content]);
    $commentId = $pdo->lastInsertId();
    
    // Create notification
    if ($post['user_id'] != $userId) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, related_id, sender_id) 
            VALUES (?, 'comment', ?, ?)
        ");
        $stmt->execute([$post['user_id'], $postId, $userId]);
        
        // Log activity (optional - table may not exist)
        /*
        $stmt = $pdo->prepare("
            INSERT INTO activity_feed (user_id, action_type, post_id, created_at)
            VALUES (?, 'comment', ?, NOW())
        ");
        $stmt->execute([$userId, $postId]);
        */
    }
    
    // Get user info
    $stmt = $pdo->prepare("
        SELECT u.username, up.avatar_url 
        FROM users u 
        LEFT JOIN user_profiles up ON u.id = up.user_id 
        WHERE u.id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    exit(json_encode([
        'success' => true,
        'comment_id' => $commentId,
        'username' => $user['username'],
        'avatar' => $user['avatar_url'] ?? getDefaultAvatarPath(),
        'content' => htmlspecialchars($content),
        'created_at' => date('M d, Y H:i')
    ]));
    
} else if ($action === 'get') {
    // Get all comments for post
    $stmt = $pdo->prepare("
        SELECT 
            pc.id, pc.user_id, pc.content, pc.created_at,
            u.username, up.avatar_url
        FROM post_comments pc
        JOIN users u ON pc.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE pc.post_id = ?
        ORDER BY pc.created_at DESC
    ");
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll();
    
    exit(json_encode([
        'success' => true,
        'comments' => $comments,
        'count' => count($comments)
    ]));
    
} else if ($action === 'delete') {
    $commentId = $_POST['comment_id'] ?? null;
    
    if (!$commentId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Comment ID required']));
    }
    
    // Check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM post_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment || $comment['user_id'] != $userId) {
        http_response_code(403);
        exit(json_encode(['error' => 'Cannot delete this comment']));
    }
    
    // Delete comment
    $stmt = $pdo->prepare("DELETE FROM post_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    exit(json_encode(['success' => true]));
}

http_response_code(400);
exit(json_encode(['error' => 'Invalid action']));
