<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

$userId = $_SESSION['user_id'];
$limit = $_GET['limit'] ?? 10;
$unreadOnly = $_GET['unread_only'] ?? false;

header('Content-Type: application/json');

$query = "
    SELECT 
        n.id, n.type, n.user_id, n.related_id, n.post_id, 
        n.is_read, n.created_at,
        u.username, up.avatar_url
    FROM notifications n
    JOIN users u ON n.sender_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE n.user_id = ?
";

if ($unreadOnly) {
    $query .= " AND n.is_read = 0";
}

$query .= " ORDER BY n.created_at DESC LIMIT ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$userId, $limit]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $pdo->prepare("
    SELECT COUNT(*) as unread_count 
    FROM notifications 
    WHERE user_id = ? AND is_read = 0
");
$stmt->execute([$userId]);
$countResult = $stmt->fetch();

exit(json_encode([
    'success' => true,
    'notifications' => $notifications,
    'unread_count' => $countResult['unread_count']
]));
