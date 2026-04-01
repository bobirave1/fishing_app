<?php
require __DIR__ . '/../../config/security.php';
secureSession();
setSecurityHeaders();

require __DIR__ . '/../../config/database.php';

require __DIR__ . '/../../config/languages.php';

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
    <link rel="stylesheet" href="../assets/css/gamification.css?v=<?= assetVersion('fe/assets/css/gamification.css') ?>">
    <link rel="stylesheet" href="../assets/css/components.css?v=<?= assetVersion('fe/assets/css/components.css') ?>">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body data-user-id="<?= isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0 ?>" data-csrf-token="<?= generateCsrfToken() ?>" data-theme="<?= $_SESSION['theme'] ?? 'light' ?>">
<?php include __DIR__ . '/../components/navbar.php'; ?>

<main class="messages-page container-fluid mt-5 mb-0 py-3">
    <section class="messages-hero glass-card">
        <div class="messages-hero-copy">
            <span class="messages-hero-eyebrow">FISHINGLORY</span>
            <h1><?= __('messages') ?></h1>
            <p><?= __('search_friend_placeholder') ?> <?= __('select_conversation') ?></p>
        </div>
        <div class="messages-hero-stats">
            <div class="messages-hero-stat">
                <i class="fas fa-feather-pointed"></i>
                <span><?= __('new_conversation') ?></span>
            </div>
            <div class="messages-hero-stat">
                <i class="fas fa-paper-plane"></i>
                <span><?= __('type_message') ?></span>
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-start">
        <div class="col-12 col-lg-4 col-xl-3">
            <aside class="sidebar-modern messages-sidebar-wrap">
                <div class="sidebar-card messages-sidebar-panel">
                    <div class="messages-panel-heading">
                        <div>
                            <span class="messages-panel-kicker"><?= __('messages') ?></span>
                            <h2><?= __('new_conversation') ?></h2>
                        </div>
                        <div class="messages-panel-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>

                    <div class="friend-search-box">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" id="friendSearchInput" class="form-control" placeholder="<?= __('search_friend_placeholder') ?>" autocomplete="off">
                        </div>
                        <div id="friendSearchResults" class="friend-search-results d-none"></div>
                    </div>

                    <div class="conversation-list-meta">
                        <span><i class="fas fa-inbox"></i> <?= __('messages') ?></span>
                    </div>

                    <div id="conversationsList" class="conversation-list-scroll">
                        <p class="text-center text-muted p-3"><i class="fas fa-spinner fa-spin"></i> <?= __('loading_conversations') ?></p>
                    </div>
                </div>
            </aside>
        </div>

        <div class="col-12 col-lg-8 col-xl-9">
            <section class="messages-shell create-post-modern">
                <div class="conversation-thread d-none" id="conversationView">
                    <div id="conversationHeader" class="conversation-header">
                        <button class="btn btn-sm btn-outline-secondary d-lg-none me-2" id="backToListBtn" type="button">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <img id="convHeaderAvatar" class="rounded-circle" width="42" height="42" style="object-fit: cover;" alt="avatar">
                        <div class="conversation-header-meta">
                            <span id="convHeaderName" class="fw-bold"></span>
                            <small><?= __('messages') ?></small>
                        </div>
                    </div>

                    <div class="messages-scroll-area flex-grow-1">
                        <div id="conversationThread" style="display: flex; flex-direction: column; gap: 10px;"></div>
                    </div>

                    <div class="message-input-area">
                        <div class="input-group">
                            <input type="file" id="fileInput" class="d-none" accept="image/*,video/*" multiple>
                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-paperclip"></i>
                            </button>
                            <input type="text" id="messageInput" class="form-control" placeholder="<?= __('type_message') ?>">
                            <button class="btn btn-primary" type="button" onclick="sendMessageToCurrentUser()">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <div id="filePreview" class="mt-2"></div>
                    </div>
                </div>

                <div class="conversation-empty-state" id="noConversationView">
                    <div class="conversation-empty-ornament"></div>
                    <div class="conversation-empty-content text-center text-muted">
                        <span class="conversation-empty-badge"><?= __('messages') ?></span>
                        <i class="fas fa-comments conversation-empty-icon"></i>
                        <h3><?= __('new_conversation') ?></h3>
                        <p><?= __('select_conversation') ?></p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= assetVersion('fe/assets/js/avatar_helper.js') ?>"></script>
<script src="../assets/js/helpers.js?v=<?= assetVersion('fe/assets/js/helpers.js') ?>"></script>
<script src="../assets/js/app.js?v=<?= assetVersion('fe/assets/js/app.js') ?>"></script>
<script src="../assets/js/notifications.js?v=<?= assetVersion('fe/assets/js/notifications.js') ?>"></script>
<script src="../assets/js/messages.js?v=<?= assetVersion('fe/assets/js/messages.js') ?>"></script>
<script src="../assets/js/chat.js?v=<?= assetVersion('fe/assets/js/chat.js') ?>"></script>

<?php include __DIR__ . '/../components/footer.php'; ?>

</body>
</html>
