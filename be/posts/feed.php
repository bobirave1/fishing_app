<?php
session_start();
require '../config/database.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
SELECT p.*, u.username
FROM posts p
JOIN users u ON u.id = p.user_id
WHERE
    p.visibility = 'public'
 OR (p.visibility = 'friends' AND p.user_id IN (
        SELECT friend_id FROM friends WHERE user_id = ?
    ))
 OR p.user_id = ?
ORDER BY p.created_at DESC
");

$stmt->execute([$userId, $userId]);
$posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Feed</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../fe/assets/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../fe/assets/css/posts.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../fe/assets/css/components.css?v=<?= time() ?>">
</head>
<body>

<div class="container mt-4">
    <h3 class="mb-4">📰 Feed</h3>

<?php foreach ($posts as $p): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h5><?= htmlspecialchars($p['title']) ?></h5>

            <strong><?= htmlspecialchars($p['username']) ?></strong>

            <p class="mt-2"><?= nl2br(htmlspecialchars($p['content'])) ?></p>

            <?php if (!empty($p['image'])): ?>
                <img src="../<?= htmlspecialchars($p['image']) ?>"
                     class="img-fluid rounded mb-2">
            <?php endif; ?>

            <small class="text-muted">
                <?= $p['created_at'] ?> |
                <?= strtoupper($p['visibility']) ?>
            </small>
        </div>
    </div>
<?php endforeach; ?>

</div>

<?php include '../../fe/components/footer.php'; ?>

</body>
</html>
