<?php
session_start();
require '../config/database.php';

$userId = $_SESSION['user_id'];
$requestId = (int)$_POST['request_id'];

$stmt = $pdo->prepare(
    "UPDATE friend_requests
     SET status = 'rejected'
     WHERE id = ? AND receiver_id = ?"
);
$stmt->execute([$requestId, $userId]);

header("Location: ../friends/list_requests.php");
exit;
