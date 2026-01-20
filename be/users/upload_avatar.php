<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];

// Check if avatar file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'No image provided']));
}

// Validate file size (max 5MB)
if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'File is too large (max 5MB)']));
}

// Validate file type
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($_FILES['avatar']['type'], $allowed)) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid image format']));
}

// Get current avatar to delete old one
$stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

if ($profile && !empty($profile['avatar_url']) && file_exists('../../' . $profile['avatar_url'])) {
    unlink('../../' . $profile['avatar_url']);
}

// Generate unique filename
$ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $userId . '_' . uniqid() . '.' . $ext;
$target = '../../fe/assets/img/avatars/' . $filename;

// Create avatars directory if it doesn't exist
if (!is_dir('../../fe/assets/img/avatars')) {
    mkdir('../../fe/assets/img/avatars', 0755, true);
}

// Upload file
if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Failed to upload avatar']));
}

$avatarPath = 'fe/assets/img/avatars/' . $filename;

// Check if user profile exists
$stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$exists = $stmt->fetch();

if ($exists) {
    // Update existing profile
    $stmt = $pdo->prepare("UPDATE user_profiles SET avatar_url = ? WHERE user_id = ?");
} else {
    // Create new profile
    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, avatar_url) VALUES (?, ?)");
}

$stmt->execute([$avatarPath, $userId]);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'avatar_url' => $avatarPath,
    'message' => 'Avatar updated successfully'
]);
exit;
