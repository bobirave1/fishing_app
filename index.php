<?php
require 'config/security.php';
secureSession();
// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require 'config/database.php';
require 'config/avatar_helper.php';
require 'config/languages.php'; // Add language support
require 'config/actions.php';
setSecurityHeaders();
handleGlobalActions();

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
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FISHINGLORY - <?= __('home') ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="fe/assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="fe/assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="fe/assets/css/posts.css?v=<?= assetVersion('fe/assets/css/posts.css') ?>">
    <link rel="stylesheet" href="fe/assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="fe/assets/img/logo_rounded.png">
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include 'fe/components/navbar.php'; ?>

<div class="container-fluid my-5 py-3">
    <div class="row">
        <!-- Left Sidebar -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="col-md-3 col-lg-2">
            <div class="sidebar-modern sidebar-sticky">
                <div class="sidebar-card">
                    <div class="sidebar-title">
                        <i class="fas fa-compass"></i>
                        <span><?= __('quick_links') ?></span>
                    </div>
                    <a href="be/users/profile.php?id=<?= $_SESSION['user_id'] ?>" class="sidebar-item">
                        <i class="fas fa-user-circle"></i>
                        <span class="sidebar-item-text"><?= __('my_profile') ?></span>
                    </a>
                    <a href="be/friends/list_friends.php" class="sidebar-item">
                        <i class="fas fa-user-friends"></i>
                        <span class="sidebar-item-text"><?= __('friends') ?></span>
                    </a>
                    <a href="be/friends/list_requests.php" class="sidebar-item">
                        <i class="fas fa-user-plus"></i>
                        <span class="sidebar-item-text"><?= __('requests') ?></span>
                    </a>
                    <a href="fe/pages/messages.php" class="sidebar-item">
                        <i class="fas fa-envelope"></i>
                        <span class="sidebar-item-text"><?= __('messages') ?></span>
                    </a>
                    <a href="fe/pages/activity_feed.php" class="sidebar-item">
                        <i class="fas fa-fish"></i>
                        <span class="sidebar-item-text"><?= __('fish_activity') ?></span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content - Posts Only -->
        <div class="col-12 col-md-<?= isset($_SESSION['user_id']) ? '6' : '8' ?> col-lg-<?= isset($_SESSION['user_id']) ? '7' : '8' ?>">

