<?php
session_start();
require '../../config/database.php';

// Collect POST data
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Check if user exists
$stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Redirect back with error
    header("Location: ../../index.php?login_error=1");
    exit;
}

// Login successful
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];

header("Location: ../../index.php");
exit;
