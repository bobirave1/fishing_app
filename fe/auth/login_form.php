<?php
session_start();
require_once '../../config/security.php';
secureSession();

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/auth_forms.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="../assets/img/logo_rounded.png" alt="Logo" width="80" height="80" class="mb-3">
        <h3 class="mb-0">Welcome Back!</h3>
        <p class="mb-0 mt-2">Login to FISHINGLORY</p>
    </div>
    
    <div class="login-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> Invalid email or password
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['login_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php if ($_GET['login_error'] === 'rate_limit'): ?>
                    Too many attempts. Please try again later.
                <?php elseif ($_GET['login_error'] === 'csrf'): ?>
                    Security error. Please refresh and try again.
                <?php else: ?>
                    Invalid email or password
                <?php endif; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Registration successful! Please login.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="../../be/auth/login.php">
            <?= getCsrfField() ?>
            
            <div class="mb-3">
                <label for="email" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required autofocus>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label"><i class="fas fa-lock"></i> Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                <label class="form-check-label" for="remember">Remember me</label>
            </div>
            
            <button type="submit" class="btn w-100 custom-btn mb-3">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <div class="text-center">
            <p class="mb-2">Don't have an account? 
                <a href="register_form.php" class="text-decoration-none fw-bold">Sign up here!</a>
            </p>
            <a href="../../index.php" class="text-muted text-decoration-none">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
