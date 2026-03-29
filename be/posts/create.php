<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) exit;

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$visibility = $_POST['visibility'] ?? 'public';
$imagePath = null;
$uploadedMediaPaths = [];

// Input validation
if (empty($title) || strlen($title) > 200) {
    $_SESSION['post_error'] = 'Title must be between 1 and 200 characters';
    header('Location: ../../index.php');
    exit;
}
if (empty($content) || strlen($content) > 5000) {
    $_SESSION['post_error'] = 'Content must be between 1 and 5000 characters';
    header('Location: ../../index.php');
    exit;
}
if (!in_array($visibility, ['public', 'friends', 'private'])) {
    $_SESSION['post_error'] = 'Invalid visibility setting';
    header('Location: ../../index.php');
    exit;
}

// Handle file upload (single or multiple media)
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

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $validationErrors = validateMediaUpload($file);
        if (!empty($validationErrors)) {
            $_SESSION['post_error'] = implode(', ', $validationErrors);
            header('Location: ../../index.php');
            exit;
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $filename = uniqid('post_', true) . '.' . $ext;
        $target = '../../fe/assets/img/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            $_SESSION['post_error'] = 'Failed to upload file';
            header('Location: ../../index.php');
            exit;
        }

        $uploadedMediaPaths[] = 'fe/assets/img/' . $filename;
    }
}

if (!empty($uploadedMediaPaths)) {
    $imagePath = $uploadedMediaPaths[0];
}

// Insert post
$stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, visibility, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], $title, $content, $imagePath, $visibility]);
$postId = $pdo->lastInsertId();

if (!empty($uploadedMediaPaths)) {
    try {
        $mediaStmt = $pdo->prepare("INSERT INTO post_images (post_id, image_url, uploaded_at) VALUES (?, ?, NOW())");
        foreach ($uploadedMediaPaths as $mediaPath) {
            $mediaStmt->execute([$postId, $mediaPath]);
        }
    } catch (Throwable $e) {
        // If post_images table is missing in a local setup, keep post creation successful with primary media in posts.image.
    }
}

// Notify friends about new post
if ($visibility !== 'private') {
    // Get all friends
    $friendsStmt = $pdo->prepare("
        SELECT DISTINCT 
            CASE 
                WHEN user_id = ? THEN friend_id
                WHEN friend_id = ? THEN user_id
            END as friend_id
        FROM friends 
        WHERE user_id = ? OR friend_id = ?
    ");
    $friendsStmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
    $friends = $friendsStmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Create notification for each friend
    if (!empty($friends)) {
        $notifStmt = $pdo->prepare(
            "INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at)
             VALUES (?, 'new_post', ?, ?, NOW())"
        );
        foreach ($friends as $friendId) {
            $notifStmt->execute([$friendId, $_SESSION['user_id'], $postId]);
        }
    }
}

// Log activity (optional - table may not exist)
// Uncomment if you have activity_feed table
/*
$stmt = $pdo->prepare("
    INSERT INTO activity_feed (user_id, action_type, post_id, description, created_at)
    VALUES (?, 'post', ?, ?, NOW())
");
$stmt->execute([$_SESSION['user_id'], $postId, $title]);
*/

header('Location: ../../index.php');
exit;
