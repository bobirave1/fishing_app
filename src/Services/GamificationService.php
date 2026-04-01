<?php

namespace App\Services;

/**
 * Gamification: badges, XP, levels, streaks.
 */
class GamificationService
{
    /** XP thresholds for each level */
    private const LEVEL_THRESHOLDS = [
        1 => 0, 2 => 100, 3 => 300, 4 => 600, 5 => 1000,
        6 => 1500, 7 => 2200, 8 => 3000, 9 => 4000, 10 => 5500,
        11 => 7500, 12 => 10000, 13 => 13000, 14 => 17000, 15 => 22000,
    ];

    /** XP rewards for actions */
    private const XP_ACTIONS = [
        'post_created'    => 15,
        'catch_logged'    => 20,
        'comment_added'   => 5,
        'like_received'   => 3,
        'friend_added'    => 10,
        'streak_bonus'    => 25,
    ];

    public function __construct(private \PDO $pdo) {}

    /**
     * Award XP for an action and check for new badges.
     * Returns ['xp_gained', 'total_xp', 'level', 'new_badges' => []]
     */
    public function awardXp(int $userId, string $reason, int $relatedId = 0): array
    {
        $xp = self::XP_ACTIONS[$reason] ?? 0;
        if ($xp <= 0) return $this->getStats($userId);

        // Log XP
        $this->pdo->prepare(
            'INSERT INTO user_xp_log (user_id, xp_amount, reason, related_id) VALUES (?, ?, ?, ?)'
        )->execute([$userId, $xp, $reason, $relatedId ?: null]);

        // Update streak
        $this->updateStreak($userId, $xp);

        // Check for new badges
        $newBadges = $this->checkBadges($userId);

        $stats = $this->getStats($userId);
        $stats['xp_gained'] = $xp;
        $stats['new_badges'] = $newBadges;

        return $stats;
    }

    /**
     * Get gamification stats for a user.
     */
    public function getStats(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT COALESCE(total_xp, 0) as total_xp, COALESCE(current_streak, 0) as current_streak,
                    COALESCE(longest_streak, 0) as longest_streak, last_activity_date
             FROM user_streaks WHERE user_id = ?'
        );
        $stmt->execute([$userId]);
        $streak = $stmt->fetch(\PDO::FETCH_ASSOC);

        $totalXp = $streak ? (int)$streak['total_xp'] : 0;
        $level = $this->calculateLevel($totalXp);

