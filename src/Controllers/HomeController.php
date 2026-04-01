<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Response;
use App\Services\PostService;

class HomeController extends Controller
{
    public function index(): void
    {
        $service = $this->service(PostService::class);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;

        if (isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
            $result = $service->getFeed($userId, $page, $perPage);
        } else {
            $result = $service->getPublicFeed($page, $perPage);
        }

        $totalPages = max(1, (int) ceil($result['total'] / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            // Re-fetch with corrected page
            $result = isset($_SESSION['user_id'])
                ? $service->getFeed((int) $_SESSION['user_id'], $page, $perPage)
                : $service->getPublicFeed($page, $perPage);
        }

        $composerUser = [
            'username' => $_SESSION['username'] ?? 'User',
            'avatar' => getUserAvatar(null),
        ];

        if (isset($_SESSION['user_id'])) {
            $stmt = $this->pdo->prepare(
                'SELECT u.username, up.avatar_url FROM users u LEFT JOIN user_profiles up ON up.user_id = u.id WHERE u.id = ? LIMIT 1'
            );
            $stmt->execute([$_SESSION['user_id']]);
            $data = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($data) {
                $composerUser['username'] = $data['username'];
                $composerUser['avatar'] = getUserAvatar($data['avatar_url'] ?? null);
            }
        }

        Response::view('pages/home', [
            'posts' => $result['posts'],
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'composerUser' => $composerUser,
        ]);
    }
}
