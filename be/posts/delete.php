<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

// Get post ID from request
$postId = $_GET['id'] ?? $_POST['id'] ?? null;

if (!$postId) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Post ID required']));
}

// Fetch the post to verify ownership
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Post not found']));
}

// Check if the user is the owner
if ($post['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'You can only delete your own posts']));
}

// Delete the image if it exists
if (!empty($post['image']) && file_exists('../../' . $post['image'])) {
    unlink('../../' . $post['image']);
}

// Delete the post
$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$postId]);

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Post deleted successfully']);
exit;
?>
