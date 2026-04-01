<?php
/**
 * Messages endpoint — delegates to MessageService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

$requestId = bin2hex(random_bytes(8));

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    jsonError('Unauthorized', $requestId, 401);
}

// CSRF Protection for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        jsonError('Invalid CSRF token', $requestId, 403);
    }
}

$container = $GLOBALS['container'];
$messageService = $container->get(App\Services\MessageService::class);
$logger = $container->get(App\Core\Logger::class);

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$receiverId = $_POST['receiver_id'] ?? $_GET['receiver_id'] ?? null;
$content = trim($_POST['content'] ?? '');

if ($action === 'send') {
    if (!checkRateLimit('msg_send_' . $userId, 30, 60)) {
        $logger->warning("Message rate limit hit for user {$userId}");
        jsonError('Too many messages. Please wait.', $requestId, 429);
    }

    $files = $_FILES['files'] ?? [];
    $hasContent = !empty($content);
    $hasFiles = !empty($files['name'][0]);

    if (!$receiverId || (!$hasContent && !$hasFiles)) {
        jsonError('Receiver and message content or files required', $requestId, 400);
    }

    if (strlen($content) > 2000) {
        jsonError('Message too long', $requestId, 400);
    }

    // Handle file uploads locally (filesystem concern)
    $attachmentUrls = [];
    if ($hasFiles) {
        $uploadDir = '../../fe/assets/uploads/messages/';
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) continue;
            $file = [
                'name' => $files['name'][$key],
                'type' => $files['type'][$key],
                'tmp_name' => $files['tmp_name'][$key],
                'error' => $files['error'][$key],
                'size' => $files['size'][$key],
            ];
            $result = secureUploadFile($file, $uploadDir, 'media');
            if ($result['success']) {
                $attachmentUrls[] = 'assets/uploads/messages/' . $result['filename'];
            }
        }
    }

    $messageId = $messageService->send($userId, (int)$receiverId, $content, $attachmentUrls);

    jsonResponse([
        'success' => true,
        'message_id' => $messageId,
        'created_at' => date('c'),
        'attachments' => $attachmentUrls,
        'request_id' => $requestId,
    ]);

} else if ($action === 'get_conversation') {
    if (!$receiverId) {
        jsonError('Receiver ID required', $requestId, 400);
    }

    $messages = $messageService->getConversation($userId, (int)$receiverId);

    jsonResponse([
        'success' => true,
        'messages' => $messages,
        'current_user_id' => $userId,
        'request_id' => $requestId,
    ]);

} else if ($action === 'get_conversations') {
    $conversations = $messageService->getConversations($userId);

    jsonResponse([
        'success' => true,
        'conversations' => $conversations,
        'request_id' => $requestId,
    ]);

} else if ($action === 'search_friends') {
    $query = trim($_GET['q'] ?? '');
    if (strlen($query) < 1) {
        jsonResponse(['success' => true, 'friends' => [], 'request_id' => $requestId]);
    }
    if (strlen($query) > 100) {
        jsonError('Query too long', $requestId, 400);
    }

    $friends = $messageService->searchFriends($userId, $query);
    jsonResponse(['success' => true, 'friends' => $friends, 'request_id' => $requestId]);

} else {
    jsonError('Invalid action', $requestId, 400);
}
