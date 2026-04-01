<?php

namespace App\Services;

/**
 * Real-time chat service with long-polling support.
 * Extends the existing messages infrastructure with online presence and typing indicators.
 */
class ChatService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Poll for new messages since a given timestamp.
     * Returns new messages + online status + typing indicators.
     */
    public function poll(int $userId, ?string $since = null, ?int $partnerId = null): array
    {
        $this->heartbeat($userId);

        $result = [
            'messages'  => [],
            'typing'    => [],
            'online'    => [],
            'timestamp' => date('c'),
        ];

        // Fetch new messages since timestamp
        if ($since) {
            $sql = "SELECT m.*, u.username AS sender_name, up.avatar_url AS sender_avatar
                    FROM messages m
                    JOIN users u ON m.sender_id = u.id
                    LEFT JOIN user_profiles up ON u.id = up.user_id
                    WHERE m.receiver_id = ? AND m.created_at > ?";
            $params = [$userId, $since];

            if ($partnerId) {
                $sql .= " AND m.sender_id = ?";
                $params[] = $partnerId;
            }

            $sql .= " ORDER BY m.created_at ASC LIMIT 50";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $result['messages'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            // Mark fetched messages as seen
            if (!empty($result['messages'])) {
                $ids = array_column($result['messages'], 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $this->pdo->prepare(
                    "UPDATE messages SET is_read = 1, seen_at = NOW()
                     WHERE id IN ({$placeholders}) AND receiver_id = ? AND is_read = 0"
                )->execute([...$ids, $userId]);
            }
        }

        // Get typing indicators from friends
        $stmt = $this->pdo->prepare("
            SELECT os.user_id, u.username
            FROM user_online_status os
            JOIN users u ON u.id = os.user_id
            WHERE os.is_typing_to = ?
              AND os.last_seen > DATE_SUB(NOW(), INTERVAL 5 SECOND)
        ");
        $stmt->execute([$userId]);
        $result['typing'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Get online status of friends
        $stmt = $this->pdo->prepare("
            SELECT os.user_id, os.last_seen,
                   CASE WHEN os.last_seen > DATE_SUB(NOW(), INTERVAL 2 MINUTE) THEN 'online'
                        WHEN os.last_seen > DATE_SUB(NOW(), INTERVAL 10 MINUTE) THEN 'away'
                        ELSE 'offline' END AS status
            FROM user_online_status os
            WHERE os.user_id IN (
                SELECT friend_id FROM friends WHERE user_id = ?
                UNION
                SELECT user_id FROM friends WHERE friend_id = ?
            )
        ");
        $stmt->execute([$userId, $userId]);
        $result['online'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Unread counts per conversation
        $stmt = $this->pdo->prepare("
            SELECT sender_id, COUNT(*) AS unread_count
            FROM messages
            WHERE receiver_id = ? AND is_read = 0
            GROUP BY sender_id
        ");
        $stmt->execute([$userId]);
        $result['unread'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    /**
     * Update heartbeat (online status).
     */
    public function heartbeat(int $userId, ?int $typingTo = null): void
    {
        $this->pdo->prepare("
            INSERT INTO user_online_status (user_id, last_seen, is_typing_to)
            VALUES (?, NOW(), ?)
            ON DUPLICATE KEY UPDATE last_seen = NOW(), is_typing_to = ?
        ")->execute([$userId, $typingTo, $typingTo]);
    }

    /**
     * Set typing indicator.
     */
    public function setTyping(int $userId, ?int $targetId): void
    {
        $this->pdo->prepare("
            INSERT INTO user_online_status (user_id, last_seen, is_typing_to)
            VALUES (?, NOW(), ?)
            ON DUPLICATE KEY UPDATE last_seen = NOW(), is_typing_to = ?
        ")->execute([$userId, $targetId, $targetId]);
    }

    /**
     * Get total unread message count for a user.
     */
    public function getUnreadCount(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Mark all messages from a specific sender as read.
     */
    public function markConversationRead(int $userId, int $senderId): void
    {
        $this->pdo->prepare("
            UPDATE messages SET is_read = 1, seen_at = NOW()
            WHERE receiver_id = ? AND sender_id = ? AND is_read = 0
        ")->execute([$userId, $senderId]);
    }
}