        return [
            'total_xp'        => $totalXp,
            'level'           => $level,
            'level_name'      => $this->getLevelName($level),
            'xp_for_next'     => $this->xpForNextLevel($level, $totalXp),
            'current_streak'  => $streak ? (int)$streak['current_streak'] : 0,
            'longest_streak'  => $streak ? (int)$streak['longest_streak'] : 0,
        ];
    }

    /**
     * Get all badges for a user.
     */
    public function getUserBadges(int $userId): array
    {
        $lang = $_SESSION['lang'] ?? 'en';
        $nameCol = $lang === 'bg' ? 'name_bg' : 'name_en';
        $descCol = $lang === 'bg' ? 'description_bg' : 'description_en';

        $stmt = $this->pdo->prepare("
            SELECT bd.id, bd.slug, bd.{$nameCol} AS name, bd.{$descCol} AS description,
                   bd.icon, bd.color, ub.earned_at,
                   CASE WHEN ub.id IS NOT NULL THEN 1 ELSE 0 END AS earned
            FROM badge_definitions bd
            LEFT JOIN user_badges ub ON ub.badge_id = bd.id AND ub.user_id = ?
            ORDER BY ub.earned_at DESC, bd.id ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Update daily streak.
     */
    private function updateStreak(int $userId, int $xp): void
    {
        $today = date('Y-m-d');

        $stmt = $this->pdo->prepare('SELECT * FROM user_streaks WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->pdo->prepare(
                'INSERT INTO user_streaks (user_id, current_streak, longest_streak, last_activity_date, total_xp)
                 VALUES (?, 1, 1, ?, ?)'
            )->execute([$userId, $today, $xp]);
            return;
        }

        $lastDate = $row['last_activity_date'];
        $currentStreak = (int)$row['current_streak'];
        $longestStreak = (int)$row['longest_streak'];
        $totalXp = (int)$row['total_xp'] + $xp;

        if ($lastDate === $today) {
            // Same day — just add XP
            $this->pdo->prepare('UPDATE user_streaks SET total_xp = ? WHERE user_id = ?')
                ->execute([$totalXp, $userId]);
            return;
        }

        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($lastDate === $yesterday) {
            // Consecutive day — extend streak
            $currentStreak++;
            if ($currentStreak > $longestStreak) {
                $longestStreak = $currentStreak;
            }
            // Streak bonus every 7 days
            if ($currentStreak % 7 === 0) {
                $totalXp += self::XP_ACTIONS['streak_bonus'];
                $this->pdo->prepare(
                    'INSERT INTO user_xp_log (user_id, xp_amount, reason) VALUES (?, ?, ?)'
                )->execute([$userId, self::XP_ACTIONS['streak_bonus'], 'streak_bonus']);
            }
        } else {
            // Streak broken
            $currentStreak = 1;
        }

        $this->pdo->prepare(
            'UPDATE user_streaks SET current_streak = ?, longest_streak = ?, last_activity_date = ?, total_xp = ? WHERE user_id = ?'
        )->execute([$currentStreak, $longestStreak, $today, $totalXp, $userId]);
    }

    /**
     * Check and award any new badges.
     */
    private function checkBadges(int $userId): array
    {
        $newBadges = [];

        // Get all badge definitions not yet earned
        $stmt = $this->pdo->prepare("
            SELECT bd.* FROM badge_definitions bd
            LEFT JOIN user_badges ub ON ub.badge_id = bd.id AND ub.user_id = ?
            WHERE ub.id IS NULL
        ");
        $stmt->execute([$userId]);
        $unearned = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($unearned as $badge) {
            if ($this->meetsCriteria($userId, $badge)) {
                // Award badge
                $this->pdo->prepare(
                    'INSERT IGNORE INTO user_badges (user_id, badge_id, earned_at) VALUES (?, ?, NOW())'
                )->execute([$userId, $badge['id']]);

                // Award badge XP
                $xpReward = (int)$badge['xp_reward'];
                if ($xpReward > 0) {
                    $this->pdo->prepare(
                        'INSERT INTO user_xp_log (user_id, xp_amount, reason, related_id) VALUES (?, ?, ?, ?)'
                    )->execute([$userId, $xpReward, 'badge_earned', $badge['id']]);
                    $this->pdo->prepare(
                        'UPDATE user_streaks SET total_xp = total_xp + ? WHERE user_id = ?'
                    )->execute([$xpReward, $userId]);
                }

                // Notification
                try {
                    $lang = $_SESSION['lang'] ?? 'en';
                    $badgeName = $lang === 'bg' ? $badge['name_bg'] : $badge['name_en'];
                    $notifMsg = $lang === 'bg'
                        ? "Получихте нова значка: {$badge['name_bg']}"
                        : "You earned the badge: {$badge['name_en']}";
                    $this->pdo->prepare(
                        "INSERT INTO notifications (user_id, type, related_id, message, created_at) VALUES (?, 'badge', ?, ?, NOW())"
                    )->execute([$userId, $badge['id'], $notifMsg]);
                } catch (\Throwable) {}

                $lang = $_SESSION['lang'] ?? 'en';
                $newBadges[] = [
                    'slug'  => $badge['slug'],
                    'name'  => $lang === 'bg' ? $badge['name_bg'] : $badge['name_en'],
                    'icon'  => $badge['icon'],
                    'color' => $badge['color'],
                ];
            }
        }

        return $newBadges;
    }

    /**
     * Check if a user meets the criteria for a badge.
     */
    private function meetsCriteria(int $userId, array $badge): bool
    {
        $value = (int)$badge['criteria_value'];

        return match ($badge['criteria_type']) {
            'post_count' => $this->countQuery('SELECT COUNT(*) FROM posts WHERE user_id = ?', [$userId]) >= $value,
            'catch_count' => $this->countQuery(
                'SELECT COUNT(*) FROM fish_catches fc JOIN posts p ON fc.post_id = p.id WHERE p.user_id = ?', [$userId]
            ) >= $value,
            'friend_count' => $this->countQuery(
                'SELECT COUNT(*) FROM friends WHERE user_id = ? OR friend_id = ?', [$userId, $userId]
            ) >= $value,
            'like_received' => $this->countQuery(
                'SELECT COUNT(*) FROM post_likes pl JOIN posts p ON pl.post_id = p.id WHERE p.user_id = ? AND pl.user_id != ?',
                [$userId, $userId]
            ) >= $value,
            'comment_count' => $this->countQuery('SELECT COUNT(*) FROM post_comments WHERE user_id = ?', [$userId]) >= $value,
            'streak_days' => $this->countQuery(
                'SELECT COALESCE(longest_streak, 0) FROM user_streaks WHERE user_id = ?', [$userId]
            ) >= $value,
            'first_catch' => $this->countQuery(
                'SELECT COUNT(*) FROM fish_catches fc JOIN posts p ON fc.post_id = p.id WHERE p.user_id = ?', [$userId]
            ) >= 1,
            'big_fish' => $this->countQuery(
                'SELECT COUNT(*) FROM fish_catches fc JOIN posts p ON fc.post_id = p.id WHERE p.user_id = ? AND fc.weight >= ?',
                [$userId, $value]
            ) >= 1,
            'species_variety' => $this->countQuery(
                'SELECT COUNT(DISTINCT fc.fish_species) FROM fish_catches fc JOIN posts p ON fc.post_id = p.id WHERE p.user_id = ?',
                [$userId]
            ) >= $value,
            default => false,
        };
    }

    private function countQuery(string $sql, array $params): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function calculateLevel(int $xp): int
    {
        $level = 1;
        foreach (self::LEVEL_THRESHOLDS as $lvl => $threshold) {
            if ($xp >= $threshold) $level = $lvl;
        }
        return $level;
    }

    private function xpForNextLevel(int $currentLevel, int $currentXp): array
    {
        $nextLevel = $currentLevel + 1;
        $nextThreshold = self::LEVEL_THRESHOLDS[$nextLevel] ?? null;
        if ($nextThreshold === null) {
            return ['needed' => 0, 'progress' => 100];
        }
        $currentThreshold = self::LEVEL_THRESHOLDS[$currentLevel];
        $range = $nextThreshold - $currentThreshold;
        $progress = $range > 0 ? round(($currentXp - $currentThreshold) / $range * 100) : 100;
        return [
            'needed' => $nextThreshold - $currentXp,
            'progress' => min(100, max(0, $progress)),
            'next_level_xp' => $nextThreshold,
        ];
    }

    private function getLevelName(int $level): string
    {
        $lang = $_SESSION['lang'] ?? 'en';
        $names = [
            'en' => [1=>'Novice', 2=>'Beginner', 3=>'Apprentice', 4=>'Fisher', 5=>'Skilled Fisher',
                     6=>'Expert', 7=>'Veteran', 8=>'Master', 9=>'Grand Master', 10=>'Legend',
                     11=>'Mythic', 12=>'Titan', 13=>'Champion', 14=>'Immortal', 15=>'Fishing God'],
            'bg' => [1=>'Новак', 2=>'Начинаещ', 3=>'Чирак', 4=>'Рибар', 5=>'Опитен Рибар',
                     6=>'Експерт', 7=>'Ветеран', 8=>'Майстор', 9=>'Гранд Майстор', 10=>'Легенда',
                     11=>'Митичен', 12=>'Титан', 13=>'Шампион', 14=>'Безсмъртен', 15=>'Бог на Риболова'],
        ];
        return $names[$lang][$level] ?? $names['en'][$level] ?? "Level {$level}";
    }
}
