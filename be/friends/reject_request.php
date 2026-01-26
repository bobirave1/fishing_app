<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid CSRF token');
}

$userId = $_SESSION['user_id'];
$requestId = (int)$_POST['request_id'];

// Delete the rejected request so it can be sent again
$stmt = $pdo->prepare(
    "DELETE FROM friend_requests
     WHERE id = ? AND receiver_id = ?"
);
$stmt->execute([$requestId, $userId]);

header("Location: list_requests.php");
exit;
