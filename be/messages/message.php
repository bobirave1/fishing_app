<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

$requestId = bin2hex(random_bytes(8));

function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function jsonError(string $message, string $requestId, int $status): void {
    jsonResponse([
        'success' => false,
        'error' => $message,
        'request_id' => $requestId,
    ], $status);
}

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

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$receiverId = $_POST['receiver_id'] ?? $_GET['receiver_id'] ?? null;
$content = trim($_POST['content'] ?? '');

if ($action === 'send') {
    $files = $_FILES['files'] ?? [];
    $hasContent = !empty($content);
    $hasFiles = !empty($files['name'][0]);
    
    if (!$receiverId || (!$hasContent && !$hasFiles)) {
        jsonError('Receiver and message content or files required', $requestId, 400);
    }
    
    if (strlen($content) > 2000) {
        jsonError('Message too long', $requestId, 400);
    }
    
    // Check if receiver exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    if (!$stmt->fetch()) {
        jsonError('Receiver not found', $requestId, 404);
    }
    
    // Handle file uploads
    $attachmentUrls = [];
    if ($hasFiles) {
        $uploadDir = '../../fe/assets/uploads/messages/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        foreach ($files['name'] as $key => $fileName) {
            if ($files['error'][$key] !== UPLOAD_ERR_OK) continue;
            
            $fileTmp = $files['tmp_name'][$key];
            $fileSize = $files['size'][$key];
            $fileType = $files['type'][$key];
            
            // Validate file type and size
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/avi', 'video/mov'];
            $maxSize = 50 * 1024 * 1024; // 50MB
            
            if (!in_array($fileType, $allowedTypes) || $fileSize > $maxSize) {
                continue;
            }
            
            // Generate unique filename
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid('msg_') . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmp, $filePath)) {
                $attachmentUrls[] = 'assets/uploads/messages/' . $newFileName;
            }
        }
    }
    
    // Save message
    $attachmentJson = !empty($attachmentUrls) ? json_encode($attachmentUrls) : null;
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, attachment_urls, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $receiverId, $content, $attachmentJson]);
    $messageId = $pdo->lastInsertId();
    
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
    
    // Get conversation between two users
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, up.avatar_url
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE (m.sender_id = ? AND m.receiver_id = ?)
           OR (m.sender_id = ? AND m.receiver_id = ?)
        ORDER BY m.created_at ASC
        LIMIT 50
    ");
    $stmt->execute([$userId, $receiverId, $receiverId, $userId]);
    $messages = $stmt->fetchAll();
    
    // Mark messages as read
    $stmt = $pdo->prepare("
        UPDATE messages 
        SET is_read = 1 
        WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId, $receiverId]);
    
    // Ensure consistent ISO timestamps for front-end localization/parsing
    foreach ($messages as &$m) {
        if (!empty($m['created_at'])) {
            $m['created_at'] = date('c', strtotime($m['created_at']));
        }
    }
    
    jsonResponse([
        'success' => true,
        'messages' => $messages,
        'current_user_id' => $userId,
        'request_id' => $requestId,
    ]);
    
} else if ($action === 'get_conversations') {
    // Get list of conversations
    $stmt = $pdo->prepare("
        SELECT 
            DISTINCT CASE 
                WHEN sender_id = ? THEN receiver_id 
                ELSE sender_id 
            END as other_user_id,
            u.username, up.avatar_url,
            (SELECT MAX(created_at) FROM messages m2 WHERE 
                (m2.sender_id = messages.sender_id AND m2.receiver_id = messages.receiver_id) OR
                (m2.sender_id = messages.receiver_id AND m2.receiver_id = messages.sender_id)
            ) as last_message_time,
            (SELECT content FROM messages m3 WHERE 
                (m3.sender_id = messages.sender_id AND m3.receiver_id = messages.receiver_id) OR
                (m3.sender_id = messages.receiver_id AND m3.receiver_id = messages.sender_id)
                ORDER BY m3.created_at DESC LIMIT 1
            ) as last_message,
            (SELECT COUNT(*) FROM messages m4 WHERE m4.receiver_id = ? AND m4.is_read = 0 AND 
                (m4.sender_id = CASE WHEN messages.sender_id = ? THEN messages.receiver_id ELSE messages.sender_id END)
            ) as unread_count
        FROM messages
        JOIN users u ON u.id = CASE 
            WHEN sender_id = ? THEN receiver_id 
            ELSE sender_id 
        END
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE sender_id = ? OR receiver_id = ?
        GROUP BY other_user_id, u.id, u.username, up.avatar_url
        ORDER BY last_message_time DESC
    ");
    $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
    $conversations = $stmt->fetchAll();
    
    jsonResponse([
        'success' => true,
        'conversations' => $conversations,
        'request_id' => $requestId,
    ]);
    
} else {
    jsonError('Invalid action', $requestId, 400);
}
