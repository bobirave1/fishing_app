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
$postsPerPage = 20;
$currentPage = max(1, (int)($_GET['page'] ?? 1));
$offset = ($currentPage - 1) * $postsPerPage;
$totalPosts = 0;
$totalPages = 1;

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM posts p
         WHERE
            p.visibility = 'public'
            OR (p.visibility = 'friends' AND p.user_id IN (
                SELECT friend_id FROM friends WHERE user_id = ?
            ))
            OR p.user_id = ?"
    );
    $countStmt->execute([$userId, $userId]);
    $totalPosts = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalPosts / $postsPerPage));

    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $postsPerPage;
    }

    $stmt = $pdo->prepare("
        SELECT p.*, u.username, up.avatar_url,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
             (SELECT GROUP_CONCAT(pi.image_url ORDER BY pi.id SEPARATOR '||') FROM post_images pi WHERE pi.post_id = p.id) as media_urls,
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
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $userId, PDO::PARAM_INT);
    $stmt->bindValue(2, $userId, PDO::PARAM_INT);
    $stmt->bindValue(3, $userId, PDO::PARAM_INT);
    $stmt->bindValue(4, $postsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(5, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();
} else {
    $countStmt = $pdo->query("SELECT COUNT(*) FROM posts WHERE visibility = 'public'");
    $totalPosts = (int)$countStmt->fetchColumn();
    $totalPages = max(1, (int)ceil($totalPosts / $postsPerPage));

    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $postsPerPage;
    }

    // за гости – само public постове
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, up.avatar_url,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
             (SELECT GROUP_CONCAT(pi.image_url ORDER BY pi.id SEPARATOR '||') FROM post_images pi WHERE pi.post_id = p.id) as media_urls,
               0 as user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE p.visibility = 'public'
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bindValue(1, $postsPerPage, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $posts = $stmt->fetchAll();
}

$composerUser = [
    'username' => $_SESSION['username'] ?? 'User',
    'avatar' => getUserAvatar(null),
];

if (isset($_SESSION['user_id'])) {
    $composerStmt = $pdo->prepare("SELECT u.username, up.avatar_url FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id WHERE u.id = ? LIMIT 1");
    $composerStmt->execute([$_SESSION['user_id']]);
    $composerData = $composerStmt->fetch(PDO::FETCH_ASSOC);
    if ($composerData) {
        $composerUser['username'] = $composerData['username'];
        $composerUser['avatar'] = getUserAvatar($composerData['avatar_url'] ?? null);
    }
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
<body class="d-flex flex-column min-vh-100" data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include 'fe/components/navbar.php'; ?>

<main class="flex-grow-1 container-fluid mt-5 mb-0 py-3">
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
        <div class="col-12 col-md-<?= isset($_SESSION['user_id']) ? '6' : '8 offset-md-2' ?> col-lg-<?= isset($_SESSION['user_id']) ? '7' : '8 offset-lg-2' ?>">

<?php if (!isset($_SESSION['user_id'])): ?>
    <div class="text-center py-5">
        <h2 class="fw-bold text-primary mb-4"><?= __('welcome_title') ?></h2>
        <div class="d-flex justify-content-center gap-3">
            <a href="fe/auth/login_form.php" class="btn btn-primary btn-lg px-4">
                <i class="fas fa-sign-in-alt"></i> <?= __('login') ?>
            </a>
            <a href="fe/auth/register_form.php" class="btn btn-success btn-lg px-4">
                <i class="fas fa-user-plus"></i> <?= __('sign_up') ?>
            </a>
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
        <form action="be/posts/create.php" method="post" enctype="multipart/form-data" id="createPostForm">
            <?= getCsrfField() ?>

            <div class="composer-header">
                <img src="<?= htmlspecialchars($composerUser['avatar']) ?>" alt="avatar" class="composer-avatar">
                <div class="composer-meta">
                    <div class="composer-name"><?= htmlspecialchars($composerUser['username']) ?></div>
                    <select id="postVisibility" name="visibility" class="form-select form-select-sm composer-privacy">
                        <option value="public">🌍 <?= __('public') ?></option>
                        <option value="friends">👥 <?= __('friends') ?></option>
                        <option value="private">🔒 <?= __('private') ?></option>
                    </select>
                </div>
            </div>

            <div class="composer-fields">
                <input
                    type="text"
                    name="title"
                    id="postTitleInput"
                    class="create-post-input mb-2"
                    placeholder="<?= __('post_title_placeholder') ?>"
                    required
                    maxlength="200"
                >
                <textarea
                    id="postContentInput"
                    name="content"
                    class="create-post-input composer-content-input"
                    placeholder="<?= __('post_placeholder') ?>"
                    required
                    maxlength="5000"
                ></textarea>
            </div>

            <input
                type="file"
                id="postMediaInput"
                name="media[]"
                class="create-post-file-input"
                multiple
                accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/avi,video/mov"
            >

            <div id="postMediaPreview" class="post-media-preview" aria-live="polite"></div>

            <label for="postMediaInput" class="composer-icon-btn mt-1" title="<?= __('attach_file') ?>">
                <i class="fas fa-images"></i>
            </label>

            <div
                id="postMediaFileName"
                class="create-post-file-name"
                data-no-file="<?= __('no_file_selected') ?>"
                data-selected-file="<?= __('selected_file') ?>"
                data-files-selected="<?= __('files_selected') ?>"
            ><?= __('no_file_selected') ?></div>

            <button class="btn btn-primary w-100 mt-3" style="border-radius: 10px; padding: 0.9rem; font-size: 1.05rem; font-weight: 700;">
                <i class="fas fa-paper-plane"></i> <?= __('post') ?>
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Posts feed -->
<?php foreach ($posts as $p): 
    $avatar = getUserAvatar($p['avatar_url'] ?? null);
    $postMedia = [];
    if (!empty($p['media_urls'])) {
        $postMedia = array_values(array_unique(array_filter(explode('||', (string) $p['media_urls']))));
    } elseif (!empty($p['image'])) {
        $postMedia = [(string) $p['image']];
    }
?>
    <div class="modern-post glass-card">
        <div class="post-header">
            <div class="d-flex align-items-center flex-grow-1">
                <img src="<?= htmlspecialchars($avatar) ?>" class="post-avatar-modern">
                <div class="post-user-info">
                    <h6>
                        <a href="be/users/profile.php?id=<?= $p['user_id'] ?>" class="text-decoration-none" style="color: var(--text-primary);">
                            <?= htmlspecialchars($p['username']) ?>
                        </a>
                    </h6>
                    <div class="post-timestamp d-flex align-items-center justify-content-between">
                        <span>
                            <i class="fas fa-clock"></i>
                            <span class="post-time-ago" data-iso-date="<?= htmlspecialchars(date('c', strtotime($p['created_at']))) ?>"></span>
                        </span>
                        <span class="badge ms-3" style="font-size: 0.85rem; background: var(--surface-2); color: var(--text-primary); border: 1px solid var(--border-color);">
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
        
        <?php if (!empty($postMedia)): ?>
            <div class="post-image-wrapper<?= count($postMedia) > 1 ? ' post-media-grid' : '' ?>">
                <?php foreach ($postMedia as $mediaPath): ?>
                    <?php
                    $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                    $videoExtensions = ['mp4', 'webm', 'avi', 'mov'];
                    ?>
                    <div class="post-media-item">
                        <?php if (in_array($ext, $videoExtensions, true)): ?>
                            <video controls class="post-image-modern" style="width: 100%; max-height: 600px;">
                                <source src="<?= htmlspecialchars($mediaPath) ?>" type="video/<?= $ext === 'mov' ? 'quicktime' : $ext ?>">
                                Your browser does not support the video tag.
                            </video>
                        <?php else: ?>
                            <img src="<?= htmlspecialchars($mediaPath) ?>"
                                 class="post-image-modern"
                                 style="cursor:pointer;"
                                 onclick="openPhotoLightbox(this)"
                                 data-post-id="<?= (int)$p['id'] ?>"
                                 data-avatar="<?= htmlspecialchars($avatar) ?>"
                                 data-username="<?= htmlspecialchars($p['username']) ?>"
                                 data-user-id="<?= (int)$p['user_id'] ?>"
                                 data-title="<?= htmlspecialchars($p['title'] ?? '') ?>"
                                 data-content="<?= htmlspecialchars($p['content'] ?? '') ?>"
                                 data-iso-date="<?= htmlspecialchars(date('c', strtotime($p['created_at']))) ?>"
                                 data-like-count="<?= (int)$p['like_count'] ?>"
                                 data-comment-count="<?= (int)$p['comment_count'] ?>"
                                 data-user-liked="<?= !empty($p['user_liked']) ? '1' : '0' ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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

<?php if ($totalPages > 1): ?>
    <nav aria-label="Feed pagination" class="my-4 d-flex justify-content-center">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= max(1, $currentPage - 1) ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                </a>
            </li>

            <?php
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            for ($page = $startPage; $page <= $endPage; $page++):
            ?>
                <li class="page-item <?= $page === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $page ?>"><?= $page ?></a>
                </li>
            <?php endfor; ?>

            <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= min($totalPages, $currentPage + 1) ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

        </div> <!-- Close main content column -->

        <!-- Right Sidebar -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="col-md-3 col-lg-3">
            <div class="sidebar-modern sidebar-sticky">
                <!-- Weather Widget -->
                <div class="weather-widget glass-card mb-4">
                    <div class="weather-content">
                        <h5 class="sidebar-title"><i class="fas fa-cloud-sun"></i> <?= __('current_weather') ?></h5>
                        <div id="weather-info">
                            <p><?= __('fetching_weather') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div> <!-- Close row -->
</main>



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
                    <i class="fas fa-trash-alt"></i> <?= __('delete_permanently') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Photo Lightbox -->
<div id="photoLightbox" class="photo-lightbox">
    <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
    <div class="lightbox-container">
        <div class="lightbox-media">
            <img id="lightboxImage" src="" alt="Post image">
        </div>
        <div class="lightbox-sidebar">
            <div class="lightbox-post-header">
                <img id="lightboxAvatar" src="" class="rounded-circle" width="42" height="42" style="object-fit:cover;flex-shrink:0;">
                <div style="min-width:0;flex:1;">
                    <a id="lightboxUsernameLink" href="#" class="text-decoration-none fw-bold" style="color:var(--text-primary);font-size:0.95rem;"></a>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;" id="lightboxTimestamp"></div>
                </div>
            </div>
            <div class="lightbox-post-body">
                <h6 id="lightboxTitle" style="font-weight:700;color:var(--text-primary);margin-bottom:6px;"></h6>
                <p id="lightboxContent" style="color:var(--text-secondary);font-size:0.9rem;line-height:1.6;margin:0;"></p>
            </div>
            <div class="lightbox-actions">
                <button id="lightboxLikeBtn" class="action-btn" onclick="toggleLike(parseInt(this.dataset.postId), this)">
                    <i class="far fa-heart"></i> <span>0</span>
                </button>
                <span class="action-btn" style="cursor:default;pointer-events:none;">
                    <i class="far fa-comment"></i> <span id="lightboxCommentCnt">0</span>
                </span>
            </div>
            <div class="lightbox-comments-scroll" id="lightboxComments"></div>
            <div id="lightboxReplyIndicator" class="lightbox-reply-indicator" style="display:none;"></div>
            <div class="lightbox-comment-input">
                <input type="text" id="lightboxCommentInput" class="form-control form-control-sm" placeholder="Write a comment...">
                <button class="btn btn-sm btn-primary" onclick="addLightboxComment()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="fe/assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="fe/assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="fe/assets/js/index.js?v=<?= assetVersion('fe/assets/js/index.js') ?>"></script>

<?php include 'fe/components/footer.php'; ?>

</body>
</html>