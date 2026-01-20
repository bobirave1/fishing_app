<?php
session_start();
require '../../config/database.php';

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
$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    exit;
}

// Return just the form content (to be loaded into modal)
?>
<form id="editPostForm" action="be/posts/edit.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= htmlspecialchars($postId) ?>">
    
    <div class="mb-3">
        <label for="editTitle" class="form-label"><i class="fas fa-heading"></i> Title</label>
        <input type="text" id="editTitle" name="title" class="form-control" 
               value="<?= htmlspecialchars($post['title']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="editContent" class="form-label"><i class="fas fa-pen"></i> Content</label>
        <textarea id="editContent" name="content" class="form-control" rows="5" required><?= htmlspecialchars($post['content']) ?></textarea>
    </div>

    <div class="mb-3">
        <label for="editVisibility" class="form-label"><i class="fas fa-eye"></i> Visibility</label>
        <select id="editVisibility" name="visibility" class="form-select">
            <option value="public" <?= $post['visibility'] === 'public' ? 'selected' : '' ?>>🌍 Public</option>
            <option value="friends" <?= $post['visibility'] === 'friends' ? 'selected' : '' ?>>👥 Friends</option>
            <option value="private" <?= $post['visibility'] === 'private' ? 'selected' : '' ?>>🔒 Only me</option>
        </select>
    </div>

    <?php if (!empty($post['image'])): ?>
        <div class="mb-3">
            <label class="form-label"><i class="fas fa-image"></i> Current Image</label>
            <img src="<?= htmlspecialchars($post['image']) ?>" class="img-fluid rounded" style="max-height: 150px;">
            <small class="d-block text-muted mt-2">Upload a new image to replace it</small>
        </div>
    <?php endif; ?>

    <div class="mb-3">
        <label for="editImage" class="form-label"><i class="fas fa-upload"></i> Change Image (optional)</label>
        <input type="file" id="editImage" name="image" class="form-control">
    </div>
</form>
