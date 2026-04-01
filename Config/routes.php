<?php
/**
 * Application route definitions.
 * Clean URL routes dispatched by the front controller.
 * 
 * Old direct-file URLs (be/*, fe/*) still work via .htaccess passthrough.
 * These routes provide clean alternatives.
 */

use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\PostController;
use App\Controllers\UserController;
use App\Controllers\FriendController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\SearchController;
use App\Controllers\WeatherController;
use App\Controllers\ActivityController;
use App\Controllers\ChatController;
use App\Controllers\GamificationController;
use App\Controllers\ExportController;

$router = new Router('/fishing_app', $GLOBALS['container'] ?? null);

// ── Pages ──────────────────────────────────────────
$router->get('/', [HomeController::class, 'index']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->get('/register', [AuthController::class, 'registerForm']);
$router->get('/profile/{id}', [UserController::class, 'profile']);
$router->get('/edit-profile', [UserController::class, 'editProfile']);
$router->get('/messages', [MessageController::class, 'page']);
$router->get('/fish-activity', [ActivityController::class, 'page']);
$router->get('/friends', [FriendController::class, 'list']);
$router->get('/friend-requests', [FriendController::class, 'requests']);

// ── Auth ───────────────────────────────────────────
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/register', [AuthController::class, 'register']);
$router->get('/auth/logout', [AuthController::class, 'logout']);

// ── Posts API ──────────────────────────────────────
$router->post('/api/posts/create', [PostController::class, 'create']);
$router->post('/api/posts/edit', [PostController::class, 'edit']);
$router->post('/api/posts/delete', [PostController::class, 'delete']);
$router->post('/api/posts/like', [PostController::class, 'like']);
$router->any('/api/posts/comments', [PostController::class, 'comments']);

// ── Users API ──────────────────────────────────────
$router->post('/api/users/edit-profile', [UserController::class, 'editProfileApi']);
$router->post('/api/users/upload-avatar', [UserController::class, 'uploadAvatar']);
$router->post('/api/users/follow', [UserController::class, 'follow']);

// ── Friends API ────────────────────────────────────
$router->post('/api/friends/send-request', [FriendController::class, 'sendRequest']);
$router->post('/api/friends/accept', [FriendController::class, 'accept']);
$router->post('/api/friends/reject', [FriendController::class, 'reject']);
$router->post('/api/friends/remove', [FriendController::class, 'remove']);

// ── Messages API ───────────────────────────────────
$router->any('/api/messages', [MessageController::class, 'handle']);

// ── Notifications API ──────────────────────────────
$router->get('/api/notifications', [NotificationController::class, 'getNotifications']);
$router->post('/api/notifications/mark-read', [NotificationController::class, 'markRead']);

// ── Search & Weather API ───────────────────────────
$router->get('/api/search', [SearchController::class, 'search']);
$router->get('/api/search/advanced', [SearchController::class, 'advancedSearch']);
$router->get('/search', [SearchController::class, 'advancedPage']);
$router->get('/api/weather', [WeatherController::class, 'get']);
$router->get('/api/activity', [ActivityController::class, 'handle']);
$router->get('/api/fish-activity', [ActivityController::class, 'predict']);
$router->get('/api/fish-activity/species', [ActivityController::class, 'species']);

// ── Chat API (real-time polling) ───────────────────
$router->get('/api/chat/poll', [ChatController::class, 'poll']);
$router->post('/api/chat/typing', [ChatController::class, 'typing']);
$router->post('/api/chat/heartbeat', [ChatController::class, 'heartbeat']);
$router->post('/api/chat/mark-read', [ChatController::class, 'markRead']);

// ── Gamification ───────────────────────────────────
$router->get('/badges', [GamificationController::class, 'badgesPage']);
$router->get('/api/gamification/stats', [GamificationController::class, 'stats']);
$router->get('/api/gamification/badges', [GamificationController::class, 'badges']);
$router->get('/api/gamification/leaderboard', [GamificationController::class, 'leaderboard']);

// ── Export ─────────────────────────────────────────
$router->get('/export', [ExportController::class, 'page']);
$router->get('/api/export/gpx', [ExportController::class, 'gpx']);
$router->get('/api/export/pdf', [ExportController::class, 'pdf']);

// ── Push Notifications ─────────────────────────────
$router->get('/api/push/poll', [NotificationController::class, 'pollPush']);
$router->post('/api/push/subscribe', [NotificationController::class, 'subscribePush']);
$router->post('/api/push/unsubscribe', [NotificationController::class, 'unsubscribePush']);

return $router;
