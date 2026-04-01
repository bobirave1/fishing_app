<?php

namespace App\Services;

class SearchService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Unified search across users, posts, and waterbodies.
     */
    public function search(string $query, int $currentUserId, string $type = 'all'): array
    {
        $escaped = addcslashes($query, '%_\\');
        $term = "%{$escaped}%";
        $results = ['users' => [], 'posts' => [], 'spots' => []];

        if ($type === 'all' || $type === 'users') {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.full_name, up.avatar_url,
                       (SELECT COUNT(*) FROM friends WHERE user_id = ? AND friend_id = u.id) as is_friend
                FROM users u
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE (u.username LIKE ? OR u.full_name LIKE ?) AND u.id != ?
                ORDER BY u.username ASC LIMIT 10
            ");
            $stmt->execute([$currentUserId, $term, $term, $currentUserId]);
            $results['users'] = $stmt->fetchAll();
        }

        if ($type === 'all' || $type === 'posts') {
            $stmt = $this->pdo->prepare("
                SELECT p.id, p.title, p.content, p.user_id, u.username, up.avatar_url, p.created_at
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE (p.title LIKE ? OR p.content LIKE ?)
                  AND (p.visibility = 'public' OR p.user_id = ? OR p.user_id IN (
                      SELECT friend_id FROM friends WHERE user_id = ?))
                ORDER BY p.created_at DESC LIMIT 10
            ");
            $stmt->execute([$term, $term, $currentUserId, $currentUserId]);
            $results['posts'] = $stmt->fetchAll();
        }

        if ($type === 'all' || $type === 'spots') {
            $stmt = $this->pdo->prepare("
                SELECT id, name, type, latitude, longitude, description
                FROM waterbodies
                WHERE name LIKE ? OR description LIKE ?
                LIMIT 10
            ");
            $stmt->execute([$term, $term]);
            $results['spots'] = $stmt->fetchAll();
        }

        return $results;
    }
}
