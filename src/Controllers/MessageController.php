<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\MessageService;

class MessageController extends Controller
{
    public function page(): void
    {
        $this->requireAuth();
        require dirname(__DIR__, 2) . '/fe/pages/messages.php';
    }

    public function handle(): void
    {
        $userId = $this->requireAuth();
        $action = $_POST['action'] ?? $_GET['action'] ?? 'get_conversations';

        $service = $this->service(MessageService::class);

        switch ($action) {
            case 'send':
                $this->requireCsrf();
                $receiverId = (int) ($_POST['receiver_id'] ?? 0);
                $content = trim($_POST['content'] ?? '');

                $attachmentUrls = [];
                if (!empty($_FILES['files']['name'][0])) {
                    $uploadDir = dirname(__DIR__, 2) . '/fe/assets/uploads/messages/';
                    foreach ($_FILES['files']['name'] as $key => $name) {
                        if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) continue;
                        $file = [
                            'name' => $name,
                            'type' => $_FILES['files']['type'][$key],
                            'tmp_name' => $_FILES['files']['tmp_name'][$key],
                            'error' => $_FILES['files']['error'][$key],
                            'size' => $_FILES['files']['size'][$key],
                        ];
                        $result = secureUploadFile($file, $uploadDir, 'media');
                        if ($result['success']) {
                            $attachmentUrls[] = 'assets/uploads/messages/' . $result['filename'];
                        }
                    }
                }

                $msgId = $service->send($userId, $receiverId, $content, $attachmentUrls);
                $this->jsonOk([
                    'message_id' => $msgId,
                    'created_at' => date('c'),
                    'attachments' => $attachmentUrls,
                ]);
                break;

            case 'get_conversation':
                $receiverId = (int) ($_GET['receiver_id'] ?? 0);
                $messages = $service->getConversation($userId, $receiverId);
                $this->jsonOk(['messages' => $messages, 'current_user_id' => $userId]);
                break;

            case 'get_conversations':
                $convs = $service->getConversations($userId);
                $this->jsonOk(['conversations' => $convs]);
                break;

            case 'search_friends':
                $q = trim($_GET['q'] ?? '');
                $friends = $service->searchFriends($userId, $q);
                $this->jsonOk(['friends' => $friends]);
                break;

            default:
                $this->jsonError('Invalid action');
        }
    }
}
