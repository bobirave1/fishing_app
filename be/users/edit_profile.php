<?php
/**
 * Edit profile endpoint — delegates to UserService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$container = $GLOBALS['container'];
$userService = $container->get(App\Services\UserService::class);

$userId = $_SESSION['user_id'];

$full_name = trim($_POST['full_name'] ?? '');
$username = trim($_POST['username'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$location = trim($_POST['location'] ?? '');
$experience_level = $_POST['experience_level'] ?? 'beginner';

if (empty($full_name) || empty($username)) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Full name and username are required']));
}

if (strlen($bio) > 500) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Bio is too long (max 500 characters)']));
}

// Handle avatar upload (filesystem concern — stays local)
$avatarPath = null;
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
    $result = secureUploadFile($_FILES['avatar'], '../../fe/assets/img/avatars', 'image');
    if (!$result['success']) {
        http_response_code(400);
        header('Content-Type: application/json');
        exit(json_encode(['error' => $result['error']]));
    }

    // Delete old avatar after successful upload
    $oldAvatar = $userService->getAvatarUrl($userId);
    if ($oldAvatar && file_exists('../../' . $oldAvatar)) {
        unlink('../../' . $oldAvatar);
    }

    $avatarPath = 'fe/assets/img/avatars/' . $result['filename'];
}

$userService->updateProfile($userId, [
    'full_name' => $full_name,
    'username' => $username,
    'bio' => $bio,
    'location' => $location,
    'experience_level' => $experience_level,
]);

if ($avatarPath) {
    $userService->updateAvatar($userId, $avatarPath);
}

$_SESSION['username'] = $username;

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Profile updated successfully'
]);
exit;
