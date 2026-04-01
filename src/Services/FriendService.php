<?php

namespace App\Services;

class FriendService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Send a friend request. Returns true on success.
     * @throws \RuntimeException on validation errors.
     */
    public function sendRequest(int $senderId, int $receiverId): bool
    {
        if ($senderId === $receiverId) {
            throw new \RuntimeException('Cannot add yourself.');
        }

        // Check if already friends
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)'
        );
        $stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('Already friends.');
        }

        // Check for pending request
        $stmt = $this->pdo->prepare(
            "SELECT 1 FROM friend_requests
             WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?))
             AND status = 'pending'"
        );
        $stmt->execute([$senderId, $receiverId, $receiverId, $senderId]);
        if ($stmt->fetch()) {
            throw new \RuntimeException('Friend request already sent.');
        }

        $this->pdo->prepare('INSERT INTO friend_requests (sender_id, receiver_id) VALUES (?, ?)')
            ->execute([$senderId, $receiverId]);

        try {
            $this->pdo->prepare(
                "INSERT INTO notifications (user_id, type, from_user_id, related_id, created_at)
                 VALUES (?, 'friend_request', ?, ?, NOW())"
            )->execute([$receiverId, $senderId, $senderId]);
        } catch (\Throwable) {}

        return true;
    }

    /**
     * Accept a friend request.
     */
    public function acceptRequest(int $requestId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT sender_id FROM friend_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'"
        );
        $stmt->execute([$requestId, $userId]);
        $request = $stmt->fetch();

        if (!$request) {
            return false;
        }

        $senderId = (int) $request['sender_id'];

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("UPDATE friend_requests SET status = 'accepted' WHERE id = ?")
                ->execute([$requestId]);
            $this->pdo->prepare('INSERT INTO friends (user_id, friend_id) VALUES (?, ?), (?, ?)')
                ->execute([$userId, $senderId, $senderId, $userId]);

            try {
                $this->pdo->prepare(
                    "INSERT INTO notifications (user_id, type, from_user_id, related_id, created_at)
                     VALUES (?, 'friend_accepted', ?, ?, NOW())"
                )->execute([$senderId, $userId, $userId]);
            } catch (\Throwable) {}

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reject (delete) a friend request.
     */
    public function rejectRequest(int $requestId, int $userId): void
    {
        $this->pdo->prepare('DELETE FROM friend_requests WHERE id = ? AND receiver_id = ?')
            ->execute([$requestId, $userId]);
    }

    /**
     * Remove a friendship (bidirectional).
     */
    public function removeFriend(int $userId, int $friendId): bool
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)'
        );
        $stmt->execute([$userId, $friendId, $friendId, $userId]);
        return $stmt->rowCount() > 0;
    }

    /**
     * Check if two users are friends.
     */
    public function areFriends(int $userId1, int $userId2): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)'
        );
        $stmt->execute([$userId1, $userId2, $userId2, $userId1]);
        return (bool) $stmt->fetch();
    }

    /**
     * Get all friend IDs for a user.
     */
    public function getFriendIds(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT friend_id FROM friends WHERE user_id = ?
             UNION
             SELECT user_id FROM friends WHERE friend_id = ?'
        );
        $stmt->execute([$userId, $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
