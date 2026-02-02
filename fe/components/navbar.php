<?php
// Get user avatar for navbar
require_once dirname(__DIR__, 2) . '/config/avatar_helper.php';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT avatar_url FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    
    $navbarAvatar = getUserAvatar($profile['avatar_url'] ?? null);
}
?>

<!-- HEADER -->
<nav class="fb-navbar">
    <!-- Left: Logo + Search -->
    <div class="fb-navbar-left">
        <?php
        $currentPath = $_SERVER['PHP_SELF'];
        
        // Determine paths based on current location
        if (strpos($currentPath, '/fe/pages/') !== false) {
            $indexPath = '../../index.php';
            $logoPath = '../assets/img/logo_rounded.png';
        } elseif (strpos($currentPath, '/be/') !== false) {
            $indexPath = '../../index.php';
            $logoPath = '../../fe/assets/img/logo_rounded.png';
        } else {
            $indexPath = 'index.php';
            $logoPath = 'fe/assets/img/logo_rounded.png';
        }
        ?>
        <a href="<?= $indexPath ?>">
            <img src="<?= $logoPath ?>" alt="Logo" class="fb-logo">
        </a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="dropdown">
            <button class="fb-search-input search-container" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" 
                    onclick="this.nextElementSibling.querySelector('input').focus()">
                Search Fishinglory
            </button>
            <div class="dropdown-menu fb-search-dropdown p-0">
                <div class="p-2">
                    <input type="text" id="searchInput" class="form-control border-0 search-input-field" 
                           placeholder="Search Fishinglory" oninput="performSearch(this.value)">
                </div>
                <div id="searchResults"></div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Center: Main Nav -->
    <?php if (isset($_SESSION['user_id'])): ?>
    <div class="fb-navbar-center d-none d-md-flex">
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        $currentPath = $_SERVER['PHP_SELF'];
        
        // Determine paths based on current location
        if (strpos($currentPath, '/fe/pages/') !== false) {
            // We're in fe/pages/
            $homePath = '../../index.php';
            $messagesPath = 'messages.php';
            $activityPath = 'activity_feed.php';
        } elseif (strpos($currentPath, '/be/') !== false) {
            // We're in backend (be/users/, be/friends/, etc.)
            $homePath = '../../index.php';
            $messagesPath = '../../fe/pages/messages.php';
            $activityPath = '../../fe/pages/activity_feed.php';
        } else {
            // We're in root (index.php)
            $homePath = 'index.php';
            $messagesPath = 'fe/pages/messages.php';
            $activityPath = 'fe/pages/activity_feed.php';
        }
        ?>
        <a href="<?= $homePath ?>" class="fb-nav-btn <?= ($currentPage == 'index.php') ? 'active' : '' ?>">
            <i class="fas fa-home fs-4"></i>
        </a>
        <a href="<?= $messagesPath ?>" class="fb-nav-btn <?= ($currentPage == 'messages.php') ? 'active' : '' ?>">
            <i class="fas fa-comments fs-4"></i>
        </a>
        <a href="<?= $activityPath ?>" class="fb-nav-btn <?= ($currentPage == 'activity_feed.php') ? 'active' : '' ?>">
            <i class="fas fa-fish fs-4"></i>
        </a>
    </div>
    <?php endif; ?>
    
    <!-- Right: Notifications + Profile -->
    <div class="fb-navbar-right">
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Notifications -->
        <div class="dropdown">
            <button class="fb-icon-btn" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-bell"></i>
                <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none notification-badge">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end navbar-dropdown">
                <li class="dropdown-header d-flex justify-content-between align-items-center">
                    <strong>Notifications</strong>
                    <button class="btn btn-sm btn-link text-decoration-none" onclick="markAllNotificationsRead()">Mark all read</button>
                </li>
                <li><hr class="dropdown-divider"></li>
                <div id="notificationList">
                    <li><span class="dropdown-item-text text-muted">No new notifications</span></li>
                </div>
            </ul>
        </div>

        <!-- Profile -->
        <div class="dropdown">
            <?php
            // Determine paths based on current location
            if (strpos($currentPath, '/fe/pages/') !== false) {
                $profilePath = '../../be/users/profile.php';
                $editProfilePath = 'edit_profile.php';
                $logoutPath = '../../be/auth/logout.php';
            } elseif (strpos($currentPath, '/be/') !== false) {
                $profilePath = '../users/profile.php';
                $editProfilePath = '../../fe/pages/edit_profile.php';
                $logoutPath = '../auth/logout.php';
            } else {
                $profilePath = 'be/users/profile.php';
                $editProfilePath = 'fe/pages/edit_profile.php';
                $logoutPath = 'be/auth/logout.php';
            }
            ?>
            <button class="fb-profile-btn" type="button" data-bs-toggle="dropdown">
                <img src="<?= htmlspecialchars($navbarAvatar) ?>" class="fb-avatar">
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="<?= $profilePath ?>?id=<?= $_SESSION['user_id'] ?>">
                    <i class="fas fa-user me-2"></i> My Profile
                </a></li>
                <li><a class="dropdown-item" href="<?= $editProfilePath ?>">
                    <i class="fas fa-edit me-2"></i> Edit Profile
                </a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= $logoutPath ?>">
                    <i class="fa fa-sign-out-alt me-2"></i> Logout
                </a></li>
            </ul>
        </div>
        <?php else: ?>
        <!-- Guest Navigation -->
        <?php
        if (strpos($currentPath, '/fe/pages/') !== false) {
            $loginPath = '../auth/login_form.php';
            $registerPath = '../auth/register_form.php';
        } elseif (strpos($currentPath, '/be/') !== false) {
            $loginPath = '../../fe/auth/login_form.php';
            $registerPath = '../../fe/auth/register_form.php';
        } else {
            $loginPath = 'fe/auth/login_form.php';
            $registerPath = 'fe/auth/register_form.php';
        }
        ?>
        <a href="<?= $loginPath ?>" class="btn btn-outline-primary me-2">Login</a>
        <a href="<?= $registerPath ?>" class="btn btn-primary">Sign Up</a>
        <?php endif; ?>
    </div>
</nav>

<?php if (isset($_SESSION['user_id'])): ?>
<script src="<?= $basePath ?>fe/assets/js/navbar.js?v=<?= time() ?>"></script>
<?php endif; ?>
