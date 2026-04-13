<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}

$postId = $_POST['id'] ?? null;
if (!$postId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Post ID required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit;
}

if ($post['user_id'] != $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'You can only edit your own posts']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$visibility = $_POST['visibility'] ?? 'public';

if (empty($title) || strlen($title) > 200) {
    echo json_encode(['success' => false, 'error' => 'Title must be between 1 and 200 characters']);
    exit;
}
if (empty($content) || strlen($content) > 5000) {
    echo json_encode(['success' => false, 'error' => 'Content must be between 1 and 5000 characters']);
    exit;
}
if (!in_array($visibility, ['public', 'friends', 'private'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid visibility']);
    exit;
}

// Handle removal of existing media
$removeMedia = array_filter(explode(',', $_POST['remove_media'] ?? ''));
if (!empty($removeMedia)) {
    foreach ($removeMedia as $mediaId) {
        $mediaId = (int)$mediaId;
        if ($mediaId > 0) {
            // Get file path before deleting
            $mediaStmt = $pdo->prepare("SELECT image_url FROM post_images WHERE id = ? AND post_id = ?");
            $mediaStmt->execute([$mediaId, $postId]);
            $mediaRow = $mediaStmt->fetch();
            if ($mediaRow && !empty($mediaRow['image_url']) && file_exists('../../' . $mediaRow['image_url'])) {
                unlink('../../' . $mediaRow['image_url']);
            }
            $pdo->prepare("DELETE FROM post_images WHERE id = ? AND post_id = ?")->execute([$mediaId, $postId]);
        } elseif ($mediaId === 0) {
            // Legacy single image in posts.image
            if (!empty($post['image']) && file_exists('../../' . $post['image'])) {
                unlink('../../' . $post['image']);
            }
            $post['image'] = null;
        }
    }
}

// Handle new file uploads
$uploadedMediaPaths = [];
if (isset($_FILES['media'])) {
    $isMultiple = is_array($_FILES['media']['name']);
    $mediaCount = $isMultiple ? count($_FILES['media']['name']) : 1;

    for ($i = 0; $i < $mediaCount; $i++) {
        $file = $isMultiple
            ? [
                'name' => $_FILES['media']['name'][$i] ?? '',
                'type' => $_FILES['media']['type'][$i] ?? '',
                'tmp_name' => $_FILES['media']['tmp_name'][$i] ?? '',
                'error' => $_FILES['media']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['media']['size'][$i] ?? 0,
            ]
            : $_FILES['media'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) continue;
        if ($file['size'] > 20 * 1024 * 1024) continue;

        $ext = strtolower(pathinfo((string)$file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('post_', true) . '.' . $ext;
        $target = '../../fe/assets/img/' . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            $uploadedMediaPaths[] = 'fe/assets/img/' . $filename;
        }
    }
}

// Insert new media into post_images
if (!empty($uploadedMediaPaths)) {
    try {
        $mediaStmt = $pdo->prepare("INSERT INTO post_images (post_id, image_url, uploaded_at) VALUES (?, ?, NOW())");
        foreach ($uploadedMediaPaths as $mediaPath) {
            $mediaStmt->execute([$postId, $mediaPath]);
        }
    } catch (Throwable $e) {}
}

// Update primary image field
$imagePath = $post['image'];
if (!empty($uploadedMediaPaths)) {
    $imagePath = $uploadedMediaPaths[0];
} elseif (!empty($removeMedia)) {
    // Check if there are remaining images in post_images
    $remainStmt = $pdo->prepare("SELECT image_url FROM post_images WHERE post_id = ? ORDER BY id LIMIT 1");
    $remainStmt->execute([$postId]);
    $remain = $remainStmt->fetch();
    $imagePath = $remain ? $remain['image_url'] : null;
}

// Update post
$stmt = $pdo->prepare("UPDATE posts SET title = ?, content = ?, image = ?, visibility = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$title, $content, $imagePath, $visibility, $postId]);

echo json_encode(['success' => true, 'message' => 'Post updated successfully']);
exit;
