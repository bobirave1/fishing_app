<?php
require '../../config/database.php';
require '../../config/security.php';
secureSession();
setSecurityHeaders();

// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require '../../config/languages.php'; // Add language support
require '../../config/actions.php';
handleGlobalActions();

$profileId = (int)($_GET['id'] ?? 0);
$currentUser = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name, u.created_at, up.avatar_url, up.bio, up.location, up.experience_level
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$profileId]);
$user = $stmt->fetch();

if (!$user) die(__('user_not_found'));

// Check friendship
$checkFriend = $pdo->prepare(
    "SELECT 1 FROM friends WHERE user_id = ? AND friend_id = ?"
);
$checkFriend->execute([$currentUser, $profileId]);
$isFriend = $checkFriend->fetch();

// Check pending request
$pending = $pdo->prepare(
    "SELECT 1 FROM friend_requests
     WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'"
);
$pending->execute([$currentUser, $profileId]);
$isPending = $pending->fetch();

// Get user's posts
$posts = [];
if ($isFriend || $currentUser === $profileId) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileId]);
    $posts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? AND visibility = 'public' ORDER BY created_at DESC");
    $stmt->execute([$profileId]);
    $posts = $stmt->fetchAll();
}

// Get friend count
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CASE 
        WHEN user_id = ? THEN friend_id 
        WHEN friend_id = ? THEN user_id 
    END) as count 
    FROM friends 
    WHERE user_id = ? OR friend_id = ?
");
$stmt->execute([$profileId, $profileId, $profileId, $profileId]);
$friendCount = $stmt->fetch()['count'];

// Get friends list (first 6 for preview)
$friends = [];
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.full_name, up.avatar_url
    FROM (
        SELECT friend_id as friend FROM friends WHERE user_id = ?
        UNION
        SELECT user_id as friend FROM friends WHERE friend_id = ?
    ) f
    JOIN users u ON u.id = f.friend
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LIMIT 6
");
$stmt->execute([$profileId, $profileId]);
$friends = $stmt->fetchAll();

require_once '../../config/avatar_helper.php';

// Avatar for the profile being viewed (not current user!)
$profileAvatar = getUserAvatar($user['avatar_url'] ?? null);
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']) ?> | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/profile.css?v=<?= assetVersion('fe/assets/css/profile.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/posts.css?v=<?= assetVersion('fe/assets/css/posts.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="../../fe/assets/img/logo_rounded.png">

</head>
<body class="d-flex flex-column min-vh-100" data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include '../../fe/components/navbar.php'; ?>

<main class="flex-grow-1 container my-5 py-5">
    <!-- Profile Header -->
    <div class="card mb-4 shadow profile-main-card">
        <div class="card-body text-center">
            <img src="<?= htmlspecialchars($profileAvatar) ?>" width="150" height="150" class="rounded-circle mb-3 border border-primary profile-avatar-main">
            <h2 class="fw-bold text-primary"><?= htmlspecialchars($user['username']) ?></h2>
            <p class="text-muted fs-5"><?= htmlspecialchars($user['full_name']) ?></p>
            
            <?php if (!empty($user['location'])): ?>
                <p class="text-muted">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['location']) ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($user['bio'])): ?>
                <p class="text-muted profile-bio">
                    "<?= htmlspecialchars($user['bio']) ?>"
                </p>
            <?php endif; ?>

            <?php if (!empty($user['experience_level'])): ?>
                <p class="text-muted mb-3">
                    <?php
                    $levels = [
                        'beginner' => '🟢 ' . __('beginner'),
                        'advanced' => '🟡 ' . __('advanced'),
                        'pro' => '🔴 ' . __('pro')
                    ];
                    echo $levels[$user['experience_level']] ?? '🟢 ' . __('beginner');
                    ?>
                </p>
            <?php endif; ?>

            <p class="text-muted"><i class="fas fa-calendar-alt"></i> <?= __('joined') ?> <?= date('F Y', strtotime($user['created_at'])) ?></p>
            <div class="profile-stats">
                <div class="profile-stat-item"><strong><?= count($posts) ?></strong> <?= __('posts') ?></div>
                <div class="profile-stat-item"><strong><?= $friendCount ?></strong> <?= __('friends') ?></div>
            </div>
            <?php if ($currentUser && $currentUser !== $profileId): ?>
                <div id="friendActionContainer">
                    <?php if ($isFriend): ?>
                        <span class="badge bg-success fs-6 px-3 py-2"><?= __('friends') ?></span>
                    <?php elseif ($isPending): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2"><?= __('request_sent') ?></span>
                    <?php else: ?>
                        <button onclick="sendFriendRequest(<?= $profileId ?>)" class="btn btn-primary btn-lg" id="addFriendBtn">
                            <i class="fas fa-user-plus"></i> <?= __('add_friend') ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php elseif ($currentUser === $profileId): ?>
                <a href="../../fe/pages/edit_profile.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-edit"></i> <?= __('edit_profile') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="row">
        <div class="col-md-8">
            <!-- Posts Section -->
            <div class="card profile-section-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> <?= __('posts') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <p class="text-center text-muted"><?= __('no_posts') ?></p>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold text-primary"><?= htmlspecialchars($post['title']) ?></h6>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                    <?php if (!empty($post['image'])): ?>
                                        <img src="../../<?= htmlspecialchars($post['image']) ?>" class="img-fluid rounded mb-2" style="max-height: 300px;">
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($post['created_at'])) ?> |
                                        <i class="fas fa-eye"></i> <?= ucfirst($post['visibility']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Friends Sidebar -->
            <div class="card profile-section-card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users"></i> <?= __('friends') ?></h5>
                    <span class="badge bg-light text-success"><?= $friendCount ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($friends)): ?>
                        <p class="text-center text-muted">
                            <i class="fas fa-user-friends fa-2x mb-2 d-block"></i>
                            <?= __('no_friends') ?>
                        </p>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($friends as $friend): 
                                $friendAvatar = getUserAvatar($friend['avatar_url'] ?? null);
                            ?>
                                <div class="col-6">
                                    <a href="profile.php?id=<?= $friend['id'] ?>" class="text-decoration-none">
                                        <div class="text-center p-2 hover-card">
                                            <img src="<?= htmlspecialchars($friendAvatar) ?>" 
                                                 class="rounded-circle mb-2" 
                                                 width="60" height="60" 
                                                 style="object-fit: cover; border: 2px solid #10b981;">
                                            <div class="small fw-bold text-truncate"><?= htmlspecialchars($friend['username']) ?></div>
                                            <?php if (!empty($friend['full_name'])): ?>
                                                <div class="small text-muted text-truncate"><?= htmlspecialchars($friend['full_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($friendCount > 6): ?>
                            <div class="text-center mt-3">
                                <a href="../friends/list_friends.php?user_id=<?= $profileId ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-arrow-right"></i> <?= __('view_all_friends') ?> (<?= $friendCount ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../../fe/components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../fe/assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../../fe/assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>

</body>
</html>
