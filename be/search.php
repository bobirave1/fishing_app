<?php
require '../config/security.php';
secureSession();
require '../config/database.php';
require '../config/avatar_helper.php';
setSecurityHeaders();

$query = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'all'; // all, users, posts, spots

header('Content-Type: application/json');

if (strlen($query) < 2) {
    exit(json_encode([
        'success' => false,
        'error' => 'Search query too short'
    ]));
}

if (strlen($query) > 100) {
    exit(json_encode([
        'success' => false,
        'error' => 'Search query too long'
    ]));
}

$results = [
    'users' => [],
    'posts' => [],
    'spots' => []
];

// Search users
if ($type === 'all' || $type === 'users') {
    $stmt = $pdo->prepare("
        SELECT u.id, u.username, u.full_name, up.avatar_url,
               (SELECT COUNT(*) FROM friends WHERE user_id = ? AND friend_id = u.id) as is_friend
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE (u.username LIKE ? OR u.full_name LIKE ?)
        AND u.id != ?
        ORDER BY u.username ASC
        LIMIT 10
    ");
    $searchTerm = '%' . $query . '%';
    $currentUserId = $_SESSION['user_id'] ?? 0;
    $stmt->execute([$currentUserId, $searchTerm, $searchTerm, $currentUserId]);
    $users = $stmt->fetchAll();
    
    // Fix avatar paths
    foreach ($users as &$user) {
        if (!$user['avatar_url']) {
            $user['avatar_url'] = getDefaultAvatarPath();
        }
    }
    $results['users'] = $users;
}

// Search posts
if ($type === 'all' || $type === 'posts') {
    $userId = $_SESSION['user_id'] ?? 0;
    
    $stmt = $pdo->prepare("
        SELECT p.id, p.title, p.content, p.user_id, u.username, up.avatar_url, p.created_at
        FROM posts p
        JOIN users u ON p.user_id = u.id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE (p.title LIKE ? OR p.content LIKE ?)
        AND (p.visibility = 'public' OR p.user_id = ? OR p.user_id IN (
            SELECT friend_id FROM friends WHERE user_id = ?
        ))
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm, $userId, $userId]);
    $results['posts'] = $stmt->fetchAll();
}

// Search waterbodies (fishing spots)
if ($type === 'all' || $type === 'spots') {
    $stmt = $pdo->prepare("
        SELECT id, name, type, latitude, longitude, description
        FROM waterbodies
        WHERE name LIKE ? OR description LIKE ?
        LIMIT 10
    ");
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm, $searchTerm]);
    $results['spots'] = $stmt->fetchAll();
}

exit(json_encode([
    'success' => true,
    'query' => htmlspecialchars($query),
    'results' => $results
]));
