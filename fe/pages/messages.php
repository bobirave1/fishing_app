<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/navbar.css">
    <link rel="stylesheet" href="../assets/css/messages.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/modern-theme.css">
    <link rel="stylesheet" href="../assets/css/messages_inline.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
</head>
<body>
<?php
session_start();
require '../../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../index.php');
    exit;
}
?>

<?php include '../components/navbar.php'; ?>

<div class="container-fluid my-4">
    <div class="row h-100">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-comments"></i> Messages</h5>
                </div>
                <div class="messages-container">
                    <!-- Conversations List -->
                    <div class="conversation-list">
                        <div id="conversationsList" style="min-height: 100%;">
                            <p class="text-center text-muted p-3"><i class="fas fa-spinner fa-spin"></i> Loading conversations...</p>
                        </div>
                    </div>

                    <!-- Conversation Thread -->
                    <div class="conversation-thread d-none" id="conversationView">
                        <div class="flex-grow-1">
                            <div id="conversationThread" style="display: flex; flex-direction: column; gap: 10px;"></div>
                        </div>
                        <div class="message-input-area">
                            <div class="input-group">
                                <input type="text" id="messageInput" class="form-control" placeholder="Type a message...">
                                <button class="btn btn-primary" onclick="sendMessageToCurrentUser()">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- No Conversation Selected -->
                    <div class="flex-grow-1 d-flex align-items-center justify-content-center bg-light" id="noConversationView">
                        <div class="text-center text-muted">
                            <i class="fas fa-comments fa-5x mb-3 text-secondary"></i>
                            <p class="fs-5">Select a conversation to start messaging</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/avatar_helper.js?v=<?= time() ?>"></script>
<script src="../assets/js/app.js?v=<?= time() ?>"></script>
<script src="../assets/js/messages.js?v=<?= time() ?>"></script>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>
