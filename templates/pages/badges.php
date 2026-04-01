<?php
/**
 * Badges & Gamification page template.
 */
$isBg = ($_SESSION['lang'] ?? 'en') === 'bg';
?>
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-trophy me-2 text-warning"></i><?= $isBg ? 'Значки и Постижения' : 'Badges & Achievements' ?></h2>
            <p class="text-muted"><?= $isBg ? 'Печелете XP и значки за вашата активност' : 'Earn XP and badges for your fishing activity' ?></p>
        </div>
    </div>

    <!-- Stats Section -->
    <div id="gamificationStats" class="mb-4">
        <div class="text-center py-3">
            <i class="fas fa-spinner fa-spin"></i> <?= $isBg ? 'Зареждане...' : 'Loading...' ?>
        </div>
    </div>

    <!-- Badges Section -->
    <div class="row mb-4">
        <div class="col-12">
            <h4><i class="fas fa-medal me-2"></i><?= $isBg ? 'Всички значки' : 'All Badges' ?></h4>
        </div>
    </div>
    <div id="badgesContainer">
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>

    <!-- Leaderboard Section -->
    <div class="row mt-5 mb-3">
        <div class="col-12">
            <h4><i class="fas fa-ranking-star me-2"></i><?= $isBg ? 'Класация' : 'Leaderboard' ?></h4>
            <p class="text-muted"><?= $isBg ? 'Топ 10 рибари по XP' : 'Top 10 anglers by XP' ?></p>
        </div>
    </div>
    <div id="leaderboardContainer">
        <div class="text-center py-4">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>
</div>
