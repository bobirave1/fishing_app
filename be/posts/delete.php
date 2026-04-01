<?php
/**
 * Post delete endpoint (legacy direct access).
 * Delegates to PostService via DI container.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

use App\Services\PostService;

/** @var \App\Core\Container $container */
$container = $GLOBALS['container'];

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

$postId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$postId) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Post ID required']));
}

$postService = $container->get(PostService::class);

if ($postService->delete($postId, $_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
    exit;
}

http_response_code(403);
header('Content-Type: application/json');
echo json_encode(['error' => 'You can only delete your own posts']);
exit;
