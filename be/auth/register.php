<?php
require '../../config/security.php';
secureSession();
require '../../config/database.php';
setSecurityHeaders();

function redirectWithRegisterData(string $error, string $fullName, string $username, string $email): void {
    $query = http_build_query([
        'error' => $error,
        'fullName' => $fullName,
        'username' => $username,
        'email' => $email,
    ]);
    header('Location: ../../fe/auth/register_form.php?' . $query);
    exit();
}

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
    redirectWithRegisterData('empty_fields', $fullName, $username, $email);
}

// Validate email
if (!validateEmail($email)) {
    redirectWithRegisterData('invalid_email', $fullName, $username, $email);
}

// Validate username
if (!validateUsername($username)) {
    redirectWithRegisterData('invalid_username', $fullName, $username, $email);
}

// Validate password strength
if (!validatePassword($password)) {
    redirectWithRegisterData('weak_password', $fullName, $username, $email);
}

if ($password !== $confirm) {
    redirectWithRegisterData('password_mismatch', $fullName, $username, $email);
}

// Check if email already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    redirectWithRegisterData('email_exists', $fullName, $username, $email);
}

// Check if username already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    redirectWithRegisterData('username_exists', $fullName, $username, $email);
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
    
    // Create user profile entry (avatar_url stays NULL – theme-aware default is resolved at display time)
    $stmt = $pdo->prepare("INSERT INTO user_profiles (user_id) VALUES (?)");
    $stmt->execute([$userId]);
    
    // Redirect to login with success message
    header('Location: ../../fe/auth/login_form.php?registered=1');
    exit();
    
} catch (PDOException $e) {
    // Log error (in production, log to file)
    error_log('Registration error: ' . $e->getMessage());
    redirectWithRegisterData('server_error', $fullName, $username, $email);
}
