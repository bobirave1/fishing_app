<?php
require 'config/security.php';
secureSession();
require 'config/database.php';
require 'config/avatar_helper.php';
setSecurityHeaders();

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
        /* Modern Search & Engagement Styles */
        .search-results-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 10px 30px rgba(0,0,0,0.12);
            margin-top: 8px;
        }
        .search-category {
            padding: 10px 16px;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            font-size: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .search-item {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .search-item:hover {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            transform: translateX(4px);
        }
        .comment-section {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
            border: 1px solid #e2e8f0;
        }
        .comment-item {
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .engagement-buttons {
            display: flex;
            gap: 12px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e2e8f0;
        }
        .engagement-btn {
            flex: 1;
            border: none;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            padding: 10px 16px;
            font-size: 0.95rem;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .engagement-btn:hover {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: #0891b2;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(8, 145, 178, 0.15);
        }
        .engagement-btn.liked {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.08);
        }
        .engagement-btn.liked:hover {
            background: rgba(239, 68, 68, 0.12);
            color: #dc2626;
        }
        .engagement-btn.following {
            color: #10b981;
            background: rgba(16, 185, 129, 0.08);
        }
        .engagement-btn.following:hover {
            background: rgba(16, 185, 129, 0.12);
            color: #059669;
        }
        .follow-btn {
            padding: 6px 16px;
            font-size: 0.9rem;
            font-weight: 600;
            border-radius: 8px;
        }
    </style>
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>">

<?php include 'fe/components/navbar.php'; ?>

<div class="container-fluid my-5 py-3">
    <div class="row">
        <!-- Left Sidebar -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="col-md-3 col-lg-2">
            <div class="sticky-top" style="top: 70px;">
                <div class="list-group">
                    <a href="be/users/profile.php?id=<?= $_SESSION['user_id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 border-0">
                        <i class="fas fa-user-circle text-primary" style="font-size: 24px;"></i>
                        <span style="font-weight: 500;">My Profile</span>
                    </a>
                    <a href="be/friends/list_friends.php" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 border-0">
                        <i class="fas fa-user-friends text-success" style="font-size: 24px;"></i>
                        <span style="font-weight: 500;">Friends</span>
                    </a>
                    <a href="be/friends/list_requests.php" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 border-0">
                        <i class="fas fa-user-plus text-warning" style="font-size: 24px;"></i>
                        <span style="font-weight: 500;">Friend Requests</span>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="<?= isset($_SESSION['user_id']) ? 'col-md-9 col-lg-10' : 'col-12' ?>">

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
                <?= getCsrfField() ?>
                <input type="text" name="title" class="form-control mb-2" placeholder="Post title" required maxlength="200">
                <textarea name="content" class="form-control mb-2" placeholder="Share your catch..." required maxlength="5000"></textarea>
                <select name="visibility" class="form-select mb-2">
                    <option value="public">🌍 Public</option>
                    <option value="friends">👥 Friends</option>
                    <option value="private">🔒 Only me</option>
                </select>
                <input type="file" name="image" class="form-control mb-2" accept="image/jpeg,image/png,image/gif,image/webp">
                <button class="btn btn-primary w-100">Post</button>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Posts feed -->
<?php foreach ($posts as $p): 
    $avatar = getUserAvatar($p['avatar_url'] ?? null);
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
                <button type="button" class="btn btn-primary" onclick="submitEditForm()">
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
<script src="fe/assets/js/avatar_helper.js?v=<?= time() ?>"></script>
<script src="fe/assets/js/app.js?v=<?= time() ?>"></script>
<script>
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

    // Submit edit form
    function submitEditForm() {
        const form = document.getElementById('editPostForm');
        if (!form) {
            alert('Form not found');
            return;
        }
        
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const formData = new FormData(form);

        fetch('be/posts/edit.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editPostModal'));
                if (modal) modal.hide();
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Error: ' + (data.message || data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the post.');
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

    // Weather widget with improved error handling
    if (navigator.geolocation) {
        console.log('Geolocation supported - requesting position...');
        
        navigator.geolocation.getCurrentPosition(function(position) {
            console.log('Position obtained:', position.coords.latitude, position.coords.longitude);
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            document.getElementById('weather-info').innerHTML = '<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading weather...</p>';
            
            fetch(`be/weather/get_weather.php?lat=${lat}&lon=${lon}`)
                .then(response => {
                    console.log('Weather API response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Weather data:', data);
                    if (data.error) {
                        document.getElementById('weather-info').innerHTML = `
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> ${data.error}
                                <button class="btn btn-sm btn-outline-primary mt-2 d-block" onclick="location.reload()">
                                    <i class="fas fa-sync"></i> Retry
                                </button>
                            </div>
                        `;
                    } else {
                        let fishingTip = '';
                        if (data.wind_speed < 5) fishingTip = 'Great day for fishing! Low wind speeds are ideal.';
                        else if (data.wind_speed < 10) fishingTip = 'Moderate wind, still suitable for most fishing activities.';
                        else fishingTip = 'High wind speeds may make fishing challenging or unsafe.';

                        document.getElementById('weather-info').innerHTML = `
                            <div class="row text-center">
                                <div class="col-12 mb-3">
                                    <h5><i class="fas fa-map-marker-alt"></i> ${data.location}${data.country ? ', ' + data.country : ''}</h5>
                                    <img src="https://openweathermap.org/img/wn/${data.icon}@2x.png" alt="weather icon" class="img-fluid" style="max-width: 80px;">
                                    <p class="mb-0 fs-5">${data.description}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="fas fa-thermometer-half text-danger"></i> <strong>Temperature:</strong> ${data.temperature}°C</p>
                                    <p><i class="fas fa-wind text-info"></i> <strong>Wind:</strong> ${data.wind_speed} m/s (${data.wind_direction}°)</p>
                                    <p><i class="fas fa-tint text-primary"></i> <strong>Humidity:</strong> ${data.humidity}%</p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="fas fa-eye text-success"></i> <strong>Visibility:</strong> ${data.visibility} km</p>
                                    <p><i class="fas fa-gauge text-warning"></i> <strong>Pressure:</strong> ${data.pressure} hPa</p>
                                    ${data.sea_level ? `<p><i class="fas fa-water"></i> <strong>Sea Level:</strong> ${data.sea_level} hPa</p>` : ''}
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="alert alert-success mb-0">
                                        <i class="fas fa-fish"></i> <strong>Fishing Tip:</strong> ${fishingTip}
                                    </div>
                                </div>
                            </div>
                        `;
                    }
                })
                .catch((error) => {
                    console.error('Weather fetch error:', error);
                    document.getElementById('weather-info').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> <strong>Error loading weather</strong><br>
                            ${error.message || 'Network error'}
                            <button class="btn btn-sm btn-outline-primary mt-2 d-block" onclick="location.reload()">
                                <i class="fas fa-sync"></i> Retry
                            </button>
                        </div>
                    `;
                });
        }, function(error) {
            console.error('Geolocation error:', error.code, error.message);
            
            let errorMessage = '';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMessage = 'Location access was denied. Please enable location services in your browser settings.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMessage = 'Location information is unavailable. Please check your device settings.';
                    break;
                case error.TIMEOUT:
                    errorMessage = 'Location request timed out. Please try again.';
                    break;
                default:
                    errorMessage = 'An unknown error occurred while getting your location.';
            }
            
            document.getElementById('weather-info').innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Location Access Needed</strong><br>
                    ${errorMessage}
                    <button class="btn btn-sm btn-primary mt-2 d-block" onclick="location.reload()">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            `;
        }, {
            enableHighAccuracy: false,
            timeout: 10000,
            maximumAge: 300000 // Cache for 5 minutes
        });
    } else {
        console.error('Geolocation not supported');
        document.getElementById('weather-info').innerHTML = `
            <div class="alert alert-secondary">
                <i class="fas fa-times-circle"></i> Your browser doesn't support geolocation.
                <br><small class="text-muted">Please use a modern browser like Chrome, Firefox, or Edge.</small>
            </div>
        `;
    }
</script>

        </div><!-- Close main content -->
    </div><!-- Close row -->
</div><!-- Close container-fluid -->

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>