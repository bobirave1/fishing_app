<?php
session_start();
require 'config/database.php';

$posts = [];

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT p.*, u.username
        FROM posts p
        JOIN users u ON u.id = p.user_id
        WHERE
            p.visibility = 'public'
         OR (p.visibility = 'friends' AND p.user_id IN (
                SELECT friend_id FROM friends WHERE user_id = ?
            ))
         OR p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$userId, $userId]);
    $posts = $stmt->fetchAll();
} else {
    // за гости – само public постове
    $stmt = $pdo->query("
        SELECT p.*, u.username
        FROM posts p
        JOIN users u ON u.id = p.user_id
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
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="fe/assets/img/logo_rounded.png">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="fe/assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
            FISHINGLORY
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item d-flex gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-white">
                            <img src="fe/assets/img/default-avatar.png" width="40" height="40" class="rounded-circle me-1">
                             <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <a href="auth/logout.php" class="btn btn-light">
                            <i class="fa fa-sign-out-alt me-1"></i> Logout
                        </a>


                    <?php else: ?>
                        <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="fa fa-sign-in-alt me-1"></i> Login
                        </button>
                        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#registerModal">
                            <i class="fa fa-user-plus me-1"></i> Register
                        </button>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container my-5 py-5">

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="row g-4 justify-content-center">


        <!-- Profile -->
        <div class="col-md-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <i class="fa fa-user fa-3x mb-3 text-primary"></i>
                    <h5>My Profile</h5>
                    <a href="profile.php?id=<?= $_SESSION['user_id'] ?>" class="btn btn-primary w-100">
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
                    <a href="friends/list_friends.php" class="btn btn-success w-100">
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
                    <a href="friends/list_requests.php" class="btn btn-warning w-100">
                        View Requests
                    </a>
                </div>
            </div>
        </div>

    </div>

<?php else: ?>

    <div class="text-center">
        <h1 class="display-4">Welcome to FISHINGLORY</h1>
        <p class="lead">The ultimate fishing community and marketplace.</p>
        <p>Login or register to unlock all features 🎣</p>
    </div>

<?php endif; ?>
                        <div class="card mb-4 shadow-sm">
                            <div class="card-body">
                                <form action="posts/create.php" method="post" enctype="multipart/form-data">
                                    <input type="text" name="title" class="form-control mb-2"
                                        placeholder="Post title" required>

                                    <textarea name="content" class="form-control mb-2"
                                            placeholder="Share your catch..." required></textarea>

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
                        <?php foreach ($posts as $p): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h5><?= htmlspecialchars($p['title']) ?></h5>

            <strong><?= htmlspecialchars($p['username']) ?></strong>

            <p class="mt-2">
                <?= nl2br(htmlspecialchars($p['content'])) ?>
            </p>

            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>"
                     class="img-fluid rounded mb-2">
            <?php endif; ?>

            <div class="d-flex justify-content-between">
                <small class="text-muted">
                    <?= $p['created_at'] ?>
                </small>
                <small class="text-muted">
                    <?= strtoupper($p['visibility']) ?>
                </small>
            </div>
        </div>
    </div>
<?php endforeach; ?>

</div> <!-- container -->
</div>


</div>


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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Load login form
    document.getElementById('loginModal').addEventListener('show.bs.modal', function () {
        fetch('login_form.php')
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
        fetch('register_form.php')
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
</script>

</body>
</html>
