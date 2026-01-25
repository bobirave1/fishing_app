<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit(json_encode(['error' => 'Unauthorized']));
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit(json_encode(['error' => 'Invalid CSRF token']));
}

$userId = $_SESSION['user_id'];
$notificationId = $_POST['notification_id'] ?? null;
$action = $_POST['action'] ?? 'mark_read'; // mark_read or mark_all_read

header('Content-Type: application/json');

if ($action === 'mark_read' && $notificationId) {
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE id = ? AND user_id = ?
    ");
    $stmt->execute([$notificationId, $userId]);
    exit(json_encode(['success' => true]));
    
} else if ($action === 'mark_all_read') {
    $stmt = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$userId]);
    exit(json_encode(['success' => true]));
    
} else {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid action']));
}
