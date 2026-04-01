<?php

namespace App\Services;

/**
 * Advanced search with filters, FULLTEXT support, and location radius search.
 */
class AdvancedSearchService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Advanced search with filters.
     *
     * @param array $filters Keys: query, type, date_from, date_to, species, location_lat,
     *                       location_lon, radius_km, visibility, sort, page, per_page
     */
    public function search(array $filters, int $currentUserId): array
    {
        $query     = trim($filters['query'] ?? '');
        $type      = $filters['type'] ?? 'all';
        $dateFrom  = $filters['date_from'] ?? null;
        $dateTo    = $filters['date_to'] ?? null;
        $species   = trim($filters['species'] ?? '');
        $lat       = isset($filters['location_lat']) ? (float)$filters['location_lat'] : null;
        $lon       = isset($filters['location_lon']) ? (float)$filters['location_lon'] : null;
        $radiusKm  = isset($filters['radius_km']) ? (float)$filters['radius_km'] : 50;
        $sort      = $filters['sort'] ?? 'relevance';
        $page      = max(1, (int)($filters['page'] ?? 1));
        $perPage   = min(50, max(5, (int)($filters['per_page'] ?? 15)));
        $offset    = ($page - 1) * $perPage;

        $results = [];

        if ($type === 'all' || $type === 'posts') {
            $results['posts'] = $this->searchPosts($query, $currentUserId, $dateFrom, $dateTo, $species, $sort, $perPage, $offset);
        }

        if ($type === 'all' || $type === 'users') {
            $results['users'] = $this->searchUsers($query, $currentUserId, $perPage, $offset);
        }

        if ($type === 'all' || $type === 'spots') {
            $results['spots'] = $this->searchSpots($query, $lat, $lon, $radiusKm, $perPage, $offset);
        }

        if ($type === 'all' || $type === 'catches') {
            $results['catches'] = $this->searchCatches($query, $species, $dateFrom, $dateTo, $currentUserId, $perPage, $offset);
        }

        return $results;
    }

    private function searchPosts(string $query, int $userId, ?string $dateFrom, ?string $dateTo, string $species, string $sort, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];

        // Visibility filter
        $conditions[] = "(p.visibility = 'public' OR p.user_id = ? OR (p.visibility = 'friends' AND p.user_id IN (SELECT friend_id FROM friends WHERE user_id = ?)))";
        $params[] = $userId;
        $params[] = $userId;

        // FULLTEXT or LIKE search
        $hasFulltext = false;
        if ($query !== '') {
            try {
                // Test if FULLTEXT index exists
                $this->pdo->query("SELECT 1 FROM posts WHERE MATCH(title, content) AGAINST ('test' IN BOOLEAN MODE) LIMIT 0");
                $hasFulltext = true;
            } catch (\Throwable) {}

            if ($hasFulltext) {
                $conditions[] = "MATCH(p.title, p.content) AGAINST (? IN BOOLEAN MODE)";
                $params[] = $this->buildBooleanQuery($query);
            } else {
                $escaped = addcslashes($query, '%_\\');
                $conditions[] = "(p.title LIKE ? OR p.content LIKE ?)";
                $params[] = "%{$escaped}%";
                $params[] = "%{$escaped}%";
            }
        }

        // Date filters
        if ($dateFrom) {
            $conditions[] = "p.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        if ($dateTo) {
            $conditions[] = "p.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        // Species filter — join fish_catches
        $speciesJoin = '';
        if ($species !== '') {
            $speciesJoin = "LEFT JOIN fish_catches fc ON fc.post_id = p.id";
            $escaped = addcslashes($species, '%_\\');
            $conditions[] = "fc.fish_species LIKE ?";
            $params[] = "%{$escaped}%";
        }

        $where = implode(' AND ', $conditions);
        $orderBy = match ($sort) {
            'date_asc'  => 'p.created_at ASC',
            'date_desc' => 'p.created_at DESC',
            'likes'     => 'like_count DESC, p.created_at DESC',
            default     => $hasFulltext && $query !== ''
                ? "MATCH(p.title, p.content) AGAINST ('{$this->buildBooleanQuery($query)}' IN BOOLEAN MODE) DESC, p.created_at DESC"
                : 'p.created_at DESC',
        };

        $sql = "
            SELECT p.id, p.title, p.content, p.user_id, p.visibility, p.created_at,
                   u.username, up.avatar_url,
                   COALESCE(lc.cnt, 0) AS like_count,
                   COALESCE(cc.cnt, 0) AS comment_count,
                   pi_agg.media_urls
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN (SELECT post_id, COUNT(*) cnt FROM post_likes GROUP BY post_id) lc ON lc.post_id = p.id
            LEFT JOIN (SELECT post_id, COUNT(*) cnt FROM post_comments GROUP BY post_id) cc ON cc.post_id = p.id
            LEFT JOIN (SELECT post_id, GROUP_CONCAT(image_url ORDER BY id SEPARATOR '||') media_urls FROM post_images GROUP BY post_id) pi_agg ON pi_agg.post_id = p.id
            {$speciesJoin}
            WHERE {$where}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function searchUsers(string $query, int $currentUserId, int $limit, int $offset): array
    {
        if ($query === '') return [];

        $escaped = addcslashes($query, '%_\\');
        $term = "%{$escaped}%";

        $stmt = $this->pdo->prepare("
            SELECT u.id, u.username, u.full_name, up.avatar_url, up.bio, up.location,
                   (SELECT COUNT(*) FROM friends WHERE user_id = ? AND friend_id = u.id) AS is_friend,
                   (SELECT COUNT(*) FROM posts WHERE user_id = u.id) AS post_count,
                   us.total_xp, us.current_streak
            FROM users u
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN user_streaks us ON us.user_id = u.id
            WHERE (u.username LIKE ? OR u.full_name LIKE ?) AND u.id != ?
            ORDER BY u.username ASC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$currentUserId, $term, $term, $currentUserId, $limit, $offset]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function searchSpots(string $query, ?float $lat, ?float $lon, float $radiusKm, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];
        $distanceSelect = '';

        // Haversine distance if coordinates provided
        if ($lat !== null && $lon !== null) {
            $distanceSelect = ",
                (6371 * ACOS(
                    LEAST(1, COS(RADIANS(?)) * COS(RADIANS(w.latitude)) * COS(RADIANS(w.longitude) - RADIANS(?))
                    + SIN(RADIANS(?)) * SIN(RADIANS(w.latitude)))
                )) AS distance_km";
            $params[] = $lat;
            $params[] = $lon;
            $params[] = $lat;
            $conditions[] = "
                (6371 * ACOS(
                    LEAST(1, COS(RADIANS(?)) * COS(RADIANS(w.latitude)) * COS(RADIANS(w.longitude) - RADIANS(?))
                    + SIN(RADIANS(?)) * SIN(RADIANS(w.latitude)))
                )) <= ?";
            $params[] = $lat;
            $params[] = $lon;
            $params[] = $lat;
            $params[] = $radiusKm;
        }

        if ($query !== '') {
            $escaped = addcslashes($query, '%_\\');
            $conditions[] = "(w.name LIKE ? OR w.description LIKE ?)";
            $params[] = "%{$escaped}%";
            $params[] = "%{$escaped}%";
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        $orderBy = ($lat !== null && $lon !== null) ? 'distance_km ASC' : 'w.name ASC';

        $sql = "
            SELECT w.id, w.name, w.type, w.latitude, w.longitude, w.description
                   {$distanceSelect}
            FROM waterbodies w
            {$where}
            ORDER BY {$orderBy}
            LIMIT ? OFFSET ?
        ";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function searchCatches(string $query, string $species, ?string $dateFrom, ?string $dateTo, int $userId, int $limit, int $offset): array
    {
        $conditions = [];
        $params = [];

        // Visibility
        $conditions[] = "(p.visibility = 'public' OR p.user_id = ? OR (p.visibility = 'friends' AND p.user_id IN (SELECT friend_id FROM friends WHERE user_id = ?)))";
        $params[] = $userId;
        $params[] = $userId;

        if ($species !== '') {
            $escaped = addcslashes($species, '%_\\');
            $conditions[] = "fc.fish_species LIKE ?";
            $params[] = "%{$escaped}%";
        }

        if ($query !== '') {
            $escaped = addcslashes($query, '%_\\');
            $conditions[] = "(fc.fish_species LIKE ? OR fc.bait LIKE ?)";
            $params[] = "%{$escaped}%";
            $params[] = "%{$escaped}%";
        }

        if ($dateFrom) { $conditions[] = "fc.catch_date >= ?"; $params[] = $dateFrom; }
        if ($dateTo)   { $conditions[] = "fc.catch_date <= ?"; $params[] = $dateTo;   }

        $where = implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare("
            SELECT fc.*, p.title AS post_title, u.username, up.avatar_url,
                   w.name AS waterbody_name, w.type AS waterbody_type
            FROM fish_catches fc
            JOIN posts p ON fc.post_id = p.id
            JOIN users u ON p.user_id = u.id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            LEFT JOIN waterbodies w ON fc.waterbody_id = w.id
            WHERE {$where}
            ORDER BY fc.catch_date DESC
            LIMIT ? OFFSET ?
        ");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Build a MySQL FULLTEXT boolean-mode query from user input.
     */
    private function buildBooleanQuery(string $input): string
    {
        $words = preg_split('/\s+/', trim($input));
        $parts = [];
        foreach ($words as $word) {
            $clean = preg_replace('/[^\p{L}\p{N}]/u', '', $word);
            if (mb_strlen($clean) >= 2) {
                $parts[] = '+' . $clean . '*';
            }
        }
        return implode(' ', $parts);
    }
}
