<?php
session_start();
require '../../config/database.php';

$profileId = (int)($_GET['id'] ?? 0);
$currentUser = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("SELECT id, username, full_name FROM users WHERE id = ?");
$stmt->execute([$profileId]);
$user = $stmt->fetch();

if (!$user) die("User not found");

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
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($user['username']) ?> - Profile</title>
    <link href="../../fe/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body text-center">
            <img src="../../fe/assets/img/default-avatar.png" width="120" class="rounded-circle mb-3">
            <h3><?= htmlspecialchars($user['username']) ?></h3>
            <p class="text-muted"><?= htmlspecialchars($user['full_name']) ?></p>

            <?php if ($currentUser && $currentUser !== $profileId): ?>
                <?php if ($isFriend): ?>
                    <span class="badge bg-success">Friends</span>
                <?php elseif ($isPending): ?>
                    <span class="badge bg-warning text-dark">Request sent</span>
                <?php else: ?>
                    <form action="../friends/send_request.php" method="post">
                        <input type="hidden" name="receiver_id" value="<?= $profileId ?>">
                        <button class="btn btn-primary">Add Friend</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
