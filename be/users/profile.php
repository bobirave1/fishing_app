<?php
session_start();
require '../../config/database.php';

$profileId = (int)($_GET['id'] ?? 0);
$currentUser = $_SESSION['user_id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name, u.created_at, up.avatar_url, up.bio, up.location, up.experience_level
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$profileId]);
$user = $stmt->fetch();

if (!$user) die("User not found");

// Check friendship
$checkFriend = $pdo->prepare(
    "SELECT 1 FROM friends WHERE user_id = ? AND friend_id = ?"
);
$checkFriend->execute([$currentUser, $profileId]);
$isFriend = $checkFriend->fetch();

// Check pending request
$pending = $pdo->prepare(
    "SELECT 1 FROM friend_requests
     WHERE sender_id = ? AND receiver_id = ? AND status = 'pending'"
);
$pending->execute([$currentUser, $profileId]);
$isPending = $pending->fetch();

// Get user's posts
$posts = [];
if ($isFriend || $currentUser === $profileId) {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$profileId]);
    $posts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE user_id = ? AND visibility = 'public' ORDER BY created_at DESC");
    $stmt->execute([$profileId]);
    $posts = $stmt->fetchAll();
}

// Get friend count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM friends WHERE user_id = ? OR friend_id = ?");
$stmt->execute([$profileId, $profileId]);
$friendCount = $stmt->fetch()['count'];

$avatar = $user['avatar_url'] ?? '../../fe/assets/img/default-avatar.png';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']) ?> - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css">
    <link rel="icon" href="../../fe/assets/img/logo_rounded.png">
</head>
<body>

<!-- HEADER -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="../../index.php">
            <img src="../../fe/assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
            <span class="fw-bold fs-4 brand-color">FISHINGLORY</span>
        </a>
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
                <?php if ($currentUser): ?>
                    <a href="../../be/users/profile.php?id=<?= $currentUser ?>" class="d-flex align-items-center text-dark text-decoration-none">
                        <img src="../../fe/assets/img/default-avatar.png" width="40" height="40" class="rounded-circle me-1">
                        <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                <?php else: ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fa fa-sign-in-alt me-1"></i> Login
                    </button>
                <?php endif; ?>
            </li>
        </ul>
    </div>
</nav>

<div class="container my-5 py-5">
    <!-- Profile Header -->
    <div class="card mb-4 shadow">
        <div class="card-body text-center">
            <img src="../../<?= htmlspecialchars($avatar) ?>" width="150" height="150" class="rounded-circle mb-3 border border-primary" style="border-width: 4px !important; object-fit: cover;">
            <h2 class="fw-bold text-primary"><?= htmlspecialchars($user['username']) ?></h2>
            <p class="text-muted fs-5"><?= htmlspecialchars($user['full_name']) ?></p>
            
            <?php if (!empty($user['location'])): ?>
                <p class="text-muted">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['location']) ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($user['bio'])): ?>
                <p class="text-muted" style="font-style: italic; margin: 1rem 0;">
                    "<?= htmlspecialchars($user['bio']) ?>"
                </p>
            <?php endif; ?>

            <?php if (!empty($user['experience_level'])): ?>
                <p class="text-muted mb-3">
                    <?php
                    $levels = ['beginner' => '🟢 Beginner', 'advanced' => '🟡 Advanced', 'pro' => '🔴 Pro'];
                    echo $levels[$user['experience_level']] ?? '🟢 Beginner';
                    ?>
                </p>
            <?php endif; ?>

            <p class="text-muted"><i class="fas fa-calendar-alt"></i> Joined <?= date('F Y', strtotime($user['created_at'])) ?></p>
            <div class="d-flex justify-content-center gap-4 mb-3">
                <div><strong><?= count($posts) ?></strong> Posts</div>
                <div><strong><?= $friendCount ?></strong> Friends</div>
            </div>
            <?php if ($currentUser && $currentUser !== $profileId): ?>
                <?php if ($isFriend): ?>
                    <span class="badge bg-success fs-6 px-3 py-2">Friends</span>
                <?php elseif ($isPending): ?>
                    <span class="badge bg-warning text-dark fs-6 px-3 py-2">Request sent</span>
                <?php else: ?>
                    <form action="../../be/friends/send_request.php" method="post" class="d-inline">
                        <input type="hidden" name="receiver_id" value="<?= $profileId ?>">
                        <button class="btn btn-primary btn-lg"><i class="fas fa-user-plus"></i> Add Friend</button>
                    </form>
                <?php endif; ?>
            <?php elseif ($currentUser === $profileId): ?>
                <button class="btn btn-outline-primary btn-lg" data-bs-toggle="modal" data-bs-target="#editProfileModal" onclick="loadEditProfile()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="row">
        <div class="col-md-8">
            <!-- Posts Section -->
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> Posts</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <p class="text-center text-muted">No posts yet.</p>
                    <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-body">
                                    <h6 class="card-title fw-bold"><?= htmlspecialchars($post['title']) ?></h6>
                                    <p class="card-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                                    <?php if (!empty($post['image'])): ?>
                                        <img src="../../<?= htmlspecialchars($post['image']) ?>" class="img-fluid rounded mb-2" style="max-height: 300px;">
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <i class="fas fa-clock"></i> <?= date('M j, Y', strtotime($post['created_at'])) ?> |
                                        <i class="fas fa-eye"></i> <?= ucfirst($post['visibility']) ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Sidebar -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> Friends</h5>
                </div>
                <div class="card-body">
                    <p>Friends list coming soon...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

<?php if ($currentUser === $profileId): ?>
<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editProfileModalLabel">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editProfileBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editProfileForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if ($currentUser === $profileId): ?>
<script>
    // Load edit profile form
    function loadEditProfile() {
        fetch('../../fe/users/edit_profile_form.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('editProfileBody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('editProfileBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
            });
    }

    // Handle edit profile form submission
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'editProfileForm') {
            e.preventDefault();
            const formData = new FormData(e.target);

            fetch('../../be/users/edit_profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editProfileModal')).hide();
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating your profile.');
            });
        }
    });

    // Clear modal when closed
    document.getElementById('editProfileModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editProfileBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    });
</script>
<?php endif; ?>

</body>
</html>
