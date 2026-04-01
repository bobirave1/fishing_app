/**
 * FISHINGLORY — Gamification: badges, XP, levels, leaderboard.
 */
(function () {
    'use strict';

    const isBg = (document.documentElement.lang || '').startsWith('bg');
    const basePath = typeof resolvePath === 'function' ? '' : '';

    // ==================== BADGE DISPLAY ====================

    function loadBadgesPage() {
        const container = document.getElementById('badgesContainer');
        const statsContainer = document.getElementById('gamificationStats');
        if (!container) return;

        fetch(resolvePath('api/gamification/badges'))
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                renderStats(data.stats, statsContainer);
                renderBadges(data.badges, container);
            })
            .catch(function () {
                container.innerHTML = '<div class="text-center text-muted py-4">' +
                    (isBg ? 'Грешка при зареждане' : 'Failed to load') + '</div>';
            });

        // Load leaderboard
        loadLeaderboard();
    }

    function renderStats(stats, container) {
        if (!container) return;

        const level = stats.level || 1;
        const xp = stats.total_xp || 0;
        const progress = stats.xp_for_next ? stats.xp_for_next.progress : 0;
        const needed = stats.xp_for_next ? stats.xp_for_next.needed : 0;
        const nextXp = stats.xp_for_next ? stats.xp_for_next.next_level_xp : 0;
        const streak = stats.current_streak || 0;
        const longestStreak = stats.longest_streak || 0;

        container.innerHTML =
            '<div class="row g-3">' +
                '<div class="col-md-4">' +
                    '<div class="gamification-stat-card">' +
                        '<div class="stat-icon"><i class="fas fa-star"></i></div>' +
                        '<div class="stat-value">' + (isBg ? 'Ниво' : 'Level') + ' ' + level + '</div>' +
                        '<div class="stat-label">' + escapeHtml(stats.level_name || '') + '</div>' +
                        '<div class="progress mt-2" style="height: 8px;">' +
                            '<div class="progress-bar bg-success" style="width: ' + progress + '%"></div>' +
                        '</div>' +
                        '<small class="text-muted">' + xp + ' / ' + nextXp + ' XP</small>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="gamification-stat-card">' +
                        '<div class="stat-icon"><i class="fas fa-fire"></i></div>' +
                        '<div class="stat-value">' + streak + ' ' + (isBg ? 'дни' : 'days') + '</div>' +
                        '<div class="stat-label">' + (isBg ? 'Текуща серия' : 'Current Streak') + '</div>' +
                        '<small class="text-muted">' + (isBg ? 'Най-дълга: ' : 'Longest: ') + longestStreak + '</small>' +
                    '</div>' +
                '</div>' +
                '<div class="col-md-4">' +
                    '<div class="gamification-stat-card">' +
                        '<div class="stat-icon"><i class="fas fa-bolt"></i></div>' +
                        '<div class="stat-value">' + xp + ' XP</div>' +
                        '<div class="stat-label">' + (isBg ? 'Общ опит' : 'Total XP') + '</div>' +
                        (needed > 0 ? '<small class="text-muted">' + needed + ' ' +
                            (isBg ? 'до следващо ниво' : 'to next level') + '</small>' :
                            '<small class="text-muted">' + (isBg ? 'Максимално ниво!' : 'Max level!') + '</small>') +
                    '</div>' +
                '</div>' +
            '</div>';
    }

    function renderBadges(badges, container) {
        if (!badges || badges.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4">' +
                (isBg ? 'Няма налични значки' : 'No badges available') + '</div>';
            return;
        }

        var html = '<div class="row g-3">';
        badges.forEach(function (badge) {
            var earned = parseInt(badge.earned);
            var earnedClass = earned ? '' : 'badge-locked';
            var earnedDate = earned && badge.earned_at ? formatDate(badge.earned_at) : '';
            var checkmark = earned ? '<span class="badge-earned-check"><i class="fas fa-check-circle"></i></span>' : '';

            html += '<div class="col-6 col-md-4 col-lg-3">' +
                '<div class="badge-card ' + earnedClass + '">' +
                    checkmark +
                    '<div class="badge-icon" style="color: ' + escapeHtml(badge.color) + ';">' +
                        '<i class="fas ' + escapeHtml(badge.icon) + '"></i>' +
                    '</div>' +
                    '<div class="badge-name">' + escapeHtml(badge.name) + '</div>' +
                    '<div class="badge-desc">' + escapeHtml(badge.description) + '</div>' +
                    (earnedDate ? '<small class="badge-date">' + earnedDate + '</small>' : '') +
                '</div>' +
            '</div>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // ==================== LEADERBOARD ====================

    function loadLeaderboard() {
        var container = document.getElementById('leaderboardContainer');
        if (!container) return;

        fetch(resolvePath('api/gamification/leaderboard') + '?limit=10')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;
                renderLeaderboard(data.leaderboard, container);
            })
            .catch(function () {});
    }

    function renderLeaderboard(entries, container) {
        if (!entries || entries.length === 0) {
            container.innerHTML = '<div class="text-muted text-center py-3">' +
                (isBg ? 'Няма данни' : 'No data yet') + '</div>';
            return;
        }

        var html = '<div class="list-group">';
        entries.forEach(function (entry, index) {
            var rank = index + 1;
            var rankBadge = rank <= 3 ? '<span class="leaderboard-rank rank-' + rank + '">' +
                (rank === 1 ? '🥇' : rank === 2 ? '🥈' : '🥉') + '</span>' :
                '<span class="leaderboard-rank">#' + rank + '</span>';

            var avatar = typeof getAvatarUrl === 'function' ? getAvatarUrl(entry.avatar_url) :
                (entry.avatar_url || 'fe/assets/img/avatars/default.webp');

            html += '<a href="' + resolvePath('profile/' + entry.user_id) + '" class="list-group-item list-group-item-action d-flex align-items-center">' +
                rankBadge +
                '<img src="' + escapeHtml(avatar) + '" class="rounded-circle me-2" width="36" height="36" alt="">' +
                '<div class="flex-grow-1">' +
                    '<strong>' + escapeHtml(entry.full_name || entry.username) + '</strong>' +
                    '<small class="d-block text-muted">' + escapeHtml(entry.username) + '</small>' +
                '</div>' +
                '<div class="text-end">' +
                    '<strong class="text-success">' + (entry.total_xp || 0) + ' XP</strong>' +
                    '<small class="d-block text-muted"><i class="fas fa-trophy me-1"></i>' + (entry.badge_count || 0) + '</small>' +
                '</div>' +
            '</a>';
        });
        html += '</div>';
        container.innerHTML = html;
    }

    // ==================== PROFILE INTEGRATION ====================

    /**
     * Load gamification widget for profile pages.
     */
    function loadProfileGamification(userId) {
        var widget = document.getElementById('profileGamificationWidget');
        if (!widget) return;

        fetch(resolvePath('api/gamification/badges') + '?user_id=' + userId)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.success) return;

                var stats = data.stats;
                var earnedBadges = (data.badges || []).filter(function (b) { return parseInt(b.earned); });

                var html = '<div class="gamification-mini-stats mb-3">' +
                    '<span class="xp-badge"><i class="fas fa-star me-1"></i> ' +
                        (isBg ? 'Ниво ' : 'Level ') + (stats.level || 1) + ' — ' +
                        escapeHtml(stats.level_name || '') + '</span>' +
                    '<span class="xp-badge ms-2"><i class="fas fa-bolt me-1"></i> ' +
                        (stats.total_xp || 0) + ' XP</span>' +
                    '<span class="xp-badge ms-2"><i class="fas fa-fire me-1"></i> ' +
                        (stats.current_streak || 0) + ' ' + (isBg ? 'дни серия' : 'day streak') + '</span>' +
                '</div>';

                if (earnedBadges.length > 0) {
                    html += '<div class="profile-badges">';
                    earnedBadges.slice(0, 6).forEach(function (badge) {
                        html += '<span class="profile-badge-icon" title="' + escapeHtml(badge.name) + '" style="color:' + escapeHtml(badge.color) + ';">' +
                            '<i class="fas ' + escapeHtml(badge.icon) + '"></i></span>';
                    });
                    if (earnedBadges.length > 6) {
                        html += '<a href="' + resolvePath('badges') + '?user_id=' + userId + '" class="ms-1">+' + (earnedBadges.length - 6) + '</a>';
                    }
                    html += '</div>';
                }

                widget.innerHTML = html;
            })
            .catch(function () {});
    }

    // ==================== BADGE TOAST ====================

    function showBadgeToast(badge) {
        var toastHtml = '<div class="badge-toast" id="badgeToast">' +
            '<div class="badge-toast-icon" style="color: ' + escapeHtml(badge.color) + ';">' +
                '<i class="fas ' + escapeHtml(badge.icon) + '"></i>' +
            '</div>' +
            '<div class="badge-toast-text">' +
                '<strong>' + (isBg ? '🎉 Нова значка!' : '🎉 New Badge!') + '</strong><br>' +
                escapeHtml(badge.name) +
            '</div>' +
        '</div>';

        document.body.insertAdjacentHTML('beforeend', toastHtml);
        var toast = document.getElementById('badgeToast');
        setTimeout(function () { toast.classList.add('show'); }, 100);
        setTimeout(function () {
            toast.classList.remove('show');
            setTimeout(function () { toast.remove(); }, 300);
        }, 4000);
    }

    // ==================== INIT ====================

    document.addEventListener('DOMContentLoaded', function () {
        // Badges page
        if (document.getElementById('badgesContainer')) {
            loadBadgesPage();
        }

        // Profile page gamification widget
        var profileWidget = document.getElementById('profileGamificationWidget');
        if (profileWidget) {
            var uid = profileWidget.dataset.userId || document.body.dataset.userId;
            if (uid) loadProfileGamification(uid);
        }
    });

    // Global export for badge toasts
    window.showBadgeToast = showBadgeToast;
    window.loadProfileGamification = loadProfileGamification;
})();
