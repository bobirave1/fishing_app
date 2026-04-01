<?php

namespace App\Services;

class PostService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Get paginated post feed for a logged-in user.
     */
    public function getFeed(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM posts p
             WHERE p.visibility = 'public'
                OR (p.visibility = 'friends' AND p.user_id IN (
                    SELECT friend_id FROM friends WHERE user_id = ?))
                OR p.user_id = ?"
        );
        $countStmt->execute([$userId, $userId]);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT p.*, u.username, up.avatar_url,
                   COALESCE(lc.like_count, 0) as like_count,
                   COALESCE(cc.comment_count, 0) as comment_count,
                   pi_agg.media_urls,
                   CASE WHEN ul.user_id IS NOT NULL THEN 1 ELSE 0 END as user_liked
            FROM posts p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (SELECT post_id, COUNT(*) as like_count FROM post_likes GROUP BY post_id) lc ON lc.post_id = p.id
            LEFT JOIN (SELECT post_id, COUNT(*) as comment_count FROM post_comments GROUP BY post_id) cc ON cc.post_id = p.id
            LEFT JOIN (SELECT post_id, GROUP_CONCAT(image_url ORDER BY id SEPARATOR '||') as media_urls FROM post_images GROUP BY post_id) pi_agg ON pi_agg.post_id = p.id
            LEFT JOIN post_likes ul ON ul.post_id = p.id AND ul.user_id = ?
            WHERE p.visibility = 'public'
               OR (p.visibility = 'friends' AND p.user_id IN (
                   SELECT friend_id FROM friends WHERE user_id = ?))
               OR p.user_id = ?
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(3, $userId, \PDO::PARAM_INT);
        $stmt->bindValue(4, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(5, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['posts' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * Get public post feed for guests.
     */
    public function getPublicFeed(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->pdo->query("SELECT COUNT(*) FROM posts WHERE visibility = 'public'")->fetchColumn();

        $stmt = $this->pdo->prepare("
            SELECT p.*, u.username, up.avatar_url,
                   COALESCE(lc.like_count, 0) as like_count,
                   COALESCE(cc.comment_count, 0) as comment_count,
                   pi_agg.media_urls,
                   0 as user_liked
            FROM posts p
            JOIN users u ON u.id = p.user_id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (SELECT post_id, COUNT(*) as like_count FROM post_likes GROUP BY post_id) lc ON lc.post_id = p.id
            LEFT JOIN (SELECT post_id, COUNT(*) as comment_count FROM post_comments GROUP BY post_id) cc ON cc.post_id = p.id
            LEFT JOIN (SELECT post_id, GROUP_CONCAT(image_url ORDER BY id SEPARATOR '||') as media_urls FROM post_images GROUP BY post_id) pi_agg ON pi_agg.post_id = p.id
            WHERE p.visibility = 'public'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $perPage, \PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return ['posts' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * Create a new post. Returns the new post ID.
     */
    public function create(int $userId, string $title, string $content, string $visibility, ?string $imagePath, array $mediaPaths = []): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO posts (user_id, title, content, image, visibility, created_at) VALUES (?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$userId, $title, $content, $imagePath, $visibility]);
        $postId = (int) $this->pdo->lastInsertId();

        if (!empty($mediaPaths)) {
            try {
                $mediaStmt = $this->pdo->prepare('INSERT INTO post_images (post_id, image_url, uploaded_at) VALUES (?, ?, NOW())');
                foreach ($mediaPaths as $path) {
                    $mediaStmt->execute([$postId, $path]);
                }
            } catch (\Throwable) {
                // post_images table may not exist in some setups
            }
        }

        return $postId;
    }

    /**
     * Update a post.
     */
    public function update(int $postId, string $title, string $content, string $visibility, ?string $imagePath): void
    {
        $this->pdo->prepare(
            'UPDATE posts SET title = ?, content = ?, image = ?, visibility = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$title, $content, $imagePath, $visibility, $postId]);
    }

    /**
     * Delete a post + its image file.
     */
    public function delete(int $postId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_id, image FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        $post = $stmt->fetch();

        if (!$post || (int) $post['user_id'] !== $userId) {
            return false;
        }

        $this->pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$postId]);

        if (!empty($post['image'])) {
            $file = dirname(__DIR__, 2) . '/' . $post['image'];
            if (is_file($file)) {
                @unlink($file);
            }
        }

        return true;
    }

    /**
     * Get a single post by ID.
     */
    public function findById(int $postId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM posts WHERE id = ?');
        $stmt->execute([$postId]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Toggle like on a post. Returns [like_count, liked].
     */
    public function toggleLike(int $postId, int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?');
        $stmt->execute([$postId, $userId]);

        if ($stmt->fetch()) {
            $this->pdo->prepare('DELETE FROM post_likes WHERE post_id = ? AND user_id = ?')
                ->execute([$postId, $userId]);
            $liked = false;
        } else {
            $this->pdo->prepare('INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)')
                ->execute([$postId, $userId]);
            $liked = true;

            // Notify post owner
            $post = $this->findById($postId);
            if ($post && (int) $post['user_id'] !== $userId) {
                try {
                    $this->pdo->prepare(
                        'INSERT INTO notifications (user_id, type, related_id, from_user_id) VALUES (?, \'like\', ?, ?)'
                    )->execute([$post['user_id'], $postId, $userId]);
                } catch (\Throwable) {}

                // Award XP to post owner for receiving a like
                try {
                    $gamification = new GamificationService($this->pdo);
                    $gamification->awardXp((int) $post['user_id'], 'like_received', $postId);
                } catch (\Throwable) {}
            }
        }

        $count = (int) $this->pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?')
            ->execute([$postId]) ? $this->pdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;

        // Re-query for accurate count
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM post_likes WHERE post_id = ?');
        $stmt->execute([$postId]);
        $count = (int) $stmt->fetchColumn();

        return ['like_count' => $count, 'liked' => $liked];
    }

    /**
     * Add a comment to a post. Returns the comment data.
     */
    public function addComment(int $postId, int $userId, string $content, ?int $parentId = null): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO post_comments (post_id, user_id, content, parent_id, created_at) VALUES (?, ?, ?, ?, NOW())'
        );
        $stmt->execute([$postId, $userId, $content, $parentId]);
        $commentId = (int) $this->pdo->lastInsertId();

        // Award XP for commenting (gamification)
        try {
            $gamification = new GamificationService($this->pdo);
            $gamification->awardXp($userId, 'comment_added', $postId);
        } catch (\Throwable) {}

        // Notify post owner
        $post = $this->findById($postId);
        if ($post && (int) $post['user_id'] !== $userId) {
            try {
                $this->pdo->prepare(
                    'INSERT INTO notifications (user_id, type, related_id, from_user_id) VALUES (?, \'comment\', ?, ?)'
                )->execute([$post['user_id'], $postId, $userId]);
            } catch (\Throwable) {}
        }

        // Get user info
        $stmt = $this->pdo->prepare(
            'SELECT u.username, up.avatar_url FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = ?'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return [
            'comment_id' => $commentId,
            'username' => $user['username'],
            'avatar' => $user['avatar_url'] ?? getDefaultAvatarPath(),
            'content' => $content,
            'created_at' => date('c'),
        ];
    }

    /**
     * Get all comments for a post.
     */
    public function getComments(int $postId, int $currentUserId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT pc.id, pc.user_id, pc.content, pc.created_at, pc.parent_id,
                   u.username, up.avatar_url,
                   (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = pc.id) AS like_count,
                   (SELECT COUNT(*) FROM comment_likes cl WHERE cl.comment_id = pc.id AND cl.user_id = ?) AS user_liked,
                   pu.username AS parent_username
            FROM post_comments pc
            JOIN users u ON pc.user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN post_comments pp ON pc.parent_id = pp.id
            LEFT JOIN users pu ON pp.user_id = pu.id
            WHERE pc.post_id = ?
            ORDER BY pc.created_at ASC
        ");
        $stmt->execute([$currentUserId, $postId]);
        $comments = $stmt->fetchAll();

        foreach ($comments as &$c) {
            if (!empty($c['created_at'])) {
                $c['created_at'] = date('c', strtotime($c['created_at']));
            }
            $c['like_count'] = (int) $c['like_count'];
            $c['user_liked'] = (int) $c['user_liked'];
        }

        return $comments;
    }

    /**
     * Delete a comment (only by its author).
     */
    public function deleteComment(int $commentId, int $userId): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM post_comments WHERE id = ?');
        $stmt->execute([$commentId]);
        $comment = $stmt->fetch();

        if (!$comment || (int) $comment['user_id'] !== $userId) {
            return false;
        }

        $this->pdo->prepare('DELETE FROM post_comments WHERE id = ?')->execute([$commentId]);
        return true;
    }

    /**
     * Toggle like on a comment. Returns [liked, like_count].
     */
    public function toggleCommentLike(int $commentId, int $userId): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM comment_likes WHERE comment_id = ? AND user_id = ?');
        $stmt->execute([$commentId, $userId]);

        if ($stmt->fetch()) {
            $this->pdo->prepare('DELETE FROM comment_likes WHERE comment_id = ? AND user_id = ?')
                ->execute([$commentId, $userId]);
            $liked = false;
        } else {
            $this->pdo->prepare('INSERT INTO comment_likes (comment_id, user_id) VALUES (?, ?)')
                ->execute([$commentId, $userId]);
            $liked = true;
        }

        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM comment_likes WHERE comment_id = ?');
        $stmt->execute([$commentId]);
        return ['liked' => $liked, 'like_count' => (int) $stmt->fetchColumn()];
    }

    /**
     * Get friend IDs for notification after post creation.
     */
    public function getFriendIds(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT DISTINCT
                CASE WHEN user_id = ? THEN friend_id
                     WHEN friend_id = ? THEN user_id
                END as friend_id
            FROM friends
            WHERE user_id = ? OR friend_id = ?
        ");
        $stmt->execute([$userId, $userId, $userId, $userId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }
}
