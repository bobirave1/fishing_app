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

// Handle file upload (image or video)
if (isset($_FILES['media']) && $_FILES['media']['error'] === 0) {
    $validationErrors = validateMediaUpload($_FILES['media']);
    if (!empty($validationErrors)) {
        $_SESSION['post_error'] = implode(', ', $validationErrors);
        header('Location: ../../index.php');
        exit;
    }
    
    $ext = strtolower(pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $ext;
    $target = '../../fe/assets/img/' . $filename;
    
    if (!move_uploaded_file($_FILES['media']['tmp_name'], $target)) {
        $_SESSION['post_error'] = 'Failed to upload file';
        header('Location: ../../index.php');
        exit;
    }
    $imagePath = 'fe/assets/img/' . $filename;
}

// Insert post
$stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, visibility, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], $title, $content, $imagePath, $visibility]);
$postId = $pdo->lastInsertId();

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
