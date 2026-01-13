<?php
session_start();
require '../../config/database.php';

$sender = $_SESSION['user_id'];
$receiver = (int)$_POST['receiver_id'];

if ($sender === $receiver) {
    die("You cannot add yourself.");
}

$stmt = $pdo->prepare(
    "INSERT INTO friend_requests (sender_id, receiver_id)
     VALUES (?, ?)"
);

try {
    $stmt->execute([$sender, $receiver]);
    header("Location: ../../profile.php?id=$receiver");
} catch (PDOException $e) {
    die("Request already sent.");
}