<?php if (!isset($_SESSION['user_id'])): ?>
    <div class="text-center py-5">
        <div class="hero-section">
            <h2 class="fw-bold text-primary mb-3">Welcome to FISHINGLORY</h2>
            <div class="d-flex justify-content-center gap-3">
                <a href="fe/auth/login_form.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-sign-in-alt"></i> <?= __('login') ?>
                </a>
                <a href="fe/auth/register_form.php" class="btn btn-success btn-lg">
                    <i class="fas fa-user-plus"></i> <?= __('sign_up') ?>
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Create post form -->
<?php if (isset($_SESSION['user_id'])): ?>
    <?php if (isset($_SESSION['post_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i>
            <?= htmlspecialchars($_SESSION['post_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['post_error']); ?>
    <?php endif; ?>
    
    <div class="create-post-modern glass-card mb-4 mt-4">
        <form action="be/posts/create.php" method="post" enctype="multipart/form-data">
            <?= getCsrfField() ?>
            <input type="text" name="title" class="create-post-input mb-3" placeholder="What's on your mind?" required maxlength="200">
            <textarea name="content" class="create-post-input mb-3" placeholder="<?= __('post_placeholder') ?>" required maxlength="5000" style="border-radius: 16px; min-height: 100px;"></textarea>
            <div class="create-post-actions">
                <select name="visibility" class="form-select" style="border-radius: 12px;">
                    <option value="public">🌍 <?= __('public') ?></option>
                    <option value="friends">👥 <?= __('friends') ?></option>
                    <option value="private">🔒 <?= __('private') ?></option>
                </select>
                <input type="file" name="media" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/avi,video/mov" style="border-radius: 12px;">
            </div>
            <button class="btn btn-primary w-100 mt-3" style="border-radius: 12px; padding: 1rem; font-size: 1.1rem; font-weight: 700;">
                <i class="fas fa-paper-plane"></i> <?= __('post') ?>
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Posts feed -->
<?php foreach ($posts as $p): 
    $avatar = getUserAvatar($p['avatar_url'] ?? null);
?>
    <div class="modern-post glass-card">
        <div class="post-header">
            <div class="d-flex align-items-center flex-grow-1">
                <img src="<?= htmlspecialchars($avatar) ?>" class="post-avatar-modern">
                <div class="post-user-info">
                    <h6>
                        <a href="be/users/profile.php?id=<?= $p['user_id'] ?>" class="text-decoration-none text-dark">
                            <?= htmlspecialchars($p['username']) ?>
                        </a>
                    </h6>
                    <div class="post-timestamp d-flex align-items-center justify-content-between">
                        <span>
                            <i class="fas fa-clock"></i>
                            <span class="post-time-ago" data-iso-date="<?= htmlspecialchars(date('c', strtotime($p['created_at']))) ?>"></span>
                        </span>
                        <span class="badge bg-light text-dark ms-3" style="font-size: 0.85rem;">
                            <?php 
                            $icons = ['public' => '🌍', 'friends' => '👥', 'private' => '🔒'];
                            echo $icons[$p['visibility']] ?? '🌍';
                            ?>
                            <?= __($p['visibility']) ?>
                        </span>
                    </div>
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

        <div class="post-content">
            <?php if (!empty($p['title'])): ?>
                <h6 class="mb-2"><?= htmlspecialchars($p['title']) ?></h6>
            <?php endif; ?>
            <p class="mb-0"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
        </div>
        
        <?php if (!empty($p['image'])): ?>
            <div class="post-image-wrapper">
                <?php 
                $ext = strtolower(pathinfo($p['image'], PATHINFO_EXTENSION));
                $videoExtensions = ['mp4', 'webm', 'avi', 'mov'];
                if (in_array($ext, $videoExtensions)): 
                ?>
                    <video controls class="post-image-modern" style="width: 100%; max-height: 600px;">
                        <source src="<?= htmlspecialchars($p['image']) ?>" type="video/<?= $ext === 'mov' ? 'quicktime' : $ext ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($p['image']) ?>" class="post-image-modern">
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Engagement Section -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="post-actions">
            <button class="action-btn <?= $p['user_liked'] ? 'liked' : '' ?>" 
                    onclick="toggleLike(<?= $p['id'] ?>, this)">
                <i class="<?= $p['user_liked'] ? 'fas' : 'far' ?> fa-heart"></i> 
                <span id="like-count-<?= $p['id'] ?>"><?= $p['like_count'] ?></span>
            </button>
            <button class="action-btn" onclick="toggleComments(<?= $p['id'] ?>)">
                <i class="far fa-comment"></i> 
                <span id="comment-count-<?= $p['id'] ?>"><?= $p['comment_count'] ?></span> <?= __('comments') ?>
            </button>
            <?php if ($_SESSION['user_id'] != $p['user_id']): ?>
            <button class="action-btn" id="follow-btn-<?= $p['user_id'] ?>" 
                    onclick="toggleFollow(<?= $p['user_id'] ?>, this)">
                <i class="fas fa-user-plus"></i> <?= __('follow') ?>
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
    </div> <!-- Close modern-post -->
<?php endforeach; ?>

        </div> <!-- Close main content column -->

        <!-- Right Sidebar -->
        <div class="col-md-3 col-lg-3">
            <div class="sidebar-modern sidebar-sticky">
                <!-- Weather Widget -->
                <div class="weather-widget glass-card mb-4">
                    <div class="weather-content">
                        <h5 class="sidebar-title"><i class="fas fa-cloud-sun"></i> <?= __('current_weather') ?></h5>
                        <div id="weather-info">
                            <p>Fetching weather based on your location...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Close row -->
</div> <!-- Close container-fluid -->



<!-- Edit Post Modal -->
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPostModalLabel">
                    <i class="fas fa-edit"></i> <?= __('edit_post') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editPostBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="submitEditForm()">
                    <i class="fas fa-save"></i> <?= __('save') ?>
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
                    <i class="fas fa-trash"></i> <?= __('delete_post') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deletePostBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-danger" onclick="confirmDeletePost()">
                    <i class="fas fa-trash-alt"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="fe/assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="fe/assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="fe/assets/js/index.js?v=<?= assetVersion('fe/assets/js/index.js') ?>"></script>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>