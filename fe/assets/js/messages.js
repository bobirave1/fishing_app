// Messages Page JavaScript
let currentConversationUserId = null;

function openConversation(userId) {
    currentConversationUserId = userId;
    
    // Update active state
    document.querySelectorAll('.conversation-item').forEach(el => {
        el.classList.remove('active');
    });
    // Find the clicked item and add active class
    const clickedItem = document.querySelector(`.conversation-item[data-user-id="${userId}"]`);
    if (clickedItem) {
        clickedItem.classList.add('active');
    }

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
        
        let attachmentsHtml = '';
        if (msg.attachment_urls) {
            const attachments = JSON.parse(msg.attachment_urls);
            attachments.forEach(url => {
                const fileExt = url.split('.').pop().toLowerCase();
                if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                    attachmentsHtml += `<img src="../${url}" class="img-fluid rounded mb-2" style="max-width: 200px; max-height: 200px;" onclick="window.open(this.src)">`;
                } else if (['mp4', 'avi', 'mov'].includes(fileExt)) {
                    attachmentsHtml += `<video controls class="mb-2" style="max-width: 200px; max-height: 200px;"><source src="../${url}" type="video/${fileExt}"></video>`;
                }
            });
        }
        
        html += `
            <div class="${alignment}">
                <div class="d-inline-block ${bgClass} p-2 rounded-3" style="max-width: 70%;">
                    ${attachmentsHtml}
                    ${msg.content ? `<p class="mb-0">${msg.content}</p>` : ''}
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

// Add Enter key listener for sending messages
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('messageInput');
    if (messageInput) {
        messageInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessageToCurrentUser();
            }
        });
    }
    
    // Add file input change listener
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelection);
    }
});

function handleFileSelection(event) {
    const files = event.target.files;
    const previewContainer = document.getElementById('filePreview');
    
    if (!previewContainer) return;
    
    previewContainer.innerHTML = '';
    
    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        const fileItem = document.createElement('div');
        fileItem.className = 'file-preview-item d-inline-block me-2 mb-2';
        
        if (file.type.startsWith('image/')) {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'img-thumbnail';
            img.style.maxWidth = '100px';
            img.style.maxHeight = '100px';
            fileItem.appendChild(img);
        } else if (file.type.startsWith('video/')) {
            const video = document.createElement('video');
            video.src = URL.createObjectURL(file);
            video.className = 'img-thumbnail';
            video.style.maxWidth = '100px';
            video.style.maxHeight = '100px';
            video.controls = false;
            fileItem.appendChild(video);
        }
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-sm btn-outline-danger ms-1';
        removeBtn.innerHTML = '×';
        removeBtn.onclick = function() {
            // Remove this file from the input
            const dt = new DataTransfer();
            const fileList = Array.from(event.target.files);
            fileList.splice(i, 1);
            fileList.forEach(f => dt.items.add(f));
            event.target.files = dt.files;
            handleFileSelection(event);
        };
        
        fileItem.appendChild(removeBtn);
        previewContainer.appendChild(fileItem);
    }
}
