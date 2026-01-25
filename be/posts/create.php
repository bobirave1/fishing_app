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
    exit('Title must be between 1 and 200 characters');
}
if (empty($content) || strlen($content) > 5000) {
    exit('Content must be between 1 and 5000 characters');
}
if (!in_array($visibility, ['public', 'friends', 'private'])) {
    exit('Invalid visibility setting');
}

// Handle file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $validationErrors = validateImageUpload($_FILES['image']);
    if (!empty($validationErrors)) {
        exit(implode(', ', $validationErrors));
    }
    
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $filename = uniqid() . '.' . $ext;
    $target = '../../fe/assets/img/' . $filename;
    
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        exit('Failed to upload image');
    }
    $imagePath = 'fe/assets/img/' . $filename;
}

// Insert post
$stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, visibility, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], $title, $content, $imagePath, $visibility]);
$postId = $pdo->lastInsertId();

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
