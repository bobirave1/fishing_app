<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
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
    http_response_code(404);
    exit;
}

$avatar = $user['avatar_url'] ?? 'fe/assets/img/default-avatar.png';
?>

<form id="editProfileForm" enctype="multipart/form-data">
    <input type="hidden" name="user_id" value="<?= $userId ?>">
    
    <!-- Avatar Section -->
    <div class="mb-4 text-center">
        <div class="mb-3">
            <img id="avatarPreview" src="<?= htmlspecialchars($avatar) ?>" 
                 class="rounded-circle" width="120" height="120" style="object-fit: cover; border: 4px solid #1976d2;">
        </div>
        <label for="avatarInput" class="form-label d-block fw-bold mb-2">Upload Avatar</label>
        <input type="file" id="avatarInput" name="avatar" class="form-control form-control-sm" accept="image/*" 
               onchange="previewAvatar(event)">
        <small class="text-muted d-block mt-1">JPG, PNG, GIF, or WebP (max 5MB)</small>
    </div>

    <hr>

    <div class="mb-3">
        <label for="editFullName" class="form-label"><i class="fas fa-user"></i> Full Name</label>
        <input type="text" id="editFullName" name="full_name" class="form-control" 
               value="<?= htmlspecialchars($user['full_name']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="editUsername" class="form-label"><i class="fas fa-at"></i> Username</label>
        <input type="text" id="editUsername" name="username" class="form-control" 
               value="<?= htmlspecialchars($user['username']) ?>" required>
    </div>

    <div class="mb-3">
        <label for="editBio" class="form-label"><i class="fas fa-quote-left"></i> Bio</label>
        <textarea id="editBio" name="bio" class="form-control" rows="3" 
                  placeholder="Tell us about yourself..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
    </div>

    <div class="mb-3">
        <label for="editLocation" class="form-label"><i class="fas fa-map-marker-alt"></i> Location</label>
        <input type="text" id="editLocation" name="location" class="form-control" 
               placeholder="Where do you fish?" value="<?= htmlspecialchars($user['location'] ?? '') ?>">
    </div>

    <div class="mb-3">
        <label for="editExperience" class="form-label"><i class="fas fa-graduation-cap"></i> Experience Level</label>
        <select id="editExperience" name="experience_level" class="form-select">
            <option value="beginner" <?= ($user['experience_level'] ?? 'beginner') === 'beginner' ? 'selected' : '' ?>>🟢 Beginner</option>
            <option value="advanced" <?= ($user['experience_level'] ?? '') === 'advanced' ? 'selected' : '' ?>>🟡 Advanced</option>
            <option value="pro" <?= ($user['experience_level'] ?? '') === 'pro' ? 'selected' : '' ?>>🔴 Pro</option>
        </select>
    </div>
</form>

<script>
function previewAvatar(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatarPreview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}
</script>
