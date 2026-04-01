<?php
/**
 * Post card partial — reusable post rendering.
 * Variables: $p (post row), $avatar, $postMedia
 */
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
            <input type="text" id="comment-input-<?= $p['id'] ?>" class="form-control form-control-sm" placeholder="Write a comment...">
            <button class="btn btn-sm btn-primary" onclick="addComment(<?= $p['id'] ?>)">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
    <?php else: ?>
    <div class="engagement-buttons">
        <button class="engagement-btn" disabled><i class="far fa-heart"></i> <?= $p['like_count'] ?></button>
        <button class="engagement-btn" disabled><i class="far fa-comment"></i> <?= $p['comment_count'] ?></button>
    </div>
    <?php endif; ?>
</div>
