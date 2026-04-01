<?php
/**
 * Feed page — delegates data fetching to PostService.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

$userId = $_SESSION['user_id'];
$container = $GLOBALS['container'];
$postService = $container->get(App\Services\PostService::class);

$result = $postService->getFeed($userId, 1, 50);
$posts = $result['posts'];
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= __('home') ?> | FISHINGLORY</title>
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

<?php include __DIR__ . '/../../fe/components/footer.php'; ?>

</body>
</html>
