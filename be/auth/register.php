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

// Basic validation
if (!$fullName || !$username || !$email || !$password || !$confirm) {
    http_response_code(400);
    exit("Please fill in all fields.");
}

// Validate email
if (!validateEmail($email)) {
    http_response_code(400);
    exit('Please provide a valid email address.');
}

// Validate username
if (!validateUsername($username)) {
    http_response_code(400);
    exit('Username must be 3-20 characters and contain only letters, numbers, and underscores.');
}

// Validate password strength
if (!validatePassword($password)) {
    http_response_code(400);
    exit('Password must be at least 8 characters long and contain at least one letter and one number.');
}

if ($password !== $confirm) {
    http_response_code(400);
    exit("Passwords do not match.");
}

// Check if email already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    http_response_code(409);
    exit('Email already registered.');
}

// Check if username already exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    http_response_code(409);
    exit('Username already taken.');
}

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user into database
$stmt = $pdo->prepare(
    "INSERT INTO users (full_name, username, email, password_hash) VALUES (?, ?, ?, ?)"
);

try {
    $stmt->execute([$fullName, $username, $email, $hash]);
} catch (PDOException $e) {
    // Log error (in production, log to file)
    error_log('Registration error: ' . $e->getMessage());
    http_response_code(500);
    exit('Registration failed. Please try again later.');
}

// Redirect to login
header("Location: ../../index.php");
exit;
