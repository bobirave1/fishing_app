<?php

namespace App\Services;

/**
 * Browser push notification service.
 * Manages push subscriptions and triggers browser Notification API via JS.
 */
class PushNotificationService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Save a push subscription for a user.
     */
    public function subscribe(int $userId, string $endpoint, string $p256dh, string $auth): void
    {
        // Remove existing subscription for same endpoint
        $this->pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?')
            ->execute([$userId, $endpoint]);

        $this->pdo->prepare(
            'INSERT INTO push_subscriptions (user_id, endpoint, p256dh_key, auth_key) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $endpoint, $p256dh, $auth]);
    }

    /**
     * Remove a push subscription.
     */
    public function unsubscribe(int $userId, string $endpoint): void
    {
        $this->pdo->prepare('DELETE FROM push_subscriptions WHERE user_id = ? AND endpoint = ?')
            ->execute([$userId, $endpoint]);
    }

    /**
     * Check if user has push notifications enabled.
     */
    public function isSubscribed(int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?');
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Get pending notifications for polling (used by JS to trigger browser Notification API).
     * Returns unread notifications since last check.
     */
    public function getPendingNotifications(int $userId, ?string $since = null): array
    {
        $sql = "
            SELECT n.id, n.type, n.message, n.created_at,
                   u.username AS from_username, up.avatar_url AS from_avatar
            FROM notifications n
            LEFT JOIN users u ON n.from_user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE n.user_id = ? AND n.is_read = 0
        ";
        $params = [$userId];

        if ($since) {
            $sql .= " AND n.created_at > ?";
            $params[] = $since;
        }

        $sql .= " ORDER BY n.created_at DESC LIMIT 10";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
