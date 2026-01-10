<?php
session_start();
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
    <link rel="icon" href="Images/logo_rounded.png">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="Images/logo_rounded.png" alt="Logo" width="40" height="40" class="me-2">
            FISHINGLORY
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item d-flex gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <span class="text-white me-2">
                            👋 <?= htmlspecialchars($_SESSION['username']) ?>
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

<div class="container my-5 text-center py-5">
    <h1 class="display-4">Welcome to FISHINGLORY</h1>
    <p class="lead">The ultimate fishing community and marketplace.</p>
    <p>Explore, share, and connect with fellow anglers.</p>
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
