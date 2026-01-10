<?php
session_start();
require '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$visibility = $_POST['visibility'] ?? 'public';

if ($title === '' || $content === '') {
    die("Missing fields");
}

$imagePath = null;

if (!empty($_FILES['image']['name'])) {
    $dir = '../uploads/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);

    $filename = time() . '_' . basename($_FILES['image']['name']);
    move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename);
    $imagePath = 'uploads/' . $filename;
}

$stmt = $pdo->prepare("
    INSERT INTO posts (user_id, title, content, image, visibility)
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    $_SESSION['user_id'],
    $title,
    $content,
    $imagePath,
    $visibility
]);

header("Location: ../posts/feed.php");
exit;
