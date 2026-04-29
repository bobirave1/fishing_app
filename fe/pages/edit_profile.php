<?php
require '../../config/security.php';
secureSession();
setSecurityHeaders();

require '../../config/database.php';
require '../../config/avatar_helper.php';
// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require '../../config/languages.php'; // Add language support

// Handle language/theme switches via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        http_response_code(400);
        die('Invalid CSRF token');
    }

    $action = $_POST['action'];
    if ($action === 'switch_lang' && isset($_POST['lang'])) {
        $newLang = (string) $_POST['lang'];
        if (in_array($newLang, ['bg', 'en'], true)) {
            $_SESSION['lang'] = $newLang;
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }

    if ($action === 'switch_theme' && isset($_POST['theme'])) {
        $newTheme = (string) $_POST['theme'];
        if (in_array($newTheme, ['light', 'dark'], true)) {
            $_SESSION['theme'] = $newTheme;
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get user profile
$stmt = $pdo->prepare("
    SELECT u.*, up.avatar_url, up.bio, up.location, up.experience_level
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: ../../index.php');
    exit;
}

$avatar = $user['avatar_url'] ?? getDefaultAvatarPath();
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('edit_profile') ?> | FISHINGLORY</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/profile.css?v=<?= assetVersion('fe/assets/css/profile.css') ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body class="d-flex flex-column min-vh-100" data-user-id="<?= $userId ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include '../components/navbar.php'; ?>

<!-- Main Content -->
<div class="container my-4 flex-grow-1">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0 profile-section-card edit-profile-card">
                <div class="card-header edit-profile-header">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit"></i> <?= __('edit_profile') ?>
                    </h4>
                    <p class="mb-0 small opacity-75"><?= __('update_profile_info') ?></p>
                </div>
                <div class="card-body p-4">
                    <!-- Success/Error Messages -->
                    <div id="profileMessage"></div>

                    <form id="editProfileForm" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?= $userId ?>">
                        
                        <!-- Avatar Section -->
                        <div class="mb-4 text-center">
                            <div class="mb-3">
                                <img id="avatarPreview" src="<?= htmlspecialchars(getUserAvatar($avatar)) ?>" 
                                     class="rounded-circle shadow-lg" width="150" height="150" 
                                     style="object-fit: cover; border: 5px solid var(--primary-color);">
                            </div>
                            <div class="mb-2">
                                <label for="avatarInput" class="btn btn-primary btn-sm">
                                    <i class="fas fa-camera"></i> <?= __('choose_avatar') ?>
                                </label>
                                <input type="file" id="avatarInput" name="avatar" class="d-none" 
                                       accept="image/*" onchange="previewAvatar(event)">
                            </div>
                            <small class="text-muted d-block">JPG, PNG, GIF, WebP (<?= __('max') ?> 5MB)</small>
                        </div>

                        <hr class="my-4">

                        <!-- Personal Information -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editFullName" class="form-label fw-semibold">
                                    <i class="fas fa-user text-primary"></i> <?= __('full_name') ?>
                                </label>
                                <input type="text" id="editFullName" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editUsername" class="form-label fw-semibold">
                                    <i class="fas fa-at text-primary"></i> <?= __('username') ?>
                                </label>
                                <input type="text" id="editUsername" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editEmail" class="form-label fw-semibold">
                                <i class="fas fa-envelope text-primary"></i> <?= __('email') ?>
                            </label>
                            <input type="email" id="editEmail" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small class="text-muted"><?= __('email_no_change') ?></small>
                        </div>

                        <div class="mb-3">
                            <label for="editBio" class="form-label fw-semibold">
                                <i class="fas fa-quote-left text-primary"></i> <?= __('bio') ?>
                            </label>
                            <textarea id="editBio" name="bio" class="form-control" rows="4" 
                                      placeholder="<?= __('bio_placeholder') ?>"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small class="text-muted"><span id="bioCount">0</span>/500 <?= __('characters') ?></small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editLocation" class="form-label fw-semibold">
                                    <i class="fas fa-map-marker-alt text-primary"></i> <?= __('location') ?>
                                </label>
                                <input type="text" id="editLocation" name="location" class="form-control" 
                                       placeholder="<?= __('location_placeholder') ?>" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editExperience" class="form-label fw-semibold">
                                    <i class="fas fa-graduation-cap text-primary"></i> <?= __('experience_level') ?>
                                </label>
                                <select id="editExperience" name="experience_level" class="form-select">
                                    <option value="beginner" <?= ($user['experience_level'] ?? 'beginner') === 'beginner' ? 'selected' : '' ?>>
                                        🟢 <?= __('beginner') ?> - <?= __('beginner_desc') ?>
                                    </option>
                                    <option value="advanced" <?= ($user['experience_level'] ?? '') === 'advanced' ? 'selected' : '' ?>>
                                        🟡 <?= __('advanced') ?> - <?= __('advanced_desc') ?>
                                    </option>
                                    <option value="pro" <?= ($user['experience_level'] ?? '') === 'pro' ? 'selected' : '' ?>>
                                        🔴 <?= __('pro') ?> - <?= __('pro_desc') ?>
                                    </option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="../../index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> <?= __('cancel') ?>
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> <?= __('save_changes') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Additional Info Card -->
            <div class="card mt-4 border-0 shadow-sm edit-profile-info-card">
                <div class="card-body">
                    <h6 class="fw-bold text-muted mb-3">
                        <i class="fas fa-info-circle"></i> <?= __('profile_tips') ?>
                    </h6>
                    <ul class="small text-muted mb-0">
                        <li><?= __('tip_clear_picture') ?></li>
                        <li><?= __('tip_share_experience') ?></li>
                        <li><?= __('tip_add_location') ?></li>
                        <li><?= __('tip_keep_updated') ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../assets/js/edit_profile.js?v=<?= assetVersion('fe/assets/js/edit_profile.js') ?>"></script>

<?php include '../components/footer.php'; ?>

</body>
</html>
