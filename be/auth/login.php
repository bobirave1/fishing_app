<?php
/**
 * Login endpoint (legacy direct access).
 * Delegates to AuthService via DI container.
 */
require_once __DIR__ . '/../../config/bootstrap.php';

use App\Services\AuthService;
use App\Core\Logger;

/** @var \App\Core\Container $container */
$container = $GLOBALS['container'];
$logger = $container->get(Logger::class);

function redirectLoginError(string $errorCode, string $email = ''): void
{
    $query = ['login_error' => $errorCode];
    if ($email !== '') {
        $query['email'] = $email;
    }
    header('Location: ../../fe/auth/login_form.php?' . http_build_query($query));
    exit;
}

// Rate limiting
if (!checkRateLimit('login', 5, 900)) {
    $logger->warning('Login rate limit exceeded from {ip}', ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    http_response_code(429);
    redirectLoginError('rate_limit');
}

// CSRF Protection
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    redirectLoginError('csrf');
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (!validateEmail($email) || empty($password)) {
    redirectLoginError('invalid', $email);
}

$authService = $container->get(AuthService::class);
$user = $authService->attempt($email, $password);

if (!$user) {
    $logger->info('Failed login attempt for {email}', ['email' => $email]);
    redirectLoginError('invalid', $email);
}

session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
unset($_SESSION['rate_limit']);

$logger->info('User {username} logged in', ['username' => $user['username']]);

header("Location: ../../index.php");
exit;
