<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\NotificationService;
use App\Services\PushNotificationService;

class NotificationController extends Controller
{
    public function getNotifications(): void
    {
        $userId = $this->requireAuth();
        $limit = max(1, min(50, (int) ($_GET['limit'] ?? 10)));
        $unreadOnly = !empty($_GET['unread_only']);

        $service = $this->service(NotificationService::class);
        $data = $service->getForUser($userId, $limit, $unreadOnly);
        $this->jsonOk($data);
    }

    public function markRead(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $service = $this->service(NotificationService::class);
        $action = $_POST['action'] ?? 'mark_read';

        if ($action === 'mark_all_read') {
            $service->markAllRead($userId);
        } else {
            $notificationId = (int) ($_POST['notification_id'] ?? 0);
            if ($notificationId) {
                $service->markRead($notificationId, $userId);
            }
        }

        $this->jsonOk();
    }

    /**
     * Poll for new push notifications (browser Notification API).
     */
    public function pollPush(): void
    {
        $userId = $this->requireAuth();
        $since = $_GET['since'] ?? null;

        $service = $this->service(PushNotificationService::class);
        $notifications = $service->getPendingNotifications($userId, $since);

        $this->jsonOk([
            'notifications' => $notifications,
            'timestamp'     => date('c'),
        ]);
    }

    /**
     * Save push subscription.
     */
    public function subscribePush(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $endpoint = trim($_POST['endpoint'] ?? '');
        $p256dh = trim($_POST['p256dh'] ?? '');
        $auth = trim($_POST['auth'] ?? '');

        if (!$endpoint) $this->jsonError('Endpoint required');

        $service = $this->service(PushNotificationService::class);
        $service->subscribe($userId, $endpoint, $p256dh, $auth);
        $this->jsonOk(['subscribed' => true]);
    }

    /**
     * Remove push subscription.
     */
    public function unsubscribePush(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $endpoint = trim($_POST['endpoint'] ?? '');
        if (!$endpoint) $this->jsonError('Endpoint required');

        $service = $this->service(PushNotificationService::class);
        $service->unsubscribe($userId, $endpoint);
        $this->jsonOk(['unsubscribed' => true]);
    }
}
