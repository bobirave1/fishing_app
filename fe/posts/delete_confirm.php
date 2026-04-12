<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
require '../../config/languages.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$postId = $_GET['id'] ?? null;

if (!$postId) {
    http_response_code(400);
    exit;
}

// Fetch the post
$stmt = $pdo->prepare("SELECT p.*, u.username FROM posts p JOIN users u ON u.id = p.user_id WHERE p.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    exit;
}

// Return confirmation content
?>
<div class="text-center">
    <div class="mb-3">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px; background: rgba(239, 68, 68, 0.1);">
            <i class="fas fa-trash-alt fa-2x text-danger"></i>
        </div>
    </div>
    <h5 class="fw-bold mb-2"><?= __('delete_post_confirm') ?></h5>
    <p class="text-muted mb-3"><?= __('delete_post_warning') ?></p>
    
    <div class="alert alert-light border border-danger-subtle rounded-3" style="margin-bottom: 1.5rem;">
        <strong class="text-dark"><?= htmlspecialchars($post['title']) ?></strong>
        <p class="mb-0 text-muted small mt-1"><?= __('post_by') ?> <?= htmlspecialchars($post['username']) ?></p>
    </div>

    <form id="deletePostForm" action="be/posts/delete.php" method="post">
        <?= getCsrfField() ?>
        <input type="hidden" name="id" value="<?= htmlspecialchars($postId) ?>">
    </form>
</div>
