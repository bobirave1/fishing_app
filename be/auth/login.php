<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

function redirectLoginError(string $errorCode, string $email = ''): void {
    $query = ['login_error' => $errorCode];
    if ($email !== '') {
        $query['email'] = $email;
    }
    header('Location: ../../fe/auth/login_form.php?' . http_build_query($query));
    exit;
}

// Rate limiting
if (!checkRateLimit('login', 5, 900)) {
    http_response_code(429);
    redirectLoginError('rate_limit');
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    redirectLoginError('csrf');
}

// Collect POST data
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validate input
if (!validateEmail($email) || empty($password)) {
    redirectLoginError('invalid', $email);
}

// Check if user exists
$stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    redirectLoginError('invalid', $email);
}

// Login successful
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
unset($_SESSION['rate_limit']); // Clear rate limit on successful login

header("Location: ../../index.php");
exit;
