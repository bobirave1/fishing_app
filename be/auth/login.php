<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

// Rate limiting
if (!checkRateLimit('login', 5, 900)) {
    http_response_code(429);
    header("Location: ../../index.php?login_error=rate_limit");
    exit;
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    header("Location: ../../index.php?login_error=csrf");
    exit;
}

// Collect POST data
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (!validateEmail($email) || empty($password)) {
    header("Location: ../../index.php?login_error=invalid");
    exit;
}

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
unset($_SESSION['rate_limit']); // Clear rate limit on successful login

header("Location: ../../index.php");
exit;
