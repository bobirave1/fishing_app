<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - FISHINGLORY</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" href="../assets/img/logo_rounded.png">
    <style>
        body {
            padding-top: 70px;
        }
        .messages-container {
            display: flex;
            height: 80vh;
            gap: 0;
        }
        .conversation-list {
            width: 300px;
            border-right: 1px solid #ddd;
            overflow-y: auto;
        }
        .conversation-item {
            cursor: pointer;
            transition: background 0.2s;
            border-bottom: 1px solid #eee;
        }
        .conversation-item:hover {
            background: #f8f9fa;
        }
        .conversation-item.active {
            background: #e7f3ff;
            border-left: 3px solid #0d6efd;
        }
        .conversation-thread {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            padding: 20px;
            background: white;
        }
        .message-input-area {
            border-top: 1px solid #ddd;
            padding: 15px;
            background: #f8f9fa;
        }
        .message-group {
            display: flex;
            margin-bottom: 10px;
        }
        .message-group.own {
            justify-content: flex-end;
        }
    </style>
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
<script>
    let currentConversationUserId = null;

    function openConversation(userId) {
        currentConversationUserId = userId;
        
        // Update active state
        document.querySelectorAll('.conversation-item').forEach(el => {
            el.classList.remove('active');
        });
        event.target.closest('.conversation-item').classList.add('active');

        // Load conversation
        fetch('../../be/messages/message.php?action=get_conversation&receiver_id=' + userId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayConversationView(data.messages, data.current_user_id, userId);
                    document.getElementById('conversationView').classList.remove('d-none');
                    document.getElementById('noConversationView').classList.add('d-none');
                }
            });
    }

    function displayConversationView(messages, currentUserId, otherId) {
        const container = document.getElementById('conversationThread');
        let html = '';
        
        messages.forEach(msg => {
            const isOwn = msg.sender_id == currentUserId;
            const alignment = isOwn ? 'text-end' : 'text-start';
            const bgClass = isOwn ? 'bg-primary text-white' : 'bg-light';
            
            html += `
                <div class="${alignment}">
                    <div class="d-inline-block ${bgClass} p-2 rounded-3" style="max-width: 70%;">
                        <p class="mb-0">${msg.content}</p>
                        <small class="${isOwn ? 'text-white-50' : 'text-muted'}">${formatDate(msg.created_at)}</small>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html || '<p class="text-center text-muted">No messages yet</p>';
        container.parentElement.scrollTop = container.parentElement.scrollHeight;
    }

    function sendMessageToCurrentUser() {
        if (!currentConversationUserId) {
            alert('Please select a conversation');
            return;
        }
        sendMessage(currentConversationUserId);
    }

    // Load conversations on page load
    loadConversations();
    setInterval(loadConversations, 10000);
</script>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <p>&copy; 2026 FISHINGLORY. All rights reserved. | Connect with fellow anglers and share your catches!</p>
    </div>
</footer>

</body>
</html>
