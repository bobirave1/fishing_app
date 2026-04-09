<?php
session_start();
require '../../config/database.php';
require '../../config/avatar_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

try {
    $userId = $_SESSION['user_id'];
    
    // Check if notifications table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'notifications'");
    
    if ($tableCheck->rowCount() == 0) {
        // Table doesn't exist, return empty notifications
        exit(json_encode([
            'success' => true,
            'notifications' => [],
            'unread_count' => 0
        ]));
    }
    
    $limit = intval($_GET['limit'] ?? 10);
    $unreadOnly = isset($_GET['unread_only']) && $_GET['unread_only'];

    $query = "
        SELECT 
            n.id, n.type, n.from_user_id, n.related_id, n.post_id,
            n.is_read, n.created_at, n.message,
            u.username, up.avatar_url
        FROM notifications n
        LEFT JOIN users u ON n.from_user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE n.user_id = ?
    ";

    if ($unreadOnly) {
        $query .= " AND n.is_read = 0";
    }

    $query .= " ORDER BY n.created_at DESC LIMIT ?";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userId, $limit]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Add default avatars
    $defaultAvatar = getDefaultAvatarPath();
    foreach ($notifications as &$n) {
        if (empty($n['avatar_url']) || !is_file(__DIR__ . '/../../' . $n['avatar_url'])) {
            $n['avatar_url'] = $defaultAvatar;
        }
    }

    // Get unread count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    $countResult = $stmt->fetch(PDO::FETCH_ASSOC);

    exit(json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $countResult['unread_count'] ?? 0
    ]));
    
} catch (Exception $e) {
    error_log("Notifications error: " . $e->getMessage());
    // Return empty notifications instead of error
    exit(json_encode([
        'success' => true,
        'notifications' => [],
        'unread_count' => 0
    ]));
}
