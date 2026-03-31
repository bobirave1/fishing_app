<?php
require '../../config/security.php';
secureSession();
setSecurityHeaders();

require '../../config/database.php';
require '../../config/avatar_helper.php';
// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require '../../config/languages.php'; // Add language support
require '../../config/actions.php';
handleGlobalActions();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$viewUserId = (int)($_GET['user_id'] ?? $currentUserId); // View someone else's friends or own

// Get user info whose friends we're viewing
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$viewUserId]);
$viewUser = $stmt->fetch();

if (!$viewUser) {
    header('Location: ../../index.php');
    exit;
}

$isOwnProfile = ($currentUserId === $viewUserId);

// Get friends list
$stmt = $pdo->prepare(
    "SELECT DISTINCT u.id, u.username, u.full_name, up.avatar_url
     FROM (
         SELECT friend_id as friend FROM friends WHERE user_id = ?
         UNION
         SELECT user_id as friend FROM friends WHERE friend_id = ?
     ) f
     JOIN users u ON u.id = f.friend
     LEFT JOIN user_profiles up ON u.id = up.user_id"
);
$stmt->execute([$viewUserId, $viewUserId]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isOwnProfile ? __('my_friends') : htmlspecialchars($viewUser['username']) . " - " . __('friends') ?> | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/friends_list.css?v=<?= assetVersion('fe/assets/css/friends_list.css') ?>">
    <link rel="icon" href="../../fe/assets/img/logo_rounded.png">
</head>
<body class="friends-page d-flex flex-column min-vh-100" data-user-id="<?= $_SESSION['user_id'] ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include '../../fe/components/navbar.php'; ?>

<main class="flex-grow-1 container my-5 py-5">
    <?php if (isset($_SESSION['friend_flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['friend_flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['friend_flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['friend_flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['friend_flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['friend_flash_error']); ?>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold page-title">
            <i class="fas fa-user-friends text-success"></i> 
            <?= $isOwnProfile ? __('my_friends') : htmlspecialchars($viewUser['username']) . " - " . __('friends') ?>
            <?php if (!empty($friends)): ?>
                <span class="badge bg-success"><?= count($friends) ?></span>
            <?php endif; ?>
        </h2>
        <div>
            <?php if ($isOwnProfile): ?>
                <a href="list_requests.php" class="btn btn-outline-warning me-2">
                    <i class="fas fa-user-plus"></i> <?= __('requests') ?>
                </a>
            <?php endif; ?>
            <?php if (!$isOwnProfile): ?>
                <a href="../users/profile.php?id=<?= $viewUserId ?>" class="btn btn-outline-primary me-2">
                    <i class="fas fa-user"></i> <?= __('back_to_profile') ?>
                </a>
            <?php endif; ?>
            <a href="../../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= __('home') ?>
            </a>
        </div>
    </div>

    <?php if (empty($friends)): ?>
        <div class="card text-center py-5 shadow-sm friends-empty">
            <div class="card-body">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted"><?= __('no_friends') ?></h4>
                <p class="text-muted"><?= $isOwnProfile ? __('start_connecting') : __('no_friends') ?></p>
                <?php if ($isOwnProfile): ?>
                    <a href="../../index.php" class="btn btn-primary mt-2">
                        <i class="fas fa-search"></i> <?= __('find_friends') ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($friends as $f): 
                $friendAvatar = getUserAvatar($f['avatar_url'] ?? null);
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm friend-card">
                        <div class="card-body text-center">
                            <img src="<?= htmlspecialchars($friendAvatar) ?>" 
                                   class="rounded-circle mb-3 friend-avatar-lg" 
                                 width="100" height="100" 
                                   >
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($f['username']) ?></h5>
                            <?php if (!empty($f['full_name'])): ?>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($f['full_name']) ?></p>
                            <?php endif; ?>
                            <a href="../users/profile.php?id=<?= $f['id'] ?>" class="btn btn-primary w-100 mb-2">
                                <i class="fas fa-user"></i> <?= __('view_profile') ?>
                            </a>
                            <?php if ($isOwnProfile): ?>
                                <form action="remove_friend.php" method="POST" class="w-100" onsubmit="return confirm('<?= htmlspecialchars(__('remove_friend_confirm'), ENT_QUOTES, 'UTF-8') ?>');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                    <input type="hidden" name="friend_id" value="<?= (int) $f['id'] ?>">
                                    <input type="hidden" name="return_to" value="friends_list">
                                    <button type="submit" class="btn btn-outline-danger w-100">
                                        <i class="fas fa-user-minus"></i> <?= __('remove_friend') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<?php include '../../fe/components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../fe/assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../../fe/assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
</body>
</html>
