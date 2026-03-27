<?php
session_start();
require '../../config/database.php';
require '../../config/security.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$userId = $_SESSION['user_id'];

// Get form data
$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$location = trim($_POST['location'] ?? '');
$experience_level = $_POST['experience_level'] ?? 'beginner';

// Validate input
if (empty($full_name) || empty($username)) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Full name and username are required']));
}

// Validate bio length
if (strlen($bio) > 500) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Bio is too long (max 500 characters)']));
}

// Update user basic info
$stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ? WHERE id = ?");
$stmt->execute([$full_name, $username, $userId]);

// Check if user profile exists
$stmt = $pdo->prepare("SELECT user_id FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profileExists = $stmt->fetch();

// Handle avatar upload
$avatarPath = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $validationErrors = validateImageUpload($_FILES['avatar']);
    if (!empty($validationErrors)) {
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['error' => implode(', ', $validationErrors)]));
    }

    // Get current avatar to delete old one
    if ($profileExists) {
        $stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        
        if ($profile && !empty($profile['avatar_url']) && file_exists('../../' . $profile['avatar_url'])) {
            unlink('../../' . $profile['avatar_url']);
        }
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
}

// Update or create user profile
if ($profileExists) {
    if ($avatarPath) {
        $stmt = $pdo->prepare("UPDATE user_profiles SET avatar_url = ?, bio = ?, location = ?, experience_level = ? WHERE user_id = ?");
        $stmt->execute([$avatarPath, $bio, $location, $experience_level, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE user_profiles SET bio = ?, location = ?, experience_level = ? WHERE user_id = ?");
        $stmt->execute([$bio, $location, $experience_level, $userId]);
    }
} else {
    if ($avatarPath) {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, avatar_url, bio, location, experience_level) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $avatarPath, $bio, $location, $experience_level]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, bio, location, experience_level) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $bio, $location, $experience_level]);
    }
}

// Update session username if changed
$_SESSION['username'] = $username;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Profile updated successfully'
]);
exit;
