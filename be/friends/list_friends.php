<?php
session_start();
require '../../config/database.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT u.id, u.username
     FROM friends f
     JOIN users u ON u.id = f.friend_id
     WHERE f.user_id = ?"
);
$stmt->execute([$userId]);
$friends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Friends</title>
    <link href="../../fe/assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="mb-4">My Friends</h3>

    <?php if (!$friends): ?>
        <div class="alert alert-secondary">You have no friends yet</div>
    <?php endif; ?>

    <div class="row">
        <?php foreach ($friends as $f): ?>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <img src="../../fe/assets/img/default-avatar.png" width="80" class="rounded-circle mb-2">
                        <h6><?= htmlspecialchars($f['username']) ?></h6>
                        <a href="../../profile.php?id=<?= $f['id'] ?>" class="btn btn-outline-primary btn-sm">
                            View Profile
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
