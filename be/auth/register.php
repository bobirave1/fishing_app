<?php
require '../../config/database.php';

// Collect POST data
$fullName = trim($_POST['fullName'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirmPassword'] ?? '';

// Basic validation
if (!$fullName || !$username || !$email || !$password || !$confirm) {
    die("Please fill in all fields.");
}

if ($password !== $confirm) {
    die("Passwords do not match.");
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
    die("Registration failed: " . $e->getMessage());
}

// Redirect to login
header("Location: ../../index.php");
exit;
