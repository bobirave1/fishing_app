<?php
session_start();
require '../../config/database.php';
require '../../config/security.php';
require '../../config/avatar_helper.php';

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isOwnProfile ? 'My Friends' : htmlspecialchars($viewUser['username']) . "'s Friends" ?> - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css">
    <link rel="icon" href="../../fe/assets/img/logo_rounded.png">
    <style>
        .friend-card {
            transition: all 0.3s ease;
        }
        .friend-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15) !important;
        }
    </style>
</head>
<body data-user-id="<?= $_SESSION['user_id'] ?>" data-csrf-token="<?= generateCsrfToken() ?>">

<?php include '../../fe/components/navbar.php'; ?>

<div class="container my-5 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">
            <i class="fas fa-user-friends text-success"></i> 
            <?= $isOwnProfile ? 'My Friends' : htmlspecialchars($viewUser['username']) . "'s Friends" ?>
        </h2>
        <div>
            <?php if (!$isOwnProfile): ?>
                <a href="../users/profile.php?id=<?= $viewUserId ?>" class="btn btn-outline-primary me-2">
                    <i class="fas fa-user"></i> Back to Profile
                </a>
            <?php endif; ?>
            <a href="../../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Home
            </a>
        </div>
    </div>

    <?php if (empty($friends)): ?>
        <div class="card text-center py-5">
            <div class="card-body">
                <i class="fas fa-users fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No friends yet</h4>
                <p class="text-muted"><?= $isOwnProfile ? 'Start connecting with other anglers!' : 'This user has no friends yet.' ?></p>
                <?php if ($isOwnProfile): ?>
                    <a href="../../index.php" class="btn btn-primary">Find Friends</a>
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
                                 class="rounded-circle mb-3" 
                                 width="100" height="100" 
                                 style="object-fit: cover; border: 3px solid #10b981;">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($f['username']) ?></h5>
                            <?php if (!empty($f['full_name'])): ?>
                                <p class="text-muted small mb-3"><?= htmlspecialchars($f['full_name']) ?></p>
                            <?php endif; ?>
                            <a href="../users/profile.php?id=<?= $f['id'] ?>" class="btn btn-primary w-100">
                                <i class="fas fa-user"></i> View Profile
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../fe/assets/js/avatar_helper.js?v=<?= time() ?>"></script>
<script src="../../fe/assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
