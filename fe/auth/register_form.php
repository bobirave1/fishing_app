<?php
require_once '../../config/security.php';
secureSession();
setSecurityHeaders();

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
    <title>Register - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../assets/css/auth_forms.css?v=<?= assetVersion('fe/assets/css/auth_forms.css') ?>">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <img src="../assets/img/logo_rounded.png" alt="Logo" width="80" height="80" class="mb-3">
        <h3 class="mb-0">Join FISHINGLORY</h3>
        <p class="mb-0 mt-2">Create your account</p>
    </div>
    
    <div class="register-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                switch($_GET['error']) {
                    case 'empty_fields':
                        echo 'Please fill in all fields';
                        break;
                    case 'invalid_email':
                        echo 'Please provide a valid email address';
                        break;
                    case 'invalid_username':
                        echo 'Username must be 3-20 characters (letters, numbers, underscores only)';
                        break;
                    case 'weak_password':
                        echo 'Password must be at least 8 characters and contain both letters and numbers';
                        break;
                    case 'password_mismatch':
                        echo 'Passwords do not match';
                        break;
                    case 'username_exists':
                        echo 'Username already taken';
                        break;
                    case 'email_exists':
                        echo 'Email already registered';
                        break;
                    case 'server_error':
                        echo 'Registration failed. Please try again';
                        break;
                    default:
                        echo 'Registration failed. Please try again';
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="../../be/auth/register.php"> 
            <?= getCsrfField() ?>
            
            <div class="mb-3">
                <label for="regFullName" class="form-label"><i class="fas fa-user"></i> Full Name</label>
                <input type="text" class="form-control" id="regFullName" name="fullName" 
                       placeholder="Enter your full name" required autofocus>
            </div>

            <div class="mb-3">
                <label for="regEmail" class="form-label"><i class="fas fa-envelope"></i> Email</label>
                <input type="email" class="form-control" id="regEmail" name="email" 
                       placeholder="Enter your email" required>
            </div>

            <div class="mb-3">
                <label for="regUsername" class="form-label"><i class="fas fa-at"></i> Username</label>
                <input type="text" class="form-control" id="regUsername" name="username" 
                       placeholder="Choose a username" pattern="[a-zA-Z0-9_]{3,20}" 
                       title="3-20 characters, letters, numbers, and underscores only" required>
                <div class="form-text">3-20 characters, letters, numbers, and underscores only</div>
            </div>

            <div class="mb-3">
                <label for="regPassword" class="form-label"><i class="fas fa-lock"></i> Password</label>
                <input type="password" class="form-control" id="regPassword" name="password" 
                       placeholder="Create a password" minlength="8" required>
                <div class="form-text">At least 8 characters with letters and numbers</div>
            </div>

            <div class="mb-3">
                <label for="regConfirmPassword" class="form-label"><i class="fas fa-lock"></i> Confirm Password</label>
                <input type="password" class="form-control" id="regConfirmPassword" name="confirmPassword" 
                       placeholder="Confirm your password" required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="regTerms" name="terms" required>
                <label for="regTerms" class="form-check-label small">
                    I agree to the <a href="#" class="text-decoration-none">Terms & Conditions</a>
                </label>
            </div>

            <button type="submit" class="btn w-100 custom-btn mb-3">
                <i class="fas fa-user-plus"></i> Register
            </button>
        </form>

        <div class="text-center">
            <p class="mb-2">Already have an account? 
                <a href="login_form.php" class="text-decoration-none fw-bold">Login here!</a>
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
