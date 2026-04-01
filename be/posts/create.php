<?php
/**
 * Post creation endpoint (legacy direct access).
 * Delegates to PostService via DI container.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

use App\Services\PostService;
use App\Core\Logger;

/** @var \App\Core\Container $container */
$container = $GLOBALS['container'];
$logger = $container->get(Logger::class);

if (!isset($_SESSION['user_id'])) {
    exit;
}

$userId = $_SESSION['user_id'];

// Rate limit
if (!checkRateLimit('post_create_' . $userId, 10, 300)) {
    $_SESSION['post_error'] = 'Too many posts. Please wait a few minutes.';
    header('Location: ../../index.php');
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$title = trim($_POST['title'] ?? '');
$content = trim($_POST['content'] ?? '');
$visibility = $_POST['visibility'] ?? 'public';

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

// Handle file uploads
$uploadedMediaPaths = [];
if (isset($_FILES['media'])) {
    $isMultiple = is_array($_FILES['media']['name']);
    $mediaCount = $isMultiple ? count($_FILES['media']['name']) : 1;

    for ($i = 0; $i < $mediaCount; $i++) {
        $file = $isMultiple
            ? [
                'name' => $_FILES['media']['name'][$i] ?? '',
                'type' => $_FILES['media']['type'][$i] ?? '',
                'tmp_name' => $_FILES['media']['tmp_name'][$i] ?? '',
                'error' => $_FILES['media']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['media']['size'][$i] ?? 0,
            ]
            : $_FILES['media'];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $result = secureUploadFile($file, '../../fe/assets/img', 'media');
        if (!$result['success']) {
            $_SESSION['post_error'] = $result['error'];
            header('Location: ../../index.php');
            exit;
        }

        $uploadedMediaPaths[] = 'fe/assets/img/' . $result['filename'];
    }
}

$imagePath = $uploadedMediaPaths[0] ?? null;

$postService = $container->get(PostService::class);
$postId = $postService->create($userId, $title, $content, $visibility, $imagePath, $uploadedMediaPaths);

// Notify friends about new post
if ($visibility !== 'private') {
    $friends = $postService->getFriendIds($userId);
    $pdo = $container->pdo();
    $notifStmt = $pdo->prepare(
        "INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at)
         VALUES (?, 'new_post', ?, ?, NOW())"
    );
    foreach ($friends as $friendId) {
        if ($friendId) {
            try {
                $notifStmt->execute([$friendId, $userId, $postId]);
            } catch (\Throwable) {
            }
        }
    }
}

$logger->info('Post created: {postId} by user {userId}', ['postId' => $postId, 'userId' => $userId]);

// Award XP and check for new badges (gamification)
try {
    $gamification = $container->get(\App\Services\GamificationService::class);
    $result = $gamification->awardXp($userId, 'post_created', $postId);
    if (!empty($result['new_badges'])) {
        $_SESSION['new_badges'] = $result['new_badges'];
    }
} catch (\Throwable $e) {
    $logger->error('Gamification awardXp failed: {msg}', ['msg' => $e->getMessage()]);
}

header('Location: ../../index.php');
exit;
