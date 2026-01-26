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

<style>
    body {
        padding-top: 60px;
    }
    
    .fb-navbar {
        height: 56px;
        background: #242526;
        border-bottom: 1px solid #393a3b;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        display: flex;
        align-items: center;
        padding: 0;
        justify-content: space-between;
    }
    
    .fb-navbar-left {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
        padding-left: 16px;
    }
    
    .fb-navbar-right {
        flex: 1;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        padding-right: 16px;
    }
    
    .fb-logo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
    }
    
    .fb-search-wrapper {
        position: relative;
    }
    
    .fb-search-input {
        width: 240px;
        height: 40px;
        background: #3a3b3c;
        border: none;
        border-radius: 50px;
        padding: 0 16px;
        font-size: 15px;
        outline: none;
        color: #e4e6eb;
    }
    
    .fb-search-input::placeholder {
        color: #b0b3b8;
    }
    
    .fb-search-input:focus {
        background: #4e4f50;
    }100%;
        left: 0;
        width: 320px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        max-height: 500px;
        overflow-y: auto;
        margin-top: 8px 8px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        max-height: 500px;
        overflow-y: auto;
        z-index: 10000;
    }
    
    .fb-search-category {
        padding: 8px 16px;
        font-size: 14px;
        font-weight: 600;
        color: #65676b;
    }
    
    .fb-search-item {
        padding: 8px 8px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        color: #050505;
        cursor: pointer;
    }
    
    .fb-search-item:hover {
        background: #f2f3f5;
    }
    
    .fb-navbar-center {
        flex: 1;
        display: flex;
        justify-content: center;
        gap: 8px;
    }
    
    .fb-nav-btn {
        width: 110px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        border-radius: 8px;
        color: #b0b3b8;
        cursor: pointer;
        position: relative;
        text-decoration: none;
    }
    
    .fb-nav-btn:hover {
        background: #3a3b3c;
        color: #e4e6eb;
    }
    
    .fb-nav-btn.active {
        color: #2d88ff;
        border-bottom: 3px solid #2d88ff;
    }
    
    .fb-icon-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #3a3b3c;
        border: none;
        border-radius: 50%;
        color: #e4e6eb;
        cursor: pointer;
        position: relative;
        padding: 0;
    }
    
    .fb-icon-btn i {
        font-size: 18px;
        line-height: 1;
        margin: 0 auto;
        text-align: center;
    }
    
    .fb-icon-btn:hover {
        background: #4e4f50;
    }
    
    .fb-profile-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: transparent;
        border: none;
        padding: 0;
        cursor: pointer;
        border-radius: 50%;
        overflow: hidden;
    }
    
    .fb-profile-btn:hover {
        opacity: 0.85;
    }
    
    .fb-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
    }
    
    .dropdown-menu {
        box-shadow: 0 4px 16px rgba(0,0,0,0.2);
        border: none;
        border-radius: 8px;
    }
</style>

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
            <button class="fb-search-input" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" 
                    style="text-align: left; cursor: pointer;" onclick="this.nextElementSibling.querySelector('input').focus()">
                Search Fishinglory
            </button>
            <div class="dropdown-menu fb-search-dropdown p-0">
                <div class="p-2">
                    <input type="text" id="searchInput" class="form-control border-0" 
                           placeholder="Search Fishinglory" oninput="performSearch(this.value)" 
                           style="background: #f0f2f5; outline: none;">
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
                <span id="notificationBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size: 10px; padding: 2px 5px;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" style="min-width: 320px; max-height: 400px; overflow-y: auto;">
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
<script>
// Auto-load notifications every 30 seconds
document.addEventListener('DOMContentLoaded', function() {
    if (typeof loadNotifications === 'function') {
        loadNotifications();
        setInterval(loadNotifications, 30000);
    }
});
</script>
<?php endif; ?>
