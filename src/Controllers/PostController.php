<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Middleware;
use App\Core\Response;
use App\Services\PostService;

class PostController extends Controller
{
    public function create(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();
        Middleware::rateLimit('post_create_' . $userId, 10, 300);

        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $visibility = $_POST['visibility'] ?? 'public';

        if (empty($title) || strlen($title) > 200) {
            $_SESSION['post_error'] = 'Title must be between 1 and 200 characters';
            Response::redirect('/fishing_app/');
        }
        if (empty($content) || strlen($content) > 5000) {
            $_SESSION['post_error'] = 'Content must be between 1 and 5000 characters';
            Response::redirect('/fishing_app/');
        }
        if (!in_array($visibility, ['public', 'friends', 'private'])) {
            $_SESSION['post_error'] = 'Invalid visibility setting';
            Response::redirect('/fishing_app/');
        }

        $uploadedPaths = [];
        if (isset($_FILES['media'])) {
            $isMultiple = is_array($_FILES['media']['name']);
            $count = $isMultiple ? count($_FILES['media']['name']) : 1;

            for ($i = 0; $i < $count; $i++) {
                $file = $isMultiple
                    ? [
                        'name' => $_FILES['media']['name'][$i] ?? '',
                        'type' => $_FILES['media']['type'][$i] ?? '',
                        'tmp_name' => $_FILES['media']['tmp_name'][$i] ?? '',
                        'error' => $_FILES['media']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $_FILES['media']['size'][$i] ?? 0,
                    ]
                    : $_FILES['media'];

                if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;

                $result = secureUploadFile($file, dirname(__DIR__, 2) . '/fe/assets/img', 'media');
                if (!$result['success']) {
                    $_SESSION['post_error'] = $result['error'];
                    Response::redirect('/fishing_app/');
                }
                $uploadedPaths[] = 'fe/assets/img/' . $result['filename'];
            }
        }

        $imagePath = $uploadedPaths[0] ?? null;
        $service = $this->service(PostService::class);
        $postId = $service->create($userId, $title, $content, $visibility, $imagePath, $uploadedPaths);

        // Award XP for post creation (gamification)
        try {
            $gamification = $this->service(\App\Services\GamificationService::class);
            $gamification->awardXp($userId, 'post_created', $postId);
        } catch (\Throwable) {}

        // Notify friends
        if ($visibility !== 'private') {
            $friends = $service->getFriendIds($userId);
            foreach ($friends as $fid) {
                if ($fid) {
                    try {
                        $this->pdo->prepare(
                            "INSERT INTO notifications (user_id, type, from_user_id, post_id, created_at) VALUES (?, 'new_post', ?, ?, NOW())"
                        )->execute([$fid, $userId, $postId]);
                    } catch (\Throwable) {}
                }
            }
        }

        Response::redirect('/fishing_app/');
    }

    public function edit(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $postId = (int) ($_POST['id'] ?? 0);
        $service = $this->service(PostService::class);
        $post = $service->findById($postId);

        if (!$post || (int) $post['user_id'] !== $userId) {
            $this->jsonError('Unauthorized', 403);
        }

        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $visibility = $_POST['visibility'] ?? 'public';
        $imagePath = $post['image'];

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $result = secureUploadFile($_FILES['image'], dirname(__DIR__, 2) . '/fe/assets/img', 'media');
            if ($result['success']) {
                if (!empty($post['image'])) {
                    $old = dirname(__DIR__, 2) . '/' . $post['image'];
                    if (is_file($old)) @unlink($old);
                }
                $imagePath = 'fe/assets/img/' . $result['filename'];
            }
        }

        $service->update($postId, $title, $content, $visibility, $imagePath);
        Response::redirect('/fishing_app/');
    }

    public function delete(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $postId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
        $service = $this->service(PostService::class);

        if ($service->delete($postId, $userId)) {
            Response::json(['success' => true, 'message' => 'Post deleted successfully']);
        } else {
            $this->jsonError('Cannot delete this post', 403);
        }
    }

    public function like(): void
    {
        $userId = $this->requireAuth();
        $this->requireCsrf();

        $postId = (int) ($_POST['post_id'] ?? 0);
        if (!$postId) $this->jsonError('Post ID required');

        $action = $_POST['action'] ?? 'like';
        $service = $this->service(PostService::class);
        $post = $service->findById($postId);
        if (!$post) $this->jsonError('Post not found', 404);

        $result = $service->toggleLike($postId, $userId);
        $this->jsonOk($result);
    }

    public function comments(): void
    {
        $userId = $this->requireAuth();
        $postId = (int) ($_POST['post_id'] ?? $_GET['post_id'] ?? 0);
        $action = $_POST['action'] ?? 'get';

        if (!$postId) $this->jsonError('Post ID required');

        $service = $this->service(PostService::class);
        if (!$service->findById($postId)) $this->jsonError('Post not found', 404);

        if ($action === 'get') {
            $comments = $service->getComments($postId, $userId);
            $this->jsonOk(['comments' => $comments, 'count' => count($comments)]);
        }

        // Write operations need CSRF
        $this->requireCsrf();

        if ($action === 'add') {
            Middleware::rateLimit('comment_' . $userId, 20, 120);
            $content = trim($_POST['content'] ?? '');
            if (empty($content)) $this->jsonError('Comment content required');
            if (strlen($content) > 1000) $this->jsonError('Comment too long');

            $parentId = !empty($_POST['parent_id']) ? (int) $_POST['parent_id'] : null;
            $comment = $service->addComment($postId, $userId, $content, $parentId);
            $this->jsonOk($comment);
        }

        if ($action === 'delete') {
            $commentId = (int) ($_POST['comment_id'] ?? 0);
            if ($service->deleteComment($commentId, $userId)) {
                $this->jsonOk();
            }
            $this->jsonError('Cannot delete this comment', 403);
        }

        if ($action === 'like_comment') {
            $commentId = (int) ($_POST['comment_id'] ?? 0);
            if (!$commentId) $this->jsonError('Comment ID required');
            $result = $service->toggleCommentLike($commentId, $userId);
            $this->jsonOk($result);
        }

        $this->jsonError('Invalid action');
    }
}
