<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

// Check if avatar file was uploaded
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'No image provided']));
}

// Validate file upload
$validationErrors = validateImageUpload($_FILES['avatar']);
if (!empty($validationErrors)) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => implode(', ', $validationErrors)]));
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
