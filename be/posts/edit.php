<?php
/**
 * Post edit endpoint (legacy direct access).
 * Delegates to PostService via DI container.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

use App\Services\PostService;

/** @var \App\Core\Container $container */
$container = $GLOBALS['container'];

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$userId = $_SESSION['user_id'];
$postId = (int) ($_POST['id'] ?? 0);
if (!$postId) {
    http_response_code(400);
    exit('Post ID required');
}

$postService = $container->get(PostService::class);
$post = $postService->findById($postId);

if (!$post) {
    http_response_code(404);
    exit('Post not found');
}
if ((int) $post['user_id'] !== $userId) {
    http_response_code(403);
    exit('You can only edit your own posts');
}

$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$visibility = $_POST['visibility'] ?? 'public';
$imagePath = $post['image'];

// Handle new file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $result = secureUploadFile($_FILES['image'], '../../fe/assets/img', 'media');
    if ($result['success']) {
        if (!empty($post['image']) && file_exists('../../' . $post['image'])) {
            unlink('../../' . $post['image']);
        }
        $imagePath = 'fe/assets/img/' . $result['filename'];
    }
}

$postService->update($postId, $title, $content, $visibility, $imagePath);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
exit;
