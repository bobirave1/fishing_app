// Messages Page JavaScript
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
