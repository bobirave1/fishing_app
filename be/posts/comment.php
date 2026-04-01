<?php
/**
 * Comment endpoint — delegates to PostService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

$requestId = bin2hex(random_bytes(8));

if (!isset($_SESSION['user_id'])) {
    jsonError('Unauthorized', $requestId, 401);
}

$container = $GLOBALS['container'];
$postService = $container->get(App\Services\PostService::class);
$logger = $container->get(App\Core\Logger::class);

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? null;
$content = trim($_POST['content'] ?? '');
$action = $_POST['action'] ?? 'add';

// CSRF Protection for write operations only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action !== 'get') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        jsonError('Invalid CSRF token', $requestId, 403);
    }
}

if (!$postId) {
    jsonError('Post ID required', $requestId, 400);
}

$post = $postService->findById((int)$postId);
if (!$post) {
    jsonError('Post not found', $requestId, 404);
}

if ($action === 'add') {
    if (!checkRateLimit('comment_' . $userId, 20, 120)) {
        $logger->warning("Comment rate limit hit for user {$userId}");
        jsonError('Too many comments. Please wait.', $requestId, 429);
    }

    if (empty($content)) {
        jsonError('Comment content required', $requestId, 400);
    }
    if (strlen($content) > 1000) {
        jsonError('Comment too long (max 1000 characters)', $requestId, 400);
    }

    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    $result = $postService->addComment((int)$postId, $userId, $content, $parentId);

    jsonResponse([
        'success' => true,
        'comment_id' => $result['comment_id'],
        'username' => $result['username'],
        'avatar' => $result['avatar'],
        'content' => htmlspecialchars($content),
        'created_at' => $result['created_at'],
        'request_id' => $requestId,
    ]);

} else if ($action === 'get') {
    $comments = $postService->getComments((int)$postId, $userId);

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

    if (!$postService->deleteComment((int)$commentId, $userId)) {
        jsonError('Cannot delete this comment', $requestId, 403);
    }

    jsonResponse([
        'success' => true,
        'request_id' => $requestId,
    ]);

} else if ($action === 'like_comment') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    if (!$commentId) {
        jsonError('Comment ID required', $requestId, 400);
    }

    $result = $postService->toggleCommentLike($commentId, $userId);

    jsonResponse([
        'success' => true,
        'liked' => $result['liked'],
        'like_count' => $result['like_count'],
        'request_id' => $requestId,
    ]);
}

jsonError('Invalid action', $requestId, 400);
