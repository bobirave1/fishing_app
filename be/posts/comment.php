<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
require '../../config/avatar_helper.php';
setSecurityHeaders();

$requestId = bin2hex(random_bytes(8));

function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function jsonError(string $message, string $requestId, int $status): void {
    jsonResponse([
        'success' => false,
        'error' => $message,
        'request_id' => $requestId,
    ], $status);
}

if (!isset($_SESSION['user_id'])) {
    jsonError('Unauthorized', $requestId, 401);
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$action = $_POST['action'] ?? 'add'; // add or get

// CSRF Protection for write operations only (not for 'get' action)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'get') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        jsonError('Invalid CSRF token', $requestId, 403);
    }
}

if (!$postId) {
    jsonError('Post ID required', $requestId, 400);
}

// Check if post exists
$stmt = $pdo->prepare("SELECT user_id FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    jsonError('Post not found', $requestId, 404);
}

if ($action === 'add') {
    if (empty($content)) {
        jsonError('Comment content required', $requestId, 400);
    }
    
    if (strlen($content) > 1000) {
        jsonError('Comment too long (max 1000 characters)', $requestId, 400);
    }
    
    // Validate parent_id if replying
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    if ($parentId) {
        $stmt = $pdo->prepare("SELECT id, user_id FROM post_comments WHERE id = ? AND post_id = ?");
        $stmt->execute([$parentId, $postId]);
        if (!$stmt->fetch()) {
            jsonError('Parent comment not found', $requestId, 404);
        }
    }
    
    // Add comment
    $stmt = $pdo->prepare("
        INSERT INTO post_comments (post_id, user_id, content, parent_id, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$postId, $userId, $content, $parentId]);
    $commentId = $pdo->lastInsertId();
    
    // Create notification
    if ($post['user_id'] != $userId) {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, type, related_id, from_user_id) 
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
    
    jsonResponse([
        'success' => true,
        'comment_id' => $commentId,
        'username' => $user['username'],
        'avatar' => $user['avatar_url'] ?? getDefaultAvatarPath(),
        'content' => htmlspecialchars($content),
        'created_at' => date('c'),
        'request_id' => $requestId,
    ]);
    
} else if ($action === 'get') {
    // Get all comments for post with like counts and parent info
    $stmt = $pdo->prepare("
        SELECT 
            pc.id, pc.user_id, pc.content, pc.created_at, pc.parent_id,
            u.username, up.avatar_url,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = pc.id) AS like_count,
            (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = pc.id AND cl.user_id = ?) AS user_liked,
            pu.username AS parent_username
        FROM post_comments pc
        JOIN users u ON pc.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN post_comments pp ON pc.parent_id = pp.id
        LEFT JOIN users pu ON pp.user_id = pu.id
        WHERE pc.post_id = ?
        ORDER BY pc.created_at ASC
    ");
    $stmt->execute([$userId, $postId]);
    $comments = $stmt->fetchAll();
    
    // Ensure consistent ISO timestamps and default avatars
    $defaultAvatar = getDefaultAvatarPath();
    foreach ($comments as &$c) {
        if (!empty($c['created_at'])) {
            $c['created_at'] = date('c', strtotime($c['created_at']));
        }
        $c['like_count'] = (int)$c['like_count'];
        $c['user_liked'] = (int)$c['user_liked'];
        if (empty($c['avatar_url']) || !is_file(__DIR__ . '/../../' . $c['avatar_url'])) {
            $c['avatar_url'] = $defaultAvatar;
        }
    }
    
    jsonResponse([
        'success' => true,
        'comments' => $comments,
        'count' => count($comments),
        'request_id' => $requestId,
    ]);
    
} else if ($action === 'delete') {
    $commentId = $_POST['comment_id'] ?? null;
    
    if (!$commentId) {
        jsonError('Comment ID required', $requestId, 400);
    }
    
    // Check ownership
    $stmt = $pdo->prepare("SELECT user_id FROM post_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch();
    
    if (!$comment || $comment['user_id'] != $userId) {
        jsonError('Cannot delete this comment', $requestId, 403);
    }
    
    // Delete comment
    $stmt = $pdo->prepare("DELETE FROM post_comments WHERE id = ?");
    $stmt->execute([$commentId]);
    
    jsonResponse([
        'success' => true,
        'request_id' => $requestId,
    ]);
} else if ($action === 'like_comment') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if (!$commentId) {
        jsonError('Comment ID required', $requestId, 400);
    }
    
    // Check comment exists and belongs to this post
    $stmt = $pdo->prepare("SELECT id FROM post_comments WHERE id = ? AND post_id = ?");
    $stmt->execute([$commentId, $postId]);
    if (!$stmt->fetch()) {
        jsonError('Comment not found', $requestId, 404);
    }
    
    // Toggle like
    $stmt = $pdo->prepare("SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?");
    $stmt->execute([$commentId, $userId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $pdo->prepare("DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?")->execute([$commentId, $userId]);
        $liked = false;
    } else {
        $pdo->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)")->execute([$commentId, $userId]);
        $liked = true;
    }
    
    // Get updated count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?");
    $stmt->execute([$commentId]);
    $likeCount = (int)$stmt->fetchColumn();
    
    jsonResponse([
        'success' => true,
        'liked' => $liked,
        'like_count' => $likeCount,
        'request_id' => $requestId,
    ]);
}

jsonError('Invalid action', $requestId, 400);
