<?php

namespace App\Services;

class MessageService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Send a message with optional attachments.
     */
    public function send(int $senderId, int $receiverId, string $content, array $attachmentUrls = []): int
    {
        $attachmentJson = !empty($attachmentUrls) ? json_encode($attachmentUrls) : null;
        $stmt = $this->pdo->prepare(
            'INSERT INTO messages (sender_id, receiver_id, content, attachment_urls, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$senderId, $receiverId, $content, $attachmentJson]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Get conversation between two users.
     */
    public function getConversation(int $userId, int $otherUserId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare("
            SELECT m.*, u.username as sender_name
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
            LIMIT ?
        ");
        $stmt->execute([$userId, $otherUserId, $otherUserId, $userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get all conversations for a user (latest message per partner).
     */
    public function getConversations(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END AS partner_id,
                u.username AS partner_username,
                up.avatar_url AS partner_avatar,
                m.content AS last_message,
                m.created_at AS last_message_time,
                (SELECT COUNT(*) FROM messages m2
                 WHERE m2.sender_id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
                   AND m2.receiver_id = ? AND m2.is_read = 0) AS unread_count
            FROM messages m
            JOIN users u ON u.id = CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
            LEFT JOIN user_profiles up ON up.user_id = u.id
            WHERE m.sender_id = ? OR m.receiver_id = ?
            GROUP BY partner_id
            ORDER BY MAX(m.created_at) DESC
        ");
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Search friends by name for messaging.
     */
    public function searchFriends(int $userId, string $query): array
    {
        $escaped = addcslashes($query, '%_\\');
        $search = "%{$escaped}%";
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, u.full_name, up.avatar_url
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id IN (
                SELECT friend_id FROM friends WHERE user_id = ?
                UNION
                SELECT user_id FROM friends WHERE friend_id = ?
            )
            AND (u.username LIKE ? OR u.full_name LIKE ?)
            LIMIT 10
        ");
        $stmt->execute([$userId, $userId, $search, $search]);
        return $stmt->fetchAll();
    }
}
