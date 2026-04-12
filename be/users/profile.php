<?php
require '../../config/database.php';
require '../../config/security.php';
secureSession();
setSecurityHeaders();

// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}
require '../../config/languages.php'; // Add language support
require '../../config/actions.php';
handleGlobalActions();

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

if (!$user) die(__('user_not_found'));

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
    $stmt = $pdo->prepare("
        SELECT p.*,
               u.username,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
               (SELECT GROUP_CONCAT(pi.image_url ORDER BY pi.id SEPARATOR '||') FROM post_images pi WHERE pi.post_id = p.id) as media_urls,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$currentUser, $profileId]);
    $posts = $stmt->fetchAll();
} else {
    $stmt = $pdo->prepare("
        SELECT p.*,
               u.username,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) as like_count,
               (SELECT COUNT(*) FROM post_comments WHERE post_id = p.id) as comment_count,
               (SELECT GROUP_CONCAT(pi.image_url ORDER BY pi.id SEPARATOR '||') FROM post_images pi WHERE pi.post_id = p.id) as media_urls,
               (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = ?) as user_liked
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE p.user_id = ? AND p.visibility = 'public'
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$currentUser, $profileId]);
    $posts = $stmt->fetchAll();
}

// Get friend count
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT CASE 
        WHEN user_id = ? THEN friend_id 
        WHEN friend_id = ? THEN user_id 
    END) as count 
    FROM friends 
    WHERE user_id = ? OR friend_id = ?
");
$stmt->execute([$profileId, $profileId, $profileId, $profileId]);
$friendCount = $stmt->fetch()['count'];

// Get friends list (first 6 for preview)
$friends = [];
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.full_name, up.avatar_url
    FROM (
        SELECT friend_id as friend FROM friends WHERE user_id = ?
        UNION
        SELECT user_id as friend FROM friends WHERE friend_id = ?
    ) f
    JOIN users u ON u.id = f.friend
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LIMIT 6
");
$stmt->execute([$profileId, $profileId]);
$friends = $stmt->fetchAll();

require_once '../../config/avatar_helper.php';

// Avatar for the profile being viewed (not current user!)
$profileAvatar = getUserAvatar($user['avatar_url'] ?? null);
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']) ?> | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../fe/assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/profile.css?v=<?= assetVersion('fe/assets/css/profile.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/posts.css?v=<?= assetVersion('fe/assets/css/posts.css') ?>">
    <link rel="stylesheet" href="../../fe/assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="../../fe/assets/img/logo_rounded.png">

</head>
<body class="d-flex flex-column min-vh-100" data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include '../../fe/components/navbar.php'; ?>

