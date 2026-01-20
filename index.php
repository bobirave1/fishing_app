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
    <link rel="stylesheet" href="fe/assets/css/style.css">
    <link rel="icon" href="fe/assets/img/logo_rounded.png">
</head>
<body>

<!-- HEADER -->
<nav class="navbar navbar-expand navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid px-4">

        <!-- Logo + Search -->
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="fe/assets/img/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
                <span class="fw-bold fs-4 brand-color">FISHINGLORY</span>
            </a>
            <input type="text" class="form-control form-control-sm rounded-pill bg-light border-0"
                   placeholder="Търси риболовци, водоеми...">
        </div>

        <!-- Main Navigation -->
        <ul class="navbar-nav mx-auto d-none d-md-flex flex-row gap-4">
            <li class="nav-item"><a class="nav-link active"><i class="fas fa-home fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link"><i class="fas fa-map-marked-alt fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link"><i class="fas fa-fish fs-4"></i></a></li>
            <li class="nav-item"><a class="nav-link"><i class="fas fa-calendar fs-4"></i></a></li>
        </ul>

        <!-- Profile / Login-Register -->
        <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item d-flex gap-2">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="be/users/profile.php?id=<?= $_SESSION['user_id'] ?>" class="d-flex align-items-center text-dark text-decoration-none">
                        <img src="fe/assets/img/default-avatar.png" width="40" height="40" class="rounded-circle me-1">
                        <?= htmlspecialchars($_SESSION['username']) ?>
                    </a>
                    <a href="be/auth/logout.php" class="btn btn-light ms-2">
                        <i class="fa fa-sign-out-alt me-1"></i> Logout
                    </a>
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
<?php foreach ($posts as $p): ?>
    <div class="card mb-3 shadow-sm">
        <div class="card-body">
            <h5><?= htmlspecialchars($p['title']) ?></h5>
            <strong><?= htmlspecialchars($p['username']) ?></strong>
            <p class="mt-2"><?= nl2br(htmlspecialchars($p['content'])) ?></p>
            <?php if (!empty($p['image'])): ?>
                <img src="<?= htmlspecialchars($p['image']) ?>" class="img-fluid rounded mb-2">
            <?php endif; ?>
            <div class="d-flex justify-content-between">
                <small class="text-muted"><?= $p['created_at'] ?></small>
                <small class="text-muted"><?= strtoupper($p['visibility']) ?></small>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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