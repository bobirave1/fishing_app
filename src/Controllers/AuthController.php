<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\AuthService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/fishing_app/');
        }
        require dirname(__DIR__, 2) . '/fe/auth/login_form.php';
    }

    public function registerForm(): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/fishing_app/');
        }
        require dirname(__DIR__, 2) . '/fe/auth/register_form.php';
    }

    public function login(): void
    {
        if (!checkRateLimit('login', 5, 900)) {
            Response::redirect('/fishing_app/login?login_error=rate_limit');
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            Response::redirect('/fishing_app/login?login_error=csrf');
        }

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!validateEmail($email) || empty($password)) {
            Response::redirect('/fishing_app/login?login_error=invalid&email=' . urlencode($email));
        }

        $service = $this->service(AuthService::class);
        $user = $service->attempt($email, $password);

        if (!$user) {
            Response::redirect('/fishing_app/login?login_error=invalid&email=' . urlencode($email));
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        unset($_SESSION['rate_limit']);

        Response::redirect('/fishing_app/');
    }

    public function register(): void
    {
        if (!checkRateLimit('register', 5, 900)) {
            http_response_code(429);
            exit('Too many registration attempts.');
        }

        if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            exit('Invalid CSRF token');
        }

        $fullName = trim($_POST['fullName'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm = trim($_POST['confirmPassword'] ?? '');

        $redirectBase = '/fishing_app/register?' . http_build_query([
            'fullName' => $fullName,
            'username' => $username,
            'email' => $email,
        ]);

        if (!$fullName || !$username || !$email || !$password || !$confirm) {
            Response::redirect($redirectBase . '&error=empty_fields');
        }
        if (!validateEmail($email)) {
            Response::redirect($redirectBase . '&error=invalid_email');
        }
        if (!validateUsername($username)) {
            Response::redirect($redirectBase . '&error=invalid_username');
        }
        if (!validatePassword($password)) {
            Response::redirect($redirectBase . '&error=weak_password');
        }
        if ($password !== $confirm) {
            Response::redirect($redirectBase . '&error=password_mismatch');
        }

        try {
            $service = $this->service(AuthService::class);
            $service->register($fullName, $username, $email, $password);
            Response::redirect('/fishing_app/login?registered=1');
        } catch (\RuntimeException $e) {
            Response::redirect($redirectBase . '&error=' . $e->getMessage());
        } catch (\Throwable $e) {
            error_log('Registration error: ' . $e->getMessage());
            Response::redirect($redirectBase . '&error=server_error');
        }
    }

    public function logout(): void
    {
        session_destroy();
        Response::redirect('/fishing_app/');
    }
}
