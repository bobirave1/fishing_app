<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
require '../../config/avatar_helper.php';
require '../../config/languages.php'; // Add language support

// Set default language to Bulgarian for diploma project
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}

// Handle language/theme switches via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'switch_lang' && isset($_POST['lang'])) {
        $_SESSION['lang'] = $_POST['lang'];
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    } elseif ($_POST['action'] === 'switch_theme' && isset($_POST['theme'])) {
        $_SESSION['theme'] = $_POST['theme'];
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT fr.id, u.id as user_id, u.username, u.full_name, up.avatar_url
     FROM friend_requests fr
     JOIN users u ON u.id = fr.sender_id
     LEFT JOIN user_profiles up ON u.id = up.user_id
     WHERE fr.receiver_id = ? AND fr.status = 'pending'"
);
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Friend Requests | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../fe/assets/css/navbar.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../fe/assets/css/components.css?v=<?= time() ?>">
    <link rel="stylesheet" href="../../fe/assets/css/friends_list.css?v=<?= time() ?>">
    <link rel="icon" href="../../fe/assets/img/logo_rounded.png">
</head>
<body class="requests-page" data-user-id="<?= $_SESSION['user_id'] ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include '../../fe/components/navbar.php'; ?>

<div class="container my-5 py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold page-title">
            <i class="fas fa-user-plus text-warning"></i> Friend Requests
            <?php if (!empty($requests)): ?>
                <span class="badge bg-warning text-dark"><?= count($requests) ?></span>
            <?php endif; ?>
        </h2>
        <div>
            <a href="list_friends.php" class="btn btn-outline-success me-2">
                <i class="fas fa-user-friends"></i> My Friends
            </a>
            <a href="../../index.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Home
            </a>
        </div>
    </div>

    <?php if (empty($requests)): ?>
        <div class="card text-center py-5 shadow-sm requests-empty">
            <div class="card-body">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4 class="text-muted">No pending requests</h4>
                <p class="text-muted">You're all caught up!</p>
                <a href="../../index.php" class="btn btn-primary mt-3">
                    <i class="fas fa-home"></i> Go to Home
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($requests as $r): 
                $senderAvatar = getUserAvatar($r['avatar_url'] ?? null);
            ?>
                <div class="col-12">
                    <div class="card shadow-sm request-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <img src="<?= htmlspecialchars($senderAvatar) ?>" 
                                         class="rounded-circle request-avatar" 
                                         width="60" height="60" 
                                         >
                                    <div>
                                        <h5 class="mb-0 fw-bold"><?= htmlspecialchars($r['username']) ?></h5>
                                        <?php if (!empty($r['full_name'])): ?>
                                            <p class="text-muted mb-0 small"><?= htmlspecialchars($r['full_name']) ?></p>
                                        <?php endif; ?>
                                        <a href="../users/profile.php?id=<?= $r['user_id'] ?>" class="text-primary text-decoration-none small">
                                            <i class="fas fa-eye"></i> View Profile
                                        </a>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <form action="accept_request.php" method="post" class="d-inline">
                                        <?= getCsrfField() ?>
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-success">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                    </form>

                                    <form action="reject_request.php" method="post" class="d-inline">
                                        <?= getCsrfField() ?>
                                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                                        <button class="btn btn-outline-danger">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../fe/assets/js/avatar_helper.js?v=<?= time() ?>"></script>
<script src="../../fe/assets/js/app.js?v=<?= time() ?>"></script>
</body>
</html>
