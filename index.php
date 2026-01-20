<?php
session_start();
require 'config/database.php';

$posts = [];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT p.*, u.username, up.avatar_url,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE
            p.visibility = 'public'
         OR (p.visibility = 'friends' AND p.user_id IN (
                SELECT friend_id FROM friends WHERE user_id = ?
            ))
         OR p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
    $posts = $stmt->fetchAll();
} else {
    // за гости – само public постове
    $stmt = $pdo->query("
        SELECT p.*, u.username, up.avatar_url,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
               0 as user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE p.visibility = 'public'
        ORDER BY p.created_at DESC
    ");
    $posts = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FISHINGLORY - Home</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="fe/assets/css/style.css">
    <link rel="icon" href="fe/assets/img/logo_rounded.png">
    <style>
        .search-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .search-category {
            padding: 8px 12px;
            background: #f8f9fa;
            font-size: 0.85rem;
            border-bottom: 1px solid #eee;
            text-transform: uppercase;
            color: #666;
        }
        .search-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-item:hover {
            background: #f8f9fa;
        }
        .comment-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }
        .comment-item {
            background: white;
            padding: 8px;
            border-radius: 4px;
        }
        .engagement-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #e9ecef;
        }
        .engagement-btn {
            flex: 1;
            border: none;
            background: transparent;
            color: #666;
            cursor: pointer;
            padding: 8px;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        .engagement-btn:hover {
            background: #f8f9fa;
            color: #0d6efd;
        }
        .engagement-btn.liked {
            color: #dc3545;
        }
        .engagement-btn.following {
            color: #198754;
        }
        .follow-btn {
            padding: 4px 12px;
            font-size: 0.9rem;
        }
    </style>
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>">

<!-- HEADER -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-4">

        <!-- Logo + Search -->
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="fe/assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
                <span class="fw-bold fs-4 brand-color">FISHINGLORY</span>
            </a>
            <div class="position-relative" style="width: 300px;">
                <input type="text" id="searchInput" class="form-control form-control-sm rounded-pill bg-light border-0"
                       placeholder="Търси риболовци, водоеми..." oninput="performSearch(this.value)">
                <div id="searchResults" class="search-results-dropdown d-none"></div>
            </div>
        </div>

        <!-- Main Navigation -->
        <ul class="navbar-nav mx-auto d-none d-md-flex flex-row gap-4">
            <li class="nav-item"><a class="nav-link active"><i class="fas fa-home fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link" href="fe/pages/messages.php"><i class="fas fa-comments fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link" href="fe/pages/activity_feed.php"><i class="fas fa-stream fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link"><i class="fas fa-map-marked-alt fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link"><i class="fas fa-fish fs-4"></i></a></li>
        </ul>

        <!-- Notifications & Profile -->
        <ul class="navbar-nav ms-auto align-items-center">
            <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item">
                <div class="dropdown">
                    <button class="btn btn-light position-relative" type="button" data-bs-toggle="dropdown" title="Notifications">
                        <i class="fas fa-bell fs-5"></i>
                        <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">0</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" style="width: 320px; max-height: 400px; overflow-y: auto;">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li id="notificationsList">
                            <p class="dropdown-item text-center text-muted">Loading...</p>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>
            <li class="nav-item d-flex gap-2">
                <?php if (isset($_SESSION['user_id'])): 
                    // Get user avatar
                    $stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $profile = $stmt->fetch();
                    $avatar = $profile['avatar_url'] ?? 'fe/assets/img/default-avatar.png';
                ?>
                    <div class="dropdown">
                        <button class="btn btn-light d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <img src="<?= htmlspecialchars($avatar) ?>" width="32" height="32" class="rounded-circle" style="object-fit: cover;">
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="be/users/profile.php?id=<?= $_SESSION['user_id'] ?>">
                                <i class="fas fa-user"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#editProfileModal" onclick="loadEditProfile()">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="be/auth/logout.php">
                                <i class="fa fa-sign-out-alt me-1"></i> Logout
                            </a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                        <i class="fa fa-sign-in-alt me-1"></i> Login
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerModal">
                        <i class="fa fa-user-plus me-1"></i> Register
                    </button>
                <?php endif; ?>
            </li>
        </ul>

    </div>
</nav>


<div class="container my-5 py-5">

<!-- Weather Widget -->
<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <h5 class="card-title"><i class="fas fa-cloud-sun"></i> Local Fishing Weather</h5>
        <div id="weather-info">
            <p>Fetching weather based on your location...</p>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="row g-4 justify-content-center">

        <!-- Profile -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fa fa-user fa-3x mb-3 text-primary"></i>
                    <h5>My Profile</h5>
                    <a href="be/users/profile.php?id=<?= $_SESSION['user_id'] ?>" class="btn btn-primary w-100">
                        View Profile
                    </a>
                </div>
            </div>
        </div>

        <!-- Friends -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fa fa-users fa-3x mb-3 text-success"></i>
                    <h5>Friends</h5>
                    <a href="be/friends/list_friends.php" class="btn btn-success w-100">
                        My Friends
                    </a>
                </div>
            </div>
        </div>

        <!-- Friend Requests -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fa fa-user-plus fa-3x mb-3 text-warning"></i>
                    <h5>Friend Requests</h5>
                    <a href="be/friends/list_requests.php" class="btn btn-warning w-100">
                        View Requests
                    </a>
                </div>
            </div>
        </div>

    </div>

<?php else: ?>
    <div class="text-center py-5">
        <div class="hero-section">
            <i class="fas fa-fish fa-5x text-primary mb-4"></i>
            <h1 class="display-4 fw-bold text-primary">Welcome to FISHINGLORY</h1>
            <p class="lead fs-4">The ultimate fishing community and marketplace.</p>
            <p class="mb-4">Connect with anglers, share catches, track weather, and explore fishing spots.</p>
            <div class="d-flex justify-content-center gap-3">
                <button class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#loginModal">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
                <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#registerModal">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Create post form -->
<?php if (isset($_SESSION['user_id'])): ?>
    <div class="card mb-4 shadow-sm mt-4">
        <div class="card-body">
            <form action="be/posts/create.php" method="post" enctype="multipart/form-data">
                <input type="text" name="title" class="form-control mb-2" placeholder="Post title" required>
                <textarea name="content" class="form-control mb-2" placeholder="Share your catch..." required></textarea>
                <select name="visibility" class="form-select mb-2">
                    <option value="public">🌍 Public</option>
                    <option value="friends">👥 Friends</option>
                    <option value="private">🔒 Only me</option>
                </select>
                <input type="file" name="image" class="form-control mb-2">
                <button class="btn btn-primary w-100">Post</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Posts feed -->
<?php foreach ($posts as $p): 
    $avatar = $p['avatar_url'] ?? 'fe/assets/img/default-avatar.png';
?>
    <div class="card mb-4 shadow-sm post-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center flex-grow-1 gap-3">
                    <img src="<?= htmlspecialchars($avatar) ?>" class="rounded-circle avatar-sm" 
                         width="48" height="48" style="object-fit: cover;">
                    <div>
                        <h6 class="mb-0">
                            <a href="be/users/profile.php?id=<?= $p['user_id'] ?>" class="text-decoration-none text-dark fw-bold">
                                <?= htmlspecialchars($p['username']) ?>
                            </a>
                        </h6>
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> <?= date('M d, Y', strtotime($p['created_at'])) ?>
                        </small>
                    </div>
                </div>
                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $p['user_id']): ?>
                    <div class="post-action-buttons">
                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editPostModal" 
                                onclick="loadEditPost(<?= $p['id'] ?>)" title="Edit post">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePostModal" 
                                onclick="loadDeletePost(<?= $p['id'] ?>)" title="Delete post">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <h5 class="card-title mb-2"><?= htmlspecialchars($p['title']) ?></h5>
            <p class="card-text mt-3"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
            
            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" class="img-fluid rounded mb-3" style="max-height: 400px; width: 100%; object-fit: cover;">
            <?php endif; ?>
            
            <!-- Engagement Section -->
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="engagement-buttons">
                <button class="engagement-btn <?= $p['user_liked'] ? 'liked' : '' ?>" 
                        onclick="toggleLike(<?= $p['id'] ?>, this)">
                    <i class="<?= $p['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i> 
                    <span id="like-count-<?= $p['id'] ?>"><?= $p['like_count'] ?></span>
                </button>
                <button class="engagement-btn" onclick="toggleComments(<?= $p['id'] ?>)">
                    <i class="far fa-comment"></i> 
                    <span id="comment-count-<?= $p['id'] ?>"><?= $p['comment_count'] ?></span>
                </button>
                <?php if ($_SESSION['user_id'] != $p['user_id']): ?>
                <button class="engagement-btn follow-btn btn-sm" id="follow-btn-<?= $p['user_id'] ?>" 
                        onclick="toggleFollow(<?= $p['user_id'] ?>, this)">
                    <i class="fas fa-user-plus"></i> Follow
                </button>
                <?php endif; ?>
            </div>
            
            <!-- Comments Section -->
            <div id="comment-section-<?= $p['id'] ?>" class="comment-section d-none">
                <div id="comments-<?= $p['id'] ?>">
                    <p class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading comments...</p>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <input type="text" id="comment-input-<?= $p['id'] ?>" 
                           class="form-control form-control-sm" 
                           placeholder="Write a comment..." />
                    <button class="btn btn-sm btn-primary" onclick="addComment(<?= $p['id'] ?>)">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
            <?php else: ?>
            <!-- Engagement Buttons (Read-only for guests) -->
            <div class="engagement-buttons">
                <button class="engagement-btn" disabled>
                    <i class="far fa-heart"></i> <?= $p['like_count'] ?>
                </button>
                <button class="engagement-btn" disabled>
                    <i class="far fa-comment"></i> <?= $p['comment_count'] ?>
                </button>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                <small class="text-muted">
                    <i class="fas fa-eye"></i>
                </small>
                <small class="badge bg-light text-dark">
                    <?php 
                    $icons = ['public' => '🌍', 'friends' => '👥', 'private' => '🔒'];
                    echo $icons[$p['visibility']] ?? '🌍';
                    ?>
                    <?= ucfirst($p['visibility']) ?>
                </small>
            </div>
        </div>
    </div>
