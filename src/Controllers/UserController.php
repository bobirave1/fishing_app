<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\UserService;

class UserController extends Controller
{
    public function profile(string $id): void
    {
        $_GET['id'] = $id; // profile.php reads from $_GET
        require dirname(__DIR__, 2) . '/be/users/profile.php';
    }

    public function editProfile(): void
    {
        $this->requireAuth();
        require dirname(__DIR__, 2) . '/fe/pages/edit_profile.php';
    }

    public function editProfileApi(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $bio = trim($_POST['bio'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $experience = $_POST['experience_level'] ?? 'beginner';

        if (empty($fullName) || empty($username)) {
            $this->jsonError('Full name and username are required');
        }
        if (strlen($bio) > 500) {
            $this->jsonError('Bio is too long (max 500 characters)');
        }
        if (!in_array($experience, ['beginner', 'advanced', 'pro'])) {
            $experience = 'beginner';
        }

        $service = $this->service(UserService::class);

        // Handle avatar upload
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $result = secureUploadFile($_FILES['avatar'], dirname(__DIR__, 2) . '/fe/assets/img/avatars', 'image');
            if (!$result['success']) {
                $this->jsonError($result['error']);
            }

            // Delete old avatar
            $oldAvatar = $service->getAvatarUrl($userId);
            if ($oldAvatar) {
                $oldFile = dirname(__DIR__, 2) . '/' . $oldAvatar;
                if (is_file($oldFile)) @unlink($oldFile);
            }

            $avatarPath = 'fe/assets/img/avatars/' . $result['filename'];
            $service->updateAvatar($userId, $avatarPath);
        }

        $service->updateProfile($userId, [
            'full_name' => $fullName,
            'username' => $username,
            'bio' => $bio,
            'location' => $location,
            'experience_level' => $experience,
        ]);

        $this->jsonOk(['avatar_url' => $avatarPath, 'message' => 'Profile updated']);
    }

    public function uploadAvatar(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== 0) {
            $this->jsonError('No image provided');
        }

        $result = secureUploadFile($_FILES['avatar'], dirname(__DIR__, 2) . '/fe/assets/img/avatars', 'image');
        if (!$result['success']) {
            $this->jsonError($result['error']);
        }

        $service = $this->service(UserService::class);

        // Delete old avatar
        $oldAvatar = $service->getAvatarUrl($userId);
        if ($oldAvatar) {
            $oldFile = dirname(__DIR__, 2) . '/' . $oldAvatar;
            if (is_file($oldFile)) @unlink($oldFile);
        }

        $avatarPath = 'fe/assets/img/avatars/' . $result['filename'];
        $service->updateAvatar($userId, $avatarPath);

        $this->jsonOk(['avatar_url' => $avatarPath]);
    }

    public function follow(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $targetId = (int) ($_POST['target_id'] ?? 0);
        $action = $_POST['action'] ?? 'follow';

        if (!$targetId) $this->jsonError('Target user ID required');
        if ($userId === $targetId) $this->jsonError('Cannot follow yourself');

        $service = $this->service(UserService::class);
        $stats = $service->toggleFollow($userId, $targetId, $action);
        $this->jsonOk($stats);
    }
}
