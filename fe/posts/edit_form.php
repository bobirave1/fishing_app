<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
require '../../config/languages.php';
require '../../config/avatar_helper.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$postId = $_GET['id'] ?? null;
if (!$postId) {
    http_response_code(400);
    exit;
}

$stmt = $pdo->prepare("SELECT p.*, u.username, up.avatar_url FROM posts p JOIN users u ON u.id = p.user_id LEFT JOIN user_profiles up ON up.user_id = p.user_id WHERE p.id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post || $post['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    exit;
}

// Get existing media
$existingMedia = [];
try {
    $mediaStmt = $pdo->prepare("SELECT id, image_url FROM post_images WHERE post_id = ? ORDER BY id");
    $mediaStmt->execute([$postId]);
    $existingMedia = $mediaStmt->fetchAll();
} catch (Throwable $e) {}

if (empty($existingMedia) && !empty($post['image'])) {
    $existingMedia = [['id' => 0, 'image_url' => $post['image']]];
}

$avatar = getUserAvatar($post['avatar_url'] ?? null);

// Compute absolute base URL for images (this HTML is AJAX-loaded into different page contexts)
$baseUrl = dirname(dirname(dirname($_SERVER['SCRIPT_NAME'])));
if ($baseUrl !== '/') $baseUrl .= '/';
?>
<form id="editPostForm" enctype="multipart/form-data">
    <?= getCsrfField() ?>
    <input type="hidden" name="id" value="<?= htmlspecialchars($postId) ?>">

    <div class="composer-header">
        <img src="<?= $baseUrl . htmlspecialchars($avatar) ?>" alt="avatar" class="composer-avatar">
        <div class="composer-meta">
            <div class="composer-name"><?= htmlspecialchars($post['username']) ?></div>
            <select name="visibility" class="form-select form-select-sm composer-privacy">
                <option value="public" <?= $post['visibility'] === 'public' ? 'selected' : '' ?>>🌍 <?= __('public') ?></option>
                <option value="friends" <?= $post['visibility'] === 'friends' ? 'selected' : '' ?>>👥 <?= __('friends') ?></option>
                <option value="private" <?= $post['visibility'] === 'private' ? 'selected' : '' ?>>🔒 <?= __('private') ?></option>
            </select>
        </div>
    </div>

    <div class="composer-fields">
        <input type="text" name="title" class="create-post-input mb-2"
               placeholder="<?= __('post_title_placeholder') ?>"
               value="<?= htmlspecialchars($post['title']) ?>"
               required maxlength="200">
        <textarea name="content" class="create-post-input composer-content-input"
                  placeholder="<?= __('post_placeholder') ?>"
                  required maxlength="5000"><?= htmlspecialchars($post['content']) ?></textarea>
    </div>

    <?php if (!empty($existingMedia)): ?>
    <div id="editExistingMedia" class="post-media-preview show">
        <?php foreach ($existingMedia as $media): ?>
            <div class="media-preview-item" data-media-id="<?= (int)$media['id'] ?>">
                <button type="button" class="media-remove-btn" data-remove-id="<?= (int)$media['id'] ?>" title="<?= __('remove') ?>">&times;</button>
                <img src="<?= $baseUrl . htmlspecialchars($media['image_url']) ?>" alt="">
            </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="remove_media" id="editRemoveMedia" value="">
    <?php endif; ?>

    <input type="file" id="editMediaInput" name="media[]" class="create-post-file-input"
           multiple accept="image/jpeg,image/png,image/gif,image/webp">

    <div id="editMediaPreview" class="post-media-preview" aria-live="polite"></div>

    <label for="editMediaInput" class="composer-icon-btn mt-1" title="<?= __('attach_file') ?>">
        <i class="fas fa-images"></i>
    </label>
</form>
