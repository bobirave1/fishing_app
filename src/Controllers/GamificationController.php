<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\GamificationService;

class GamificationController extends Controller
{
    /**
     * Badges page.
     */
    public function badgesPage(): void
    {
        $this->requireAuth();
        $pageTitle = __('badges') . ' | FISHINGLORY';
        $pageCss = ['fe/assets/css/gamification.css'];
        $pageJs = ['fe/assets/js/gamification.js'];

        $content = function () {
            include dirname(__DIR__, 2) . '/templates/pages/badges.php';
        };
        include dirname(__DIR__, 2) . '/templates/layouts/main.php';
    }

    /**
     * Get gamification stats for current user or a specific user.
     */
    public function stats(): void
    {
        $userId = $this->requireAuth();
        $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;

        $service = $this->service(GamificationService::class);
        $stats = $service->getStats($targetUserId);
        $this->jsonOk($stats);
    }

    /**
     * Get badges for current user or a specific user.
     */
    public function badges(): void
    {
        $userId = $this->requireAuth();
        $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;

        $service = $this->service(GamificationService::class);
        $badges = $service->getUserBadges($targetUserId);
        $stats = $service->getStats($targetUserId);

        $this->jsonOk([
            'badges' => $badges,
            'stats'  => $stats,
        ]);
    }

    /**
     * Leaderboard — top users by XP.
     */
    public function leaderboard(): void
    {
        $this->requireAuth();
        $limit = min(50, max(5, (int)($_GET['limit'] ?? 20)));

        $stmt = $this->pdo->prepare("
            SELECT us.user_id, us.total_xp, us.current_streak, us.longest_streak,
                   u.username, u.full_name, up.avatar_url,
                   (SELECT COUNT(*) FROM user_badges WHERE user_id = us.user_id) AS badge_count
            FROM user_streaks us
            JOIN users u ON u.id = us.user_id
            LEFT JOIN user_profiles up ON u.id = up.user_id
            ORDER BY us.total_xp DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $limit, \PDO::PARAM_INT);
        $stmt->execute();

        $this->jsonOk(['leaderboard' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }
}
