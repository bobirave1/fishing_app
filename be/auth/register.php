<?php
/**
 * Registration endpoint (legacy direct access).
 * Delegates to AuthService via DI container.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

use App\Services\AuthService;
use App\Core\Logger;

/** @var \App\Core\Container $container */
$container = $GLOBALS['container'];
$logger = $container->get(Logger::class);

function redirectWithRegisterData(string $error, string $fullName, string $username, string $email): void
{
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
    $logger->warning('Register rate limit exceeded from {ip}', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    http_response_code(429);
    exit('Too many registration attempts. Please try again in 15 minutes.');
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    http_response_code(403);
    exit('Invalid security token. Please refresh the page and try again.');
}

$fullName = trim($_POST['fullName'] ?? '');
$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$confirm  = trim($_POST['confirmPassword'] ?? '');

if (!$fullName || !$username || !$email || !$password || !$confirm) {
    redirectWithRegisterData('empty_fields', $fullName, $username, $email);
}
if (!validateEmail($email)) {
    redirectWithRegisterData('invalid_email', $fullName, $username, $email);
}
if (!validateUsername($username)) {
    redirectWithRegisterData('invalid_username', $fullName, $username, $email);
}
if (!validatePassword($password)) {
    redirectWithRegisterData('weak_password', $fullName, $username, $email);
}
if ($password !== $confirm) {
    redirectWithRegisterData('password_mismatch', $fullName, $username, $email);
}

try {
    $authService = $container->get(AuthService::class);
    $authService->register($fullName, $username, $email, $password);
    $logger->info('New user registered: {username}', ['username' => $username]);
    header('Location: ../../fe/auth/login_form.php?registered=1');
    exit();
} catch (\RuntimeException $e) {
    redirectWithRegisterData($e->getMessage(), $fullName, $username, $email);
} catch (\Throwable $e) {
    $logger->error('Registration error', ['exception' => $e]);
    redirectWithRegisterData('server_error', $fullName, $username, $email);
}
