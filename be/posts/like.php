<?php
/**
 * Post like/unlike endpoint (legacy direct access).
 * Delegates to PostService via DI container.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

use App\Services\PostService;

/** @var \App\Core\Container $container */
$container = $GLOBALS['container'];

$requestId = bin2hex(random_bytes(8));

if (!isset($_SESSION['user_id'])) {
    jsonError('Unauthorized', $requestId, 401);
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    jsonError('Invalid CSRF token', $requestId, 403);
}

$userId = $_SESSION['user_id'];
$postId = (int) ($_POST['post_id'] ?? 0);

if (!$postId) {
    jsonError('Post ID required', $requestId, 400);
}

$postService = $container->get(PostService::class);

if (!$postService->findById($postId)) {
    jsonError('Post not found', $requestId, 404);
}

$result = $postService->toggleLike($postId, $userId);

jsonResponse([
    'success' => true,
    'like_count' => $result['like_count'],
    'liked' => $result['liked'],
    'request_id' => $requestId,
]);
