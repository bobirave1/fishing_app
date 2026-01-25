<?php
session_start();
require '../../config/database.php';
require '../../config/security.php';
setSecurityHeaders();

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

$avatar = $user['avatar_url'] ?? 'fe/assets/img/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - FISHINGLORY</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body data-user-id="<?= $userId ?>" data-csrf-token="<?= generateCsrfToken() ?>">

<!-- Navbar -->
<nav class="navbar navbar-expand navbar-light shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="../../index.php">
            <img src="../assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
            <span class="fw-bold fs-4">FISHINGLORY</span>
        </a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item">
                <a class="btn btn-outline-primary" href="../../index.php">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
            </li>
        </ul>
    </div>
</nav>

<!-- Main Content -->
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, var(--primary-color), var(--primary-light));">
                    <h4 class="mb-0">
                        <i class="fas fa-user-edit"></i> Edit Your Profile
                    </h4>
                    <p class="mb-0 small opacity-75">Update your information and profile picture</p>
                </div>
                <div class="card-body p-4">
                    <!-- Success/Error Messages -->
                    <div id="profileMessage"></div>

                    <form id="editProfileForm" enctype="multipart/form-data">
                        <input type="hidden" name="user_id" value="<?= $userId ?>">
                        
                        <!-- Avatar Section -->
                        <div class="mb-4 text-center">
                            <div class="mb-3">
                                <img id="avatarPreview" src="../../<?= htmlspecialchars($avatar) ?>" 
                                     class="rounded-circle shadow-lg" width="150" height="150" 
                                     style="object-fit: cover; border: 5px solid var(--primary-color);">
                            </div>
                            <div class="mb-2">
                                <label for="avatarInput" class="btn btn-primary btn-sm">
                                    <i class="fas fa-camera"></i> Choose New Avatar
                                </label>
                                <input type="file" id="avatarInput" name="avatar" class="d-none" 
                                       accept="image/*" onchange="previewAvatar(event)">
                            </div>
                            <small class="text-muted d-block">JPG, PNG, GIF, or WebP (max 5MB)</small>
                        </div>

                        <hr class="my-4">

                        <!-- Personal Information -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editFullName" class="form-label fw-semibold">
                                    <i class="fas fa-user text-primary"></i> Full Name
                                </label>
                                <input type="text" id="editFullName" name="full_name" class="form-control" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editUsername" class="form-label fw-semibold">
                                    <i class="fas fa-at text-primary"></i> Username
                                </label>
                                <input type="text" id="editUsername" name="username" class="form-control" 
                                       value="<?= htmlspecialchars($user['username']) ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editEmail" class="form-label fw-semibold">
                                <i class="fas fa-envelope text-primary"></i> Email
                            </label>
                            <input type="email" id="editEmail" class="form-control" 
                                   value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <small class="text-muted">Email cannot be changed</small>
                        </div>

                        <div class="mb-3">
                            <label for="editBio" class="form-label fw-semibold">
                                <i class="fas fa-quote-left text-primary"></i> Bio
                            </label>
                            <textarea id="editBio" name="bio" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself and your fishing experience..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                            <small class="text-muted"><span id="bioCount">0</span>/500 characters</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="editLocation" class="form-label fw-semibold">
                                    <i class="fas fa-map-marker-alt text-primary"></i> Location
                                </label>
                                <input type="text" id="editLocation" name="location" class="form-control" 
                                       placeholder="Where do you fish?" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="editExperience" class="form-label fw-semibold">
                                    <i class="fas fa-graduation-cap text-primary"></i> Experience Level
                                </label>
                                <select id="editExperience" name="experience_level" class="form-select">
                                    <option value="beginner" <?= ($user['experience_level'] ?? 'beginner') === 'beginner' ? 'selected' : '' ?>>
                                        🟢 Beginner - Just starting out
                                    </option>
                                    <option value="advanced" <?= ($user['experience_level'] ?? '') === 'advanced' ? 'selected' : '' ?>>
                                        🟡 Advanced - Several years experience
                                    </option>
                                    <option value="pro" <?= ($user['experience_level'] ?? '') === 'pro' ? 'selected' : '' ?>>
                                        🔴 Pro - Expert level
                                    </option>
                                </select>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Action Buttons -->
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="../../index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Additional Info Card -->
            <div class="card mt-4 border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="fw-bold text-muted mb-3">
                        <i class="fas fa-info-circle"></i> Profile Tips
                    </h6>
                    <ul class="small text-muted mb-0">
                        <li>Use a clear profile picture to help others recognize you</li>
                        <li>Share your fishing experience and favorite techniques in your bio</li>
                        <li>Add your location to connect with local anglers</li>
                        <li>Keep your profile updated to get the most out of FISHINGLORY</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Character counter for bio
const bioTextarea = document.getElementById('editBio');
const bioCount = document.getElementById('bioCount');

function updateBioCount() {
    const length = bioTextarea.value.length;
    bioCount.textContent = length;
    if (length > 500) {
        bioCount.classList.add('text-danger');
        bioTextarea.value = bioTextarea.value.substring(0, 500);
    } else {
        bioCount.classList.remove('text-danger');
    }
}

bioTextarea.addEventListener('input', updateBioCount);
updateBioCount();

// Avatar preview
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        // Check file size
        if (file.size > 5 * 1024 * 1024) {
            alert('File is too large! Maximum size is 5MB.');
            event.target.value = '';
            return;
        }

        // Check file type
        const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Invalid file type! Please use JPG, PNG, GIF, or WebP.');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Form submission
document.getElementById('editProfileForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;

    const formData = new FormData(this);
    formData.append('csrf_token', document.body.dataset.csrfToken);

    try {
        const response = await fetch('../../be/users/edit_profile.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        const messageDiv = document.getElementById('profileMessage');

        if (data.success) {
            messageDiv.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> <strong>Success!</strong> Your profile has been updated.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = '../../index.php';
            }, 2000);
        } else {
            messageDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> ${data.error || 'Failed to update profile'}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }

        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });

    } catch (error) {
        console.error('Error:', error);
        document.getElementById('profileMessage').innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <strong>Error!</strong> Network error. Please try again.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }
});
</script>

</body>
</html>
