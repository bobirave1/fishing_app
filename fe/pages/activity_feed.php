<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Feed - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
    <style>
        .activity-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background 0.2s;
        }
        .activity-item:hover {
            background: #f8f9fa;
        }
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        .activity-like {
            background: #dc3545;
        }
        .activity-comment {
            background: #0d6efd;
        }
        .activity-post {
            background: #198754;
        }
        .activity-follow {
            background: #6f42c1;
        }
    </style>
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>">

<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

// Get user avatar for navbar
$stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$profile = $stmt->fetch();
$avatar = $profile['avatar_url'] ?? '../assets/img/default-avatar.png';
?>

<!-- HEADER -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="../../index.php">
            <img src="../assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
            <span class="fw-bold fs-4 brand-color">FISHINGLORY</span>
        </a>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <button class="btn btn-light d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <img src="<?= htmlspecialchars($avatar) ?>" width="32" height="32" class="rounded-circle" style="object-fit: cover;">
                    <?= htmlspecialchars($_SESSION['username']) ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="../../be/users/profile.php?id=<?= $_SESSION['user_id'] ?>">
                        <i class="fas fa-user"></i> My Profile
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="../../be/auth/logout.php">
                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                    </a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-stream"></i> Activity Feed</h5>
                    <small>What your friends are doing</small>
                </div>
                <div class="card-body p-0" id="activityFeed">
                    <p class="text-center text-muted p-4"><i class="fas fa-spinner fa-spin"></i> Loading activity...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/app.js"></script>
<script>
    function loadActivityFeed() {
        fetch('../../be/activity/feed.php?action=get_feed&limit=20')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayActivityFeed(data.activities);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('activityFeed').innerHTML = '<p class="text-danger">Failed to load activity feed</p>';
            });
    }

    function displayActivityFeed(activities) {
        const container = document.getElementById('activityFeed');
        
        if (!activities || activities.length === 0) {
            container.innerHTML = `
                <div class="text-center p-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No activity yet. Follow more users to see their activities!</p>
                </div>
            `;
            return;
        }

        let html = '';
        activities.forEach(activity => {
            const avatar = activity.avatar_url || '../assets/img/default-avatar.png';
            let icon = 'fas fa-star';
            let iconClass = 'activity-post';
            let actionText = 'did something';

            switch(activity.action_type) {
                case 'post':
                    icon = 'fas fa-images';
                    iconClass = 'activity-post';
                    actionText = `posted <strong>${activity.description || 'a new post'}</strong>`;
                    break;
                case 'like':
                    icon = 'fas fa-heart';
                    iconClass = 'activity-like';
                    actionText = 'liked a post';
                    break;
                case 'comment':
                    icon = 'fas fa-comment';
                    iconClass = 'activity-comment';
                    actionText = 'commented on a post';
                    break;
                case 'follow':
                    icon = 'fas fa-user-plus';
                    iconClass = 'activity-follow';
                    actionText = activity.description || 'started following someone';
                    break;
            }

            html += `
                <div class="activity-item">
                    <div class="d-flex gap-3">
                        <div class="activity-icon ${iconClass}">
                            <i class="${icon}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div>
                                <a href="../../be/users/profile.php?id=${activity.user_id}" class="text-decoration-none fw-bold">
                                    ${activity.username}
                                </a>
                                <span>${actionText}</span>
                            </div>
                            <small class="text-muted">${formatDate(activity.created_at)}</small>
                            ${activity.post_id ? `<br><small class="text-primary cursor-pointer"><i class="fas fa-link"></i> View post</small>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        container.innerHTML = html;
    }

    // Load activity on page load
    loadActivityFeed();
    setInterval(loadActivityFeed, 30000); // Refresh every 30 seconds
</script>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>
