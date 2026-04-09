<?php
require '../../config/security.php';
secureSession();
setSecurityHeaders();

require '../../config/database.php';
require '../../config/avatar_helper.php';

// Set default language to Bulgarian for diploma project BEFORE requiring languages.php
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}

require '../../config/languages.php';

// Handle language/theme switches via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrf)) {
        http_response_code(400);
        die('Invalid CSRF token');
    }

    $action = $_POST['action'];
    if ($action === 'switch_lang' && isset($_POST['lang'])) {
        $newLang = (string) $_POST['lang'];
        if (in_array($newLang, ['bg', 'en'], true)) {
            $_SESSION['lang'] = $newLang;
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }

    if ($action === 'switch_theme' && isset($_POST['theme'])) {
        $newTheme = (string) $_POST['theme'];
        if (in_array($newTheme, ['light', 'dark'], true)) {
            $_SESSION['theme'] = $newTheme;
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}

$currentUserId = $_SESSION['user_id'];

// Pre-fetch friends list server-side for initial render
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.username, u.full_name, up.avatar_url
    FROM (
        SELECT friend_id as friend FROM friends WHERE user_id = ?
        UNION
        SELECT user_id as friend FROM friends WHERE friend_id = ?
    ) f
    JOIN users u ON u.id = f.friend
    LEFT JOIN user_profiles up ON u.id = up.user_id
    ORDER BY u.username ASC
");
$stmt->execute([$currentUserId, $currentUserId]);
$friendsList = $stmt->fetchAll();

// Check if we should open a specific conversation (e.g. from profile page link)
$openUserId = (int)($_GET['user'] ?? 0);
?>
<!DOCTYPE html>
<html lang="<?= getCurrentLang() ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('messages') ?> | FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= assetVersion('fe/assets/css/style.css') ?>">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=<?= assetVersion('fe/assets/css/navbar.css') ?>">
    <link rel="stylesheet" href="../assets/css/messages.css?v=<?= assetVersion('fe/assets/css/messages.css') ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body data-user-id="<?= $currentUserId ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">

<?php include '../components/navbar.php'; ?>

<div class="messages-page-wrapper">
    <div class="messages-layout glass-card">

        <!-- LEFT PANEL -->
        <div class="messages-left-panel">
            <!-- Panel Header with Tabs -->
            <div class="messages-panel-header">
                <h5 class="mb-0"><i class="fas fa-comments me-2"></i><?= __('messages') ?></h5>
                <div class="messages-tabs mt-3">
                    <button class="msg-tab active" id="tabConvBtn" onclick="switchMsgTab('conversations')">
                        <i class="fas fa-inbox"></i> <?= __('conversations') ?>
                        <span class="unread-dot" id="unreadDot" style="display:none;"></span>
                    </button>
                    <button class="msg-tab" id="tabFriendsBtn" onclick="switchMsgTab('friends')">
                        <i class="fas fa-users"></i> <?= __('friends') ?>
                    </button>
                </div>
                <!-- Search inside panel -->
                <div class="messages-search mt-2">
                    <input type="text" id="msgSearch" class="form-control form-control-sm" placeholder="<?= __('search') ?>..." oninput="filterMsgPanel(this.value)">
                </div>
            </div>

            <!-- Conversations List -->
            <div id="panelConversations" class="panel-list">
                <div id="conversationsList">
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p class="small"><?= __('loading_conversations') ?></p>
                    </div>
                </div>
            </div>

            <!-- Friends List -->
            <div id="panelFriends" class="panel-list d-none">
                <?php if (empty($friendsList)): ?>
                    <div class="text-center p-4 text-muted">
                        <i class="fas fa-user-friends fa-2x mb-2 d-block"></i>
                        <p class="small"><?= __('no_friends') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($friendsList as $friend):
                        $fa = getUserAvatar($friend['avatar_url'] ?? null);
                    ?>
                    <div class="conversation-item friend-item"
                         data-user-id="<?= $friend['id'] ?>"
                         data-name="<?= htmlspecialchars(strtolower($friend['username'] . ' ' . ($friend['full_name'] ?? ''))) ?>"
                         onclick="openConversation(<?= $friend['id'] ?>)">
                        <img src="<?= htmlspecialchars($fa) ?>" class="conversation-avatar" alt="">
                        <div class="conversation-info">
                            <div class="conversation-name"><?= htmlspecialchars($friend['username']) ?></div>
                            <?php if (!empty($friend['full_name'])): ?>
                                <div class="conversation-preview"><?= htmlspecialchars($friend['full_name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <i class="fas fa-paper-plane text-muted small"></i>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- RIGHT PANEL - Chat Area -->
        <div class="messages-right-panel">

            <!-- No Conversation Selected (default) -->
            <div id="noConversationView" class="messages-empty-state">
                <div class="text-center">
                    <i class="fas fa-comments fa-4x mb-3" style="color: var(--primary-light); opacity: 0.6;"></i>
                    <h5 style="color: var(--text-secondary);"><?= __('select_conversation') ?></h5>
                    <p class="small" style="color: var(--text-muted);">
                        <?= __('friends') ?> &mdash; <?= count($friendsList) ?>
                    </p>
                </div>
            </div>

            <!-- Active Conversation -->
            <div id="conversationView" class="d-none conversation-view-inner">
                <!-- Chat Header -->
                <div class="chat-header">
                    <button class="btn btn-sm btn-link me-2 d-md-none" onclick="closeConversation()" style="color: var(--text-primary);">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <img id="chatAvatar" src="" class="conversation-avatar me-3" alt="">
                    <div class="flex-grow-1">
                        <div class="fw-bold" id="chatName" style="color: var(--text-primary);"></div>
                        <div class="small" style="color: var(--text-muted);" id="chatStatus"></div>
                    </div>
                    <a id="chatProfileLink" href="#" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-user"></i>
                    </a>
                </div>

                <!-- Messages Scroll Area -->
                <div class="messages-scroll-area" id="messagesScrollArea">
                    <div id="conversationThread"></div>
                </div>

                <!-- Input Area -->
                <div class="message-input-area">
                    <input type="file" id="fileInput" class="d-none" accept="image/*,video/*" multiple>
                    <div class="message-input-wrapper">
                        <button class="btn btn-outline-secondary btn-sm" title="<?= __('attach') ?>" onclick="document.getElementById('fileInput').click()">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <textarea id="messageInput" class="message-input" rows="1" placeholder="<?= __('type_message') ?>..."></textarea>
                        <button class="message-send-btn" onclick="sendMessageToCurrentUser()" title="<?= __('send') ?>">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    <div id="filePreview" class="file-preview-strip"></div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="../assets/js/messages.js?v=<?= assetVersion('fe/assets/js/messages.js') ?>"></script>
<?php if ($openUserId > 0): ?>
<script>document.addEventListener('DOMContentLoaded', function() { openConversation(<?= $openUserId ?>); });</script>
<?php endif; ?>
</body>
</html>