<main class="flex-grow-1 container my-4">
    <?php if (isset($_SESSION['friend_flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($_SESSION['friend_flash_success']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['friend_flash_success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['friend_flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($_SESSION['friend_flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['friend_flash_error']); ?>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="card mb-4 shadow profile-main-card">
        <div class="card-body text-center">
            <img src="<?= htmlspecialchars($profileAvatar) ?>" width="150" height="150" class="rounded-circle mb-3 border border-primary profile-avatar-main">
            <h2 class="fw-bold text-primary"><?= htmlspecialchars($user['username']) ?></h2>
            <p class="text-muted fs-5"><?= htmlspecialchars($user['full_name']) ?></p>
            
            <?php if (!empty($user['location'])): ?>
                <p class="text-muted">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['location']) ?>
                </p>
            <?php endif; ?>
            
            <?php if (!empty($user['bio'])): ?>
                <p class="text-muted profile-bio">
                    "<?= htmlspecialchars($user['bio']) ?>"
                </p>
            <?php endif; ?>

            <?php if (!empty($user['experience_level'])): ?>
                <p class="text-muted mb-3">
                    <?php
                    $levels = [
                        'beginner' => '🟢 ' . __('beginner'),
                        'advanced' => '🟡 ' . __('advanced'),
                        'pro' => '🔴 ' . __('pro')
                    ];
                    echo $levels[$user['experience_level']] ?? '🟢 ' . __('beginner');
                    ?>
                </p>
            <?php endif; ?>

            <p class="text-muted"><i class="fas fa-calendar-alt"></i> <?= __('joined') ?> <?= date('F Y', strtotime($user['created_at'])) ?></p>
            <div class="profile-stats">
                <div class="profile-stat-item"><strong><?= count($posts) ?></strong> <?= __('posts') ?></div>
                <div class="profile-stat-item"><strong><?= $friendCount ?></strong> <?= __('friends') ?></div>
            </div>
            <?php if ($currentUser && $currentUser !== $profileId): ?>
                <div id="friendActionContainer">
                    <?php if ($isFriend): ?>
                        <span class="badge bg-success fs-6 px-3 py-2 me-2"><?= __('friends') ?></span>
                        <form action="../friends/remove_friend.php" method="POST" class="d-inline" onsubmit="return confirm('<?= htmlspecialchars(__('remove_friend_confirm'), ENT_QUOTES, 'UTF-8') ?>');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generateCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="friend_id" value="<?= (int) $profileId ?>">
                            <input type="hidden" name="return_to" value="profile">
                            <button type="submit" class="btn btn-outline-danger btn-sm ms-2">
                                <i class="fas fa-user-minus"></i> <?= __('remove_friend') ?>
                            </button>
                        </form>
                    <?php elseif ($isPending): ?>
                        <span class="badge bg-warning text-dark fs-6 px-3 py-2"><?= __('request_sent') ?></span>
                    <?php else: ?>
                        <button onclick="sendFriendRequest(<?= $profileId ?>)" class="btn btn-primary btn-lg" id="addFriendBtn">
                            <i class="fas fa-user-plus"></i> <?= __('add_friend') ?>
                        </button>
                    <?php endif; ?>
                </div>
            <?php elseif ($currentUser === $profileId): ?>
                <a href="../../fe/pages/edit_profile.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-edit"></i> <?= __('edit_profile') ?>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Content -->
    <div class="row">
        <div class="col-md-8">
            <!-- Posts Section -->
            <div class="card profile-section-card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> <?= __('posts') ?></h5>
                </div>
                <div class="card-body">
                    <?php if (empty($posts)): ?>
                        <p class="text-center text-muted"><?= __('no_posts') ?></p>
                    <?php else: ?>
                        <?php foreach ($posts as $p): 
                            $postAvatar = $profileAvatar;
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
                                        <a href="profile.php?id=<?= $p['user_id'] ?>">
                                            <img src="<?= htmlspecialchars($postAvatar) ?>" class="post-avatar-modern" onerror="handleAvatarError(this)">
                                        </a>
                                        <div class="post-user-info">
                                            <h6>
                                                <a href="profile.php?id=<?= $p['user_id'] ?>" class="text-decoration-none" style="color: var(--text-primary);">
                                                    <?= htmlspecialchars($p['username']) ?>
                                                </a>
                                            </h6>
                                            <div class="post-timestamp d-flex align-items-center">
                                                <span>
                                                    <i class="fas fa-clock"></i>
                                                    <span class="post-time-ago" data-iso-date="<?= htmlspecialchars(date('c', strtotime($p['created_at']))) ?>"></span>
                                                </span>
                                                <span class="badge ms-2" style="font-size: 0.75rem; background: var(--surface-2); color: var(--text-primary); border: 1px solid var(--border-color);">
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
                                    <p class="mb-0"><?= preg_replace(
                                        '#(https?://[^\s<>"\']+)#i',
                                        '<a href="$1" target="_blank" rel="noopener noreferrer" style="color:var(--primary-color);word-break:break-all;">$1</a>',
                                        nl2br(htmlspecialchars($p['content']))
                                    ) ?></p>
                                </div>
                                
                                <?php if (!empty($postMedia)): ?>
                                    <?php $mediaCount = count($postMedia); $showCount = min($mediaCount, 4); ?>
                                    <?php
                                    $allImages = array_values(array_filter($postMedia, function($path) {
                                        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                                        return !in_array($ext, ['mp4', 'webm', 'avi', 'mov'], true);
                                    }));
                                    ?>
                                    <div class="post-image-wrapper post-media-count-<?= $showCount ?>" data-all-images='<?= htmlspecialchars(json_encode(array_map(function($p) { return "../../" . $p; }, $allImages)), ENT_QUOTES, "UTF-8") ?>'>
                                        <?php for ($mi = 0; $mi < $showCount; $mi++): ?>
                                            <?php
                                            $mediaPath = $postMedia[$mi];
                                            $ext = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION));
                                            $videoExtensions = ['mp4', 'webm', 'avi', 'mov'];
                                            $mediaSrc = '../../' . $mediaPath;
                                            $isLast = ($mi === $showCount - 1) && ($mediaCount > $showCount);
                                            ?>
                                            <div class="post-media-item<?= $isLast ? ' post-media-more' : '' ?>">
                                                <?php if (in_array($ext, $videoExtensions, true)): ?>
                                                    <video controls class="post-image-modern" style="width: 100%; max-height: 600px;">
                                                        <source src="<?= htmlspecialchars($mediaSrc) ?>" type="video/<?= $ext === 'mov' ? 'quicktime' : $ext ?>">
                                                    </video>
                                                <?php else: ?>
                                                    <img src="<?= htmlspecialchars($mediaSrc) ?>"
                                                         class="post-image-modern"
                                                         style="cursor:pointer;"
                                                         onclick="openPhotoLightbox(this)"
                                                         data-post-id="<?= (int)$p['id'] ?>"
                                                         data-avatar="<?= htmlspecialchars($postAvatar) ?>"
                                                         data-username="<?= htmlspecialchars($p['username']) ?>"
                                                         data-user-id="<?= (int)$p['user_id'] ?>"
                                                         data-title="<?= htmlspecialchars($p['title'] ?? '') ?>"
                                                         data-content="<?= htmlspecialchars($p['content'] ?? '') ?>"
                                                         data-iso-date="<?= htmlspecialchars(date('c', strtotime($p['created_at']))) ?>"
                                                         data-like-count="<?= (int)$p['like_count'] ?>"
                                                         data-comment-count="<?= (int)$p['comment_count'] ?>"
                                                         data-user-liked="<?= !empty($p['user_liked']) ? '1' : '0' ?>">
                                                <?php endif; ?>
                                                <?php if ($isLast): ?>
                                                    <div class="post-media-overlay">+<?= $mediaCount - $showCount ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endfor; ?>
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
                                </div>
                                    
                                <!-- Comments Section -->
                                <div id="comment-section-<?= $p['id'] ?>" class="comment-section d-none">
                                    <div id="comments-<?= $p['id'] ?>">
                                        <p class="text-center text-muted"><i class="fas fa-spinner fa-spin"></i> Loading comments...</p>
                                    </div>
                                    <div class="mt-3 d-flex gap-2">
                                        <input type="text" id="comment-input-<?= $p['id'] ?>" 
                                               class="form-control form-control-sm" 
                                               placeholder="<?= __('write_comment') ?>" />
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
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <!-- Friends Sidebar -->
            <div class="card profile-section-card">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-users"></i> <?= __('friends') ?></h5>
                    <span class="badge bg-light text-success"><?= $friendCount ?></span>
                </div>
                <div class="card-body">
                    <?php if (empty($friends)): ?>
                        <p class="text-center text-muted">
                            <i class="fas fa-user-friends fa-2x mb-2 d-block"></i>
                            <?= __('no_friends') ?>
                        </p>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($friends as $friend): 
                                $friendAvatar = getUserAvatar($friend['avatar_url'] ?? null);
                            ?>
                                <div class="col-6">
                                    <a href="profile.php?id=<?= $friend['id'] ?>" class="text-decoration-none">
                                        <div class="text-center p-2 hover-card">
                                            <img src="<?= htmlspecialchars($friendAvatar) ?>" 
                                                 class="rounded-circle mb-2" 
                                                 width="60" height="60" 
                                                 style="object-fit: cover; border: 2px solid #10b981;">
                                            <div class="small fw-bold text-truncate"><?= htmlspecialchars($friend['username']) ?></div>
                                            <?php if (!empty($friend['full_name'])): ?>
                                                <div class="small text-muted text-truncate"><?= htmlspecialchars($friend['full_name']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($friendCount > 6): ?>
                            <div class="text-center mt-3">
                                <a href="../friends/list_friends.php?user_id=<?= $profileId ?>" class="btn btn-sm btn-outline-success">
                                    <i class="fas fa-arrow-right"></i> <?= __('view_all_friends') ?> (<?= $friendCount ?>)
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
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
            <button class="lightbox-nav lightbox-prev" id="lightboxPrev" onclick="lightboxNav(-1)"><i class="fas fa-chevron-left"></i></button>
            <img id="lightboxImage" src="" alt="Post image">
            <button class="lightbox-nav lightbox-next" id="lightboxNext" onclick="lightboxNav(1)"><i class="fas fa-chevron-right"></i></button>
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
                <p id="lightboxContent" style="color:var(--text-primary);font-size:0.9rem;line-height:1.6;margin:0;"></p>
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
                <input type="text" id="lightboxCommentInput" class="form-control form-control-sm" placeholder="<?= __('write_comment') ?>">
                <button class="btn btn-sm btn-primary" onclick="addLightboxComment()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../../fe/components/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../fe/assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../../fe/assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="../../fe/assets/js/index.js?v=<?= assetVersion('fe/assets/js/index.js') ?>"></script>

</body>
</html>