<?php endforeach; ?>

</div> <!-- container -->

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded shadow custom-bg">
            <div class="modal-header border-0 text-center d-block position-relative">
                <h5 class="modal-title" id="loginModalLabel">Login to FISHINGLORY</h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-3 me-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="loginModalBody">
                <p class="text-center">Loading login form...</p>
            </div>
        </div>
    </div>
</div>

<!-- Register Modal -->
<div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded shadow custom-bg">
            <div class="modal-header border-0 text-center d-block position-relative">
                <h5 class="modal-title" id="registerModalLabel">Register for FISHINGLORY</h5>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-3 me-3" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="registerModalBody">
                <p class="text-center">Loading registration form...</p>
            </div>
        </div>
    </div>
</div>

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

<!-- Edit Post Modal -->
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPostModalLabel">
                    <i class="fas fa-edit"></i> Edit Post
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editPostBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="editPostForm" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Post Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePostModalLabel">
                    <i class="fas fa-trash"></i> Delete Post
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deletePostBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeletePost()">
                    <i class="fas fa-trash-alt"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="fe/assets/js/app.js"></script>
<script>
    // Load edit profile form
    function loadEditProfile() {
        fetch('fe/users/edit_profile_form.php')
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

            fetch('be/users/edit_profile.php', {
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

    // Load edit post form
    function loadEditPost(postId) {
        fetch('fe/posts/edit_form.php?id=' + postId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('editPostBody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('editPostBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
            });
    }

    // Load delete confirmation
    function loadDeletePost(postId) {
        fetch('fe/posts/delete_confirm.php?id=' + postId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('deletePostBody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('deletePostBody').innerHTML = '<p class="text-danger">Error loading confirmation.</p>';
            });
    }

    // Confirm delete
    function confirmDeletePost() {
        const form = document.getElementById('deletePostForm');
        const formData = new FormData(form);

        fetch('be/posts/delete.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Close modal and reload
                bootstrap.Modal.getInstance(document.getElementById('deletePostModal')).hide();
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the post.');
        });
    }

    // Handle edit form submission
    document.addEventListener('submit', function(e) {
        if (e.target.id === 'editPostForm') {
            e.preventDefault();
            const formData = new FormData(e.target);

            fetch('be/posts/edit.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editPostModal')).hide();
                    setTimeout(() => location.reload(), 500);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the post.');
            });
        }
    });

    // Load login form
    document.getElementById('loginModal').addEventListener('show.bs.modal', function () {
        fetch('fe/auth/login_form.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('loginModalBody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('loginModalBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
            });
    });

    // Load register form
    document.getElementById('registerModal').addEventListener('show.bs.modal', function () {
        fetch('fe/auth/register_form.php')
            .then(response => response.text())
            .then(html => {
                document.getElementById('registerModalBody').innerHTML = html;
            })
            .catch(() => {
                document.getElementById('registerModalBody').innerHTML = '<p class="text-danger">Error loading form.</p>';
            });
    });

    // Clear bodies when modals close
    ['loginModal', 'registerModal'].forEach(id => {
        document.getElementById(id).addEventListener('hidden.bs.modal', function () {
            document.getElementById(
                id === 'loginModal' ? 'loginModalBody' : 'registerModalBody'
            ).innerHTML = '<p class="text-center">Loading form...</p>';
        });
    });

    // Clear edit and delete modals when closed
    document.getElementById('editPostModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editPostBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    });

    document.getElementById('deletePostModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('deletePostBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    });

    document.getElementById('editProfileModal').addEventListener('hidden.bs.modal', function () {
        document.getElementById('editProfileBody').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>';
    });

    // Weather widget
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            fetch(`be/weather/get_weather.php?lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('weather-info').innerHTML = `<p class="text-danger">${data.error}</p>`;
                    } else {
                        let fishingTip = '';
                        if (data.wind_speed < 5) fishingTip = 'Great day for fishing! Low wind speeds are ideal.';
                        else if (data.wind_speed < 10) fishingTip = 'Moderate wind, still suitable for most fishing activities.';
                        else fishingTip = 'High wind speeds may make fishing challenging or unsafe.';

                        document.getElementById('weather-info').innerHTML = `
                            <div class="row text-center">
                                <div class="col-12 mb-3">
                                    <h5><i class="fas fa-map-marker-alt"></i> ${data.location}</h5>
                                    <img src="https://openweathermap.org/img/wn/${data.icon}@2x.png" alt="weather icon" class="img-fluid" style="max-width: 80px;">
                                    <p class="mb-0 fs-4">${data.description}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="fas fa-thermometer-half"></i> <strong>Temperature:</strong> ${data.temperature}°C</p>
                                    <p><i class="fas fa-wind"></i> <strong>Wind:</strong> ${data.wind_speed} m/s (${data.wind_direction}°)</p>
                                    <p><i class="fas fa-tint"></i> <strong>Humidity:</strong> ${data.humidity}%</p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="fas fa-eye"></i> <strong>Visibility:</strong> ${data.visibility} km</p>
                                    <p><i class="fas fa-gauge"></i> <strong>Pressure:</strong> ${data.pressure} hPa</p>
                                    ${data.sea_level ? `<p><i class="fas fa-water"></i> <strong>Sea Level:</strong> ${data.sea_level} hPa</p>` : ''}
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-fish"></i> <strong>Fishing Tip:</strong> ${fishingTip}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch(() => {
                    document.getElementById('weather-info').innerHTML = '<p class="text-danger">Failed to load weather data.</p>';
                });
        }, function() {
            document.getElementById('weather-info').innerHTML = '<p class="text-danger">Location access denied. Please enable location services for personalized weather.</p>';
        });
    } else {
        document.getElementById('weather-info').innerHTML = '<p class="text-danger">Geolocation not supported by this browser.</p>';
    }
</script>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>