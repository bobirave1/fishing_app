<?php
/**
 * Shared helper functions for FISHINGLORY backend.
 * Include this file after security.php and database.php.
 */

/**
 * Send a JSON response and exit.
 */
function jsonResponse(array $payload, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

/**
 * Send a JSON error response and exit.
 */
function jsonError(string $message, string $requestId, int $status): void {
    jsonResponse([
        'success' => false,
        'error' => $message,
        'request_id' => $requestId,
    ], $status);
}

/**
 * Require an authenticated session (JSON response on failure).
 */
function requireAuth(string $requestId): int {
    if (!isset($_SESSION['user_id'])) {
        jsonError('Unauthorized', $requestId, 401);
    }
    return (int) $_SESSION['user_id'];
}

/**
 * Require a valid CSRF token on POST (JSON response on failure).
 */
function requireCsrf(string $requestId): void {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        jsonError('Invalid CSRF token', $requestId, 403);
    }
}

/**
 * Check if two users are friends.
 */
function areFriends(PDO $pdo, int $userId1, int $userId2): bool {
    $stmt = $pdo->prepare(
        'SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)'
    );
    $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
    return (bool) $stmt->fetch();
}

/**
 * Get all friend IDs for a user.
 */
function getFriendIds(PDO $pdo, int $userId): array {
    $stmt = $pdo->prepare(
        'SELECT friend_id FROM friends WHERE user_id = ? UNION SELECT user_id FROM friends WHERE friend_id = ?'
    );
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Create a notification.
 */
function createNotification(PDO $pdo, int $userId, string $type, int $fromUserId, ?int $postId = null): void {
    if ($userId === $fromUserId) return; // Don't notify yourself
    $stmt = $pdo->prepare(
        'INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at) VALUES (?, ?, ?, ?, NOW())'
    );
    $stmt->execute([$userId, $type, $fromUserId, $postId]);
}
