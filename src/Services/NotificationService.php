<?php

namespace App\Services;

class NotificationService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Get notifications for a user.
     */
    public function getForUser(int $userId, int $limit = 10, bool $unreadOnly = false): array
    {
        try {
            $table = $this->pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($table->rowCount() === 0) {
                return ['notifications' => [], 'unread_count' => 0];
            }

            $sql = "
                SELECT n.id, n.type, n.from_user_id, n.related_id, n.post_id,
                       n.is_read, n.created_at, n.message,
                       u.username, up.avatar_url
                FROM notifications n
                LEFT JOIN users u ON n.from_user_id = u.id
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE n.user_id = ?
            ";
            if ($unreadOnly) {
                $sql .= ' AND n.is_read = 0';
            }
            $sql .= ' ORDER BY n.created_at DESC LIMIT ?';

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
            $notifications = $stmt->fetchAll();

            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
            $stmt->execute([$userId]);
            $unread = (int) $stmt->fetchColumn();

            return ['notifications' => $notifications, 'unread_count' => $unread];
        } catch (\Throwable) {
            return ['notifications' => [], 'unread_count' => 0];
        }
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(int $notificationId, int $userId): void
    {
        $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
            ->execute([$notificationId, $userId]);
    }

    /**
     * Mark all notifications as read for a user.
     */
    public function markAllRead(int $userId): void
    {
        $this->pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0')
            ->execute([$userId]);
    }

    /**
     * Create a notification.
     */
    public function create(int $userId, string $type, int $fromUserId, ?int $relatedId = null, ?int $postId = null): void
    {
        try {
            $this->pdo->prepare(
                'INSERT INTO notifications (user_id, type, from_user_id, related_id, post_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
            )->execute([$userId, $type, $fromUserId, $relatedId, $postId]);
        } catch (\Throwable) {}
    }
}
