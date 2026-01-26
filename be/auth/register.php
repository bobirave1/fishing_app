<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

// Rate limiting
if (!checkRateLimit('register', 5, 900)) {
    http_response_code(429);
    exit('Too many registration attempts. Please try again in 15 minutes.');
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid security token. Please refresh the page and try again.');
}

// Collect POST data
$fullName = trim($_POST['fullName'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirmPassword'] ?? '';

// Remove any accidental whitespace from passwords
$password = trim($password);
$confirm = trim($confirm);

// Basic validation
if (!$fullName || !$username || !$email || !$password || !$confirm) {
    header('Location: ../../fe/auth/register_form.php?error=empty_fields');
    exit();
}

// Validate email
if (!validateEmail($email)) {
    header('Location: ../../fe/auth/register_form.php?error=invalid_email');
    exit();
}

// Validate username
if (!validateUsername($username)) {
    header('Location: ../../fe/auth/register_form.php?error=invalid_username');
    exit();
}

// Validate password strength
if (!validatePassword($password)) {
    header('Location: ../../fe/auth/register_form.php?error=weak_password');
    exit();
}

if ($password !== $confirm) {
    header('Location: ../../fe/auth/register_form.php?error=password_mismatch');
    exit();
}

// Check if email already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    header('Location: ../../fe/auth/register_form.php?error=email_exists');
    exit();
}

// Check if username already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    header('Location: ../../fe/auth/register_form.php?error=username_exists');
    exit();
}

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)"
);

try {
    $stmt->execute([$fullName, $username, $email, $hash]);
    $userId = $pdo->lastInsertId();
    
    // Create user profile entry with default avatar
    $defaultAvatar = 'fe/assets/img/default-avatar.png';
    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id, avatar_url) VALUES (?, ?)");
    $stmt->execute([$userId, $defaultAvatar]);
    
    // Redirect to login with success message
    header('Location: ../../fe/auth/login_form.php?registered=1');
    exit();
    
} catch (PDOException $e) {
    // Log error (in production, log to file)
    error_log('Registration error: ' . $e->getMessage());
    header('Location: ../../fe/auth/register_form.php?error=server_error');
    exit();
}
