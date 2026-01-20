<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) exit;

$title = $_POST['title'] ?? '';
$content = $_POST['content'] ?? '';
$visibility = $_POST['visibility'] ?? 'public';
$imagePath = null;

// Handle file upload
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
    $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $target = '../../fe/assets/img/' . $filename;
    move_uploaded_file($_FILES['image']['tmp_name'], $target);
    $imagePath = 'fe/assets/img/' . $filename;
}

// Insert post
$stmt = $pdo->prepare("INSERT INTO posts (user_id, title, content, image, visibility, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->execute([$_SESSION['user_id'], $title, $content, $imagePath, $visibility]);
$postId = $pdo->lastInsertId();

// Log activity
$stmt = $pdo->prepare("
    INSERT INTO activity_feed (user_id, action_type, post_id, description, created_at)
    VALUES (?, 'post', ?, ?, NOW())
");
$stmt->execute([$_SESSION['user_id'], $postId, $title]);

header('Location: ../../index.php');
exit;
