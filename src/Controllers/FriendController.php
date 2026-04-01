<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\FriendService;

class FriendController extends Controller
{
    public function list(): void
    {
        $this->requireAuth();
        require dirname(__DIR__, 2) . '/be/friends/list_friends.php';
    }

    public function requests(): void
    {
        $this->requireAuth();
        require dirname(__DIR__, 2) . '/be/friends/list_requests.php';
    }

    public function sendRequest(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $receiverId = (int) ($_POST['receiver_id'] ?? 0);
        $service = $this->service(FriendService::class);

        try {
            $service->sendRequest($userId, $receiverId);
            Response::redirect("/fishing_app/profile/{$receiverId}");
        } catch (\RuntimeException $e) {
            http_response_code(409);
            exit($e->getMessage());
        }
    }

    public function accept(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $service = $this->service(FriendService::class);

        if ($service->acceptRequest($requestId, $userId)) {
            Response::redirect('/fishing_app/friend-requests');
        }
        exit('Invalid request');
    }

    public function reject(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $requestId = (int) ($_POST['request_id'] ?? 0);
        $service = $this->service(FriendService::class);
        $service->rejectRequest($requestId, $userId);

        Response::redirect('/fishing_app/friend-requests');
    }

    public function remove(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $friendId = (int) ($_POST['friend_id'] ?? 0);
        $returnTo = $_POST['return_to'] ?? 'friends_list';
        $service = $this->service(FriendService::class);

        if ($service->removeFriend($userId, $friendId)) {
            $_SESSION['friend_flash_success'] = __('friend_removed_success');
        } else {
            $_SESSION['friend_flash_error'] = __('friend_not_found');
        }

        if ($returnTo === 'profile') {
            Response::redirect("/fishing_app/profile/{$friendId}");
        }
        Response::redirect('/fishing_app/friends');
    }
}
