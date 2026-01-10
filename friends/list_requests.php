<?php
session_start();
require '../config/database.php';

$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT fr.id, u.username
     FROM friend_requests fr
     JOIN users u ON u.id = fr.sender_id
     WHERE fr.receiver_id = ? AND fr.status = 'pending'"
);
$stmt->execute([$userId]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Friend Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <h3 class="mb-4">Friend Requests</h3>

    <?php if (!$requests): ?>
        <div class="alert alert-info">No pending requests</div>
    <?php endif; ?>

    <?php foreach ($requests as $r): ?>
        <div class="card mb-3">
            <div class="card-body d-flex justify-content-between align-items-center">
                <strong><?= htmlspecialchars($r['username']) ?></strong>

                <div>
                    <form action="accept_request.php" method="post" class="d-inline">
                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                        <button class="btn btn-success btn-sm">Accept</button>
                    </form>

                    <form action="reject_request.php" method="post" class="d-inline">
                        <input type="hidden" name="request_id" value="<?= $r['id'] ?>">
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
