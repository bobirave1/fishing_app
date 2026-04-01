<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Services\ChatService;

class ChatController extends Controller
{
    /**
     * Chat page (uses existing messages page with enhanced features).
     */
    public function page(): void
    {
        $this->requireAuth();
        require dirname(__DIR__, 2) . '/fe/pages/messages.php';
    }

    /**
     * Long-polling endpoint for real-time messages.
     */
    public function poll(): void
    {
        $userId = $this->requireAuth();
        $since = $_GET['since'] ?? null;
        $partnerId = isset($_GET['partner_id']) ? (int)$_GET['partner_id'] : null;

        $service = $this->service(ChatService::class);
        $data = $service->poll($userId, $since, $partnerId);
        $this->jsonOk($data);
    }

    /**
     * Typing indicator endpoint.
     */
    public function typing(): void
    {
        $userId = $this->requireAuth();
        $targetId = isset($_POST['target_id']) ? (int)$_POST['target_id'] : null;

        $service = $this->service(ChatService::class);
        $service->setTyping($userId, $targetId);
        $this->jsonOk();
    }

    /**
     * Heartbeat — keep online status alive.
     */
    public function heartbeat(): void
    {
        $userId = $this->requireAuth();
        $typingTo = isset($_POST['typing_to']) ? (int)$_POST['typing_to'] : null;

        $service = $this->service(ChatService::class);
        $service->heartbeat($userId, $typingTo);
        $this->jsonOk(['status' => 'ok']);
    }

    /**
     * Mark conversation as read.
     */
    public function markRead(): void
    {
        $userId = $this->requireAuth();
        $senderId = (int)($_POST['sender_id'] ?? 0);
        if (!$senderId) $this->jsonError('sender_id required');

        $service = $this->service(ChatService::class);
        $service->markConversationRead($userId, $senderId);
        $this->jsonOk();
    }
}
