<?php
/**
 * Upload avatar endpoint — delegates DB update to UserService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== 0) {
    http_response_code(400);
    header('Content-Type: application/json');
    exit(json_encode(['error' => 'No image provided']));
}

$result = secureUploadFile($_FILES['avatar'], '../../fe/assets/img/avatars', 'image');
if (!$result['success']) {
    http_response_code(500);
    header('Content-Type: application/json');
    exit(json_encode(['error' => $result['error']]));
}

$container = $GLOBALS['container'];
$userService = $container->get(App\Services\UserService::class);

// Delete old avatar after successful upload
$oldAvatar = $userService->getAvatarUrl($userId);
if ($oldAvatar && file_exists('../../' . $oldAvatar)) {
    unlink('../../' . $oldAvatar);
}

$avatarPath = 'fe/assets/img/avatars/' . $result['filename'];
$userService->updateAvatar($userId, $avatarPath);

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'avatar_url' => $avatarPath,
    'message' => 'Avatar updated successfully'
]);
exit;
