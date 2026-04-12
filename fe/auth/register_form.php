<?php
require_once '../../config/security.php';
secureSession();
// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require_once '../../config/languages.php';
setSecurityHeaders();

$prefillFullName = htmlspecialchars(trim((string)($_GET['fullName'] ?? '')), ENT_QUOTES, 'UTF-8');
$prefillEmail = htmlspecialchars(trim((string)($_GET['email'] ?? '')), ENT_QUOTES, 'UTF-8');
$prefillUsername = htmlspecialchars(trim((string)($_GET['username'] ?? '')), ENT_QUOTES, 'UTF-8');

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('sign_up') ?> | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="stylesheet" href="../assets/css/auth_forms.css?v=<?= assetVersion('fe/assets/css/auth_forms.css') ?>">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body class="d-flex flex-column min-vh-100 auth-page">
<main class="flex-grow-1 auth-main">
<div class="register-card">
    <div class="register-header">
        <img src="../assets/img/logo_rounded.png" alt="Logo" width="80" height="80" class="mb-3">
        <h3 class="mb-0"><?= __('join_title') ?></h3>
        <p class="mb-0 mt-2"><?= __('create_account') ?></p>
    </div>
    
    <div class="register-body">
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <?php 
                switch($_GET['error']) {
                    case 'empty_fields':
                        echo __('err_empty_fields');
                        break;
                    case 'invalid_email':
                        echo __('err_invalid_email');
                        break;
                    case 'invalid_username':
                        echo __('err_invalid_username');
                        break;
                    case 'weak_password':
                        echo __('err_weak_password');
                        break;
                    case 'password_mismatch':
                        echo __('err_password_mismatch');
                        break;
                    case 'username_exists':
                        echo __('err_username_exists');
                        break;
                    case 'email_exists':
                        echo __('err_email_exists');
                        break;
                    case 'server_error':
                        echo __('err_server');
                        break;
                    default:
                        echo __('err_server');
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="../../be/auth/register.php" id="registerForm"> 
            <?= getCsrfField() ?>
            
            <div class="mb-3">
                <label for="regFullName" class="form-label"><i class="fas fa-user"></i> <?= __('full_name') ?></label>
                <input type="text" class="form-control" id="regFullName" name="fullName" 
                      placeholder="<?= __('full_name') ?>" value="<?= $prefillFullName ?>" required autofocus>
            </div>

            <div class="mb-3">
                <label for="regEmail" class="form-label"><i class="fas fa-envelope"></i> <?= __('email') ?></label>
                <input type="email" class="form-control" id="regEmail" name="email" 
                      placeholder="<?= __('email') ?>" value="<?= $prefillEmail ?>" required>
            </div>

            <div class="mb-3">
                <label for="regUsername" class="form-label"><i class="fas fa-at"></i> <?= __('username') ?></label>
                <input type="text" class="form-control" id="regUsername" name="username" 
                      placeholder="<?= __('username') ?>" value="<?= $prefillUsername ?>" pattern="[a-zA-Z0-9_]{3,20}" 
                       title="<?= __('username_hint') ?>" required>
                <div class="form-text"><?= __('username_hint') ?></div>
            </div>

            <div class="mb-3">
                <label for="regPassword" class="form-label"><i class="fas fa-lock"></i> <?= __('password') ?></label>
                <input type="password" class="form-control" id="regPassword" name="password" 
                       placeholder="<?= __('password') ?>" minlength="8" required>
                <div class="form-text"><?= __('password_hint') ?></div>
            </div>

            <div class="mb-3">
                <label for="regConfirmPassword" class="form-label"><i class="fas fa-lock"></i> <?= __('confirm_password') ?></label>
                <input type="password" class="form-control" id="regConfirmPassword" name="confirmPassword" 
                       placeholder="<?= __('confirm_password') ?>" required>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="regTerms" name="terms" required>
                <label for="regTerms" class="form-check-label small">
                    <?= __('agree_to') ?> <a href="#" class="text-decoration-none"><?= __('terms_conditions') ?></a>
                </label>
            </div>

            <button type="submit" class="btn w-100 custom-btn mb-3">
                <i class="fas fa-user-plus"></i> <?= __('register') ?>
            </button>
        </form>

        <div class="text-center">
            <p class="mb-2"><?= __('already_have_account') ?> 
                <a href="login_form.php" class="text-decoration-none fw-bold"><?= __('login_here_excl') ?></a>
            </p>
            <a href="../../index.php" class="text-muted text-decoration-none">
                <i class="fas fa-home"></i> <?= __('home') ?>
            </a>
        </div>
    </div>
</div>
</main>

<?php include '../components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script>
    // Enable bubble animation on register form submission
    document.getElementById('registerForm')?.addEventListener('submit', function() {
        sessionStorage.setItem('bubbleTransition', 'true');
    });
</script>
</body>
</html>
