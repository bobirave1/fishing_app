<?php
/**
 * Homepage template — rendered by HomeController::index()
 * Variables: $posts, $currentPage, $totalPages, $composerUser
 */
$pageTitle = __('home') . ' | FISHINGLORY';
$pageCss = ['fe/assets/css/posts.css'];
$pageJs = ['fe/assets/js/app.js', 'fe/assets/js/index.js'];

// Start content capture
$content = function () use ($posts, $currentPage, $totalPages, $composerUser) {
?>
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

    <!-- Main Content -->
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
                    <input type="text" name="title" id="postTitleInput" class="create-post-input mb-2"
                           placeholder="<?= __('post_title_placeholder') ?>" required maxlength="200">
                    <textarea id="postContentInput" name="content" class="create-post-input composer-content-input"
                              placeholder="<?= __('post_placeholder') ?>" required maxlength="5000"></textarea>
                </div>
                <input type="file" id="postMediaInput" name="media[]" class="create-post-file-input" multiple
                       accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,video/avi,video/mov">
                <div id="postMediaPreview" class="post-media-preview" aria-live="polite"></div>
                <label for="postMediaInput" class="composer-icon-btn mt-1" title="<?= __('attach_file') ?>">
                    <i class="fas fa-images"></i>
                </label>
                <div id="postMediaFileName" class="create-post-file-name"
                     data-no-file="<?= __('no_file_selected') ?>"
                     data-selected-file="<?= __('selected_file') ?>"
                     data-files-selected="<?= __('files_selected') ?>"><?= __('no_file_selected') ?></div>
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
        <?php include dirname(__DIR__) . '/partials/post_card.php'; ?>
    <?php endforeach; ?>

    <?php if ($totalPages > 1): ?>
        <nav aria-label="Feed pagination" class="my-4 d-flex justify-content-center">
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= max(1, $currentPage - 1) ?>"><span>&laquo;</span></a>
                </li>
                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                for ($pg = $startPage; $pg <= $endPage; $pg++):
                ?>
                    <li class="page-item <?= $pg === $currentPage ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $pg ?>"><?= $pg ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= min($totalPages, $currentPage + 1) ?>"><span>&raquo;</span></a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    </div>

    <!-- Right Sidebar -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="col-md-3 col-lg-3">
        <div class="sidebar-modern sidebar-sticky">
            <div class="weather-widget glass-card mb-4">
                <div class="weather-content">
                    <h5 class="sidebar-title"><i class="fas fa-cloud-sun"></i> <?= __('current_weather') ?></h5>
                    <div id="weather-info"><p><?= __('fetching_weather') ?></p></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Edit Post Modal -->
<div class="modal fade" id="editPostModal" tabindex="-1" aria-labelledby="editPostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editPostModalLabel"><i class="fas fa-edit"></i> <?= __('edit_post') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="editPostBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-primary" onclick="submitEditForm()"><i class="fas fa-save"></i> <?= __('save') ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Post Modal -->
<div class="modal fade" id="deletePostModal" tabindex="-1" aria-labelledby="deletePostModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-danger">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePostModalLabel"><i class="fas fa-trash"></i> <?= __('delete_post') ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="deletePostBody">
                <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <button type="button" class="btn btn-danger" onclick="confirmDeletePost()"><i class="fas fa-trash-alt"></i> <?= __('delete_permanently') ?></button>
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
                <input type="text" id="lightboxCommentInput" class="form-control form-control-sm" placeholder="<?= __('write_comment') ?>">
                <button class="btn btn-sm btn-primary" onclick="addLightboxComment()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </div>
    </div>
</div>
<?php
}; // end $content closure

require dirname(__DIR__) . '/layouts/main.php';
