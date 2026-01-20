<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? null;
$receiverId = $_POST['receiver_id'] ?? $_GET['receiver_id'] ?? null;
$content = trim($_POST['content'] ?? '');

header('Content-Type: application/json');

if ($action === 'send') {
    if (!$receiverId || !$content) {
        http_response_code(400);
        exit(json_encode(['error' => 'Receiver and message content required']));
    }
    
    if (strlen($content) > 2000) {
        http_response_code(400);
        exit(json_encode(['error' => 'Message too long']));
    }
    
    // Check if receiver exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$receiverId]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        exit(json_encode(['error' => 'Receiver not found']));
    }
    
    // Save message
    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $receiverId, $content]);
    $messageId = $pdo->lastInsertId();
    
    exit(json_encode([
        'success' => true,
        'message_id' => $messageId,
        'created_at' => date('M d, Y H:i')
    ]));
    
} else if ($action === 'get_conversation') {
    if (!$receiverId) {
        http_response_code(400);
        exit(json_encode(['error' => 'Receiver ID required']));
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
    
    exit(json_encode([
        'success' => true,
        'messages' => $messages,
        'current_user_id' => $userId
    ]));
    
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
    
    exit(json_encode([
        'success' => true,
        'conversations' => $conversations
    ]));
    
} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid action']));
}
