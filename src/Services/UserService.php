<?php

namespace App\Services;

class UserService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Get full user profile by user ID.
     */
    public function getProfile(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, u.full_name, u.email, u.created_at,
                   up.bio, up.location, up.experience_level, up.avatar_url,
                   (SELECT COUNT(*) FROM posts WHERE user_id = u.id) as post_count,
                   (SELECT COUNT(*) FROM follows WHERE following_id = u.id) as follower_count,
                   (SELECT COUNT(*) FROM follows WHERE follower_id = u.id) as following_count
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Update basic user info + profile.
     */
    public function updateProfile(int $userId, array $data): void
    {
        $this->pdo->prepare('UPDATE users SET full_name = ?, username = ? WHERE id = ?')
            ->execute([$data['full_name'], $data['username'], $userId]);

        $stmt = $this->pdo->prepare('SELECT user_id FROM user_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);

        if ($stmt->fetch()) {
            $this->pdo->prepare(
                'UPDATE user_profiles SET bio = ?, location = ?, experience_level = ? WHERE user_id = ?'
            )->execute([$data['bio'], $data['location'], $data['experience_level'], $userId]);
        } else {
            $this->pdo->prepare(
                'INSERT INTO user_profiles (user_id, bio, location, experience_level) VALUES (?, ?, ?, ?)'
            )->execute([$userId, $data['bio'], $data['location'], $data['experience_level']]);
        }
    }

    /**
     * Update avatar URL for a user.
     */
    public function updateAvatar(int $userId, string $avatarPath): void
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM user_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);

        if ($stmt->fetch()) {
            $this->pdo->prepare('UPDATE user_profiles SET avatar_url = ? WHERE user_id = ?')
                ->execute([$avatarPath, $userId]);
        } else {
            $this->pdo->prepare('INSERT INTO user_profiles (user_id, avatar_url) VALUES (?, ?)')
                ->execute([$userId, $avatarPath]);
        }
    }

    /**
     * Get current avatar URL for a user (or null).
     */
    public function getAvatarUrl(int $userId): ?string
    {
        $stmt = $this->pdo->prepare('SELECT avatar_url FROM user_profiles WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row['avatar_url'] ?? null;
    }

    /**
     * Toggle follow on a target user. Returns follow stats.
     */
    public function toggleFollow(int $userId, int $targetId, string $action = 'follow'): array
    {
        if ($action === 'follow') {
            $stmt = $this->pdo->prepare('SELECT id FROM follows WHERE follower_id = ? AND following_id = ?');
            $stmt->execute([$userId, $targetId]);

            if (!$stmt->fetch()) {
                $this->pdo->prepare('INSERT INTO follows (follower_id, following_id) VALUES (?, ?)')
                    ->execute([$userId, $targetId]);

                try {
                    $this->pdo->prepare(
                        "INSERT INTO notifications (user_id, type, from_user_id) VALUES (?, 'follow', ?)"
                    )->execute([$targetId, $userId]);
                } catch (\Throwable) {}
            }
        } else {
            $this->pdo->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?')
                ->execute([$userId, $targetId]);
        }

        return $this->getFollowStats($userId, $targetId);
    }

    /**
     * Get follow statistics for a target user.
     */
    public function getFollowStats(int $currentUserId, int $targetId): array
    {
        $followers = (int) $this->pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?')
            ->execute([$targetId]) ? 0 : 0;
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM follows WHERE following_id = ?');
        $stmt->execute([$targetId]);
        $followers = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM follows WHERE follower_id = ?');
        $stmt->execute([$targetId]);
        $following = (int) $stmt->fetchColumn();

        $stmt = $this->pdo->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$currentUserId, $targetId]);
        $isFollowing = (bool) $stmt->fetch();

        return [
            'followers' => $followers,
            'following' => $following,
            'is_following' => $isFollowing,
        ];
    }
}
