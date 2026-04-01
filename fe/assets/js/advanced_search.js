/**
 * FISHINGLORY — Advanced search with filters.
 */
(function () {
    'use strict';

    var searchTimer = null;
    var currentPage = 1;
    var isLoading = false;

    var isBg = (document.documentElement.lang || '').startsWith('bg');

    function init() {
        var form = document.getElementById('advancedSearchForm');
        if (!form) return;

        // Live search on input
        var queryInput = document.getElementById('searchQuery');
        if (queryInput) {
            queryInput.addEventListener('input', function () {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(function () { doSearch(1); }, 400);
            });
        }

        // Filter change triggers search
        ['searchType', 'searchSort', 'searchSpecies', 'searchDateFrom', 'searchDateTo', 'searchRadius'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) el.addEventListener('change', function () { doSearch(1); });
        });

        // Form submit
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            doSearch(1);
        });

        // Geo location button
        var geoBtn = document.getElementById('useMyLocation');
        if (geoBtn) {
            geoBtn.addEventListener('click', function () {
                if (!navigator.geolocation) {
                    alert(isBg ? 'Геолокацията не е налична' : 'Geolocation not available');
                    return;
                }
                navigator.geolocation.getCurrentPosition(function (pos) {
                    document.getElementById('searchLat').value = pos.coords.latitude.toFixed(6);
                    document.getElementById('searchLon').value = pos.coords.longitude.toFixed(6);
                    geoBtn.innerHTML = '<i class="fas fa-check text-success"></i> ' + (isBg ? 'Местоположение зададено' : 'Location set');
                    doSearch(1);
                }, function () {
                    alert(isBg ? 'Не може да се определи местоположението' : 'Could not get location');
                });
            });
        }

        // Clear location
        var clearGeo = document.getElementById('clearLocation');
        if (clearGeo) {
            clearGeo.addEventListener('click', function () {
                document.getElementById('searchLat').value = '';
                document.getElementById('searchLon').value = '';
                document.getElementById('useMyLocation').innerHTML = '<i class="fas fa-map-marker-alt"></i> ' +
                    (isBg ? 'Моята локация' : 'My Location');
                doSearch(1);
            });
        }

        // Load more button
        var loadMoreBtn = document.getElementById('loadMoreResults');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function () {
                doSearch(currentPage + 1, true);
            });
        }

        // Initial search if query param present
        var urlParams = new URLSearchParams(window.location.search);
        var q = urlParams.get('q');
        if (q && queryInput) {
            queryInput.value = q;
            doSearch(1);
        }
    }

    function doSearch(page, append) {
        if (isLoading) return;

        var query = (document.getElementById('searchQuery').value || '').trim();
        var type = (document.getElementById('searchType') || {}).value || 'all';
        var sort = (document.getElementById('searchSort') || {}).value || 'relevance';
        var species = (document.getElementById('searchSpecies') || {}).value || '';
        var dateFrom = (document.getElementById('searchDateFrom') || {}).value || '';
        var dateTo = (document.getElementById('searchDateTo') || {}).value || '';
        var lat = (document.getElementById('searchLat') || {}).value || '';
        var lon = (document.getElementById('searchLon') || {}).value || '';
        var radius = (document.getElementById('searchRadius') || {}).value || '50';

        var params = new URLSearchParams();
        if (query) params.set('q', query);
        params.set('type', type);
        params.set('sort', sort);
        params.set('page', page);
        params.set('per_page', '15');
        if (species) params.set('species', species);
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        if (lat && lon) { params.set('lat', lat); params.set('lon', lon); params.set('radius', radius); }

        isLoading = true;
        var resultsContainer = document.getElementById('searchResults');
        if (!append && resultsContainer) {
            resultsContainer.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i></div>';
        }

        fetch(resolvePath('api/search/advanced') + '?' + params.toString())
            .then(function (r) { return r.json(); })
            .then(function (data) {
                isLoading = false;
                if (!data.success) return;
                currentPage = page;
                renderResults(data.results, append);
            })
            .catch(function () {
                isLoading = false;
                if (resultsContainer) {
                    resultsContainer.innerHTML = '<div class="text-center text-danger py-4">' +
                        (isBg ? 'Грешка при търсене' : 'Search error') + '</div>';
                }
            });
    }

    function renderResults(results, append) {
        var container = document.getElementById('searchResults');
        if (!container) return;

        var html = append ? container.innerHTML.replace(/<div class="text-center py-3" id="loadMoreWrap">.*?<\/div>/s, '') : '';
        var hasResults = false;

        // Posts
        if (results.posts && results.posts.length > 0) {
            hasResults = true;
            if (!append) html += '<h5 class="search-section-title mt-3"><i class="fas fa-newspaper me-2"></i>' +
                (isBg ? 'Публикации' : 'Posts') + ' (' + results.posts.length + ')</h5>';
            results.posts.forEach(function (post) {
                html += renderPostResult(post);
            });
        }

        // Users
        if (results.users && results.users.length > 0) {
            hasResults = true;
            html += '<h5 class="search-section-title mt-3"><i class="fas fa-users me-2"></i>' +
                (isBg ? 'Потребители' : 'Users') + ' (' + results.users.length + ')</h5>';
            results.users.forEach(function (user) {
                html += renderUserResult(user);
            });
        }

        // Spots
        if (results.spots && results.spots.length > 0) {
            hasResults = true;
            html += '<h5 class="search-section-title mt-3"><i class="fas fa-map-marker-alt me-2"></i>' +
                (isBg ? 'Водоеми' : 'Fishing Spots') + ' (' + results.spots.length + ')</h5>';
            results.spots.forEach(function (spot) {
                html += renderSpotResult(spot);
            });
        }

        // Catches
        if (results.catches && results.catches.length > 0) {
            hasResults = true;
            html += '<h5 class="search-section-title mt-3"><i class="fas fa-fish me-2"></i>' +
                (isBg ? 'Улови' : 'Catches') + ' (' + results.catches.length + ')</h5>';
            results.catches.forEach(function (c) {
                html += renderCatchResult(c);
            });
        }

        if (!hasResults && !append) {
            html = '<div class="text-center text-muted py-5">' +
                '<i class="fas fa-search fa-3x mb-3" style="opacity:0.3;"></i>' +
                '<p>' + (isBg ? 'Няма намерени резултати' : 'No results found') + '</p></div>';
        }

        // Load more
        var totalResults = (results.posts || []).length + (results.users || []).length +
            (results.spots || []).length + (results.catches || []).length;
        if (totalResults >= 15) {
            html += '<div class="text-center py-3" id="loadMoreWrap">' +
                '<button class="btn btn-outline-primary" id="loadMoreResults">' +
                (isBg ? 'Зареди още' : 'Load More') + '</button></div>';
        }

        container.innerHTML = html;

        // Re-bind load more
        var btn = document.getElementById('loadMoreResults');
        if (btn) {
            btn.addEventListener('click', function () { doSearch(currentPage + 1, true); });
        }
    }

    function renderPostResult(post) {
        var avatar = typeof getAvatarUrl === 'function' ? getAvatarUrl(post.avatar_url) : (post.avatar_url || '');
        var content = (post.content || '').substring(0, 150);
        return '<div class="search-result-card">' +
            '<div class="d-flex align-items-start">' +
                '<img src="' + escapeHtml(avatar) + '" class="rounded-circle me-2" width="40" height="40" alt="">' +
                '<div class="flex-grow-1">' +
                    '<strong>' + escapeHtml(post.title || '') + '</strong>' +
                    '<small class="text-muted ms-2">@' + escapeHtml(post.username) + ' · ' + formatDate(post.created_at) + '</small>' +
                    '<p class="mb-1 text-truncate">' + escapeHtml(content) + '</p>' +
                    '<small class="text-muted">' +
                        '<i class="fas fa-heart me-1"></i>' + (post.like_count || 0) + ' ' +
                        '<i class="fas fa-comment ms-2 me-1"></i>' + (post.comment_count || 0) +
                    '</small>' +
                '</div>' +
            '</div></div>';
    }

    function renderUserResult(user) {
        var avatar = typeof getAvatarUrl === 'function' ? getAvatarUrl(user.avatar_url) : (user.avatar_url || '');
        return '<a href="' + resolvePath('profile/' + user.id) + '" class="search-result-card d-flex align-items-center text-decoration-none">' +
            '<img src="' + escapeHtml(avatar) + '" class="rounded-circle me-3" width="48" height="48" alt="">' +
            '<div class="flex-grow-1">' +
                '<strong>' + escapeHtml(user.full_name || user.username) + '</strong>' +
                '<small class="text-muted d-block">@' + escapeHtml(user.username) + '</small>' +
                (user.location ? '<small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>' + escapeHtml(user.location) + '</small>' : '') +
            '</div>' +
            '<div class="text-end">' +
                '<small class="text-muted">' + (user.post_count || 0) + ' ' + (isBg ? 'публикации' : 'posts') + '</small>' +
                (user.total_xp ? '<small class="d-block text-success">' + user.total_xp + ' XP</small>' : '') +
            '</div>' +
        '</a>';
    }

    function renderSpotResult(spot) {
        var distanceText = spot.distance_km ? ' · ' + parseFloat(spot.distance_km).toFixed(1) + 'km' : '';
        return '<div class="search-result-card">' +
            '<div class="d-flex align-items-center">' +
                '<div class="spot-icon me-3"><i class="fas fa-water"></i></div>' +
                '<div class="flex-grow-1">' +
                    '<strong>' + escapeHtml(spot.name) + '</strong>' +
                    '<small class="text-muted ms-2">' + escapeHtml(spot.type || '') + distanceText + '</small>' +
                    (spot.description ? '<p class="mb-0 text-muted small">' + escapeHtml(spot.description).substring(0, 100) + '</p>' : '') +
                '</div>' +
            '</div></div>';
    }

    function renderCatchResult(c) {
        var avatar = typeof getAvatarUrl === 'function' ? getAvatarUrl(c.avatar_url) : (c.avatar_url || '');
        return '<div class="search-result-card">' +
            '<div class="d-flex align-items-center">' +
                '<img src="' + escapeHtml(avatar) + '" class="rounded-circle me-2" width="36" height="36" alt="">' +
                '<div class="flex-grow-1">' +
                    '<strong><i class="fas fa-fish me-1"></i>' + escapeHtml(c.fish_species || '?') + '</strong>' +
                    '<small class="text-muted ms-2">@' + escapeHtml(c.username) + '</small>' +
                    '<div class="small">' +
                        (c.weight ? '<span class="me-2">' + c.weight + 'kg</span>' : '') +
                        (c.length ? '<span class="me-2">' + c.length + 'cm</span>' : '') +
                        (c.bait ? '<span class="me-2"><i class="fas fa-worm me-1"></i>' + escapeHtml(c.bait) + '</span>' : '') +
                        (c.waterbody_name ? '<span><i class="fas fa-map-marker-alt me-1"></i>' + escapeHtml(c.waterbody_name) + '</span>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="text-end text-muted small">' + escapeHtml(c.catch_date || '') + '</div>' +
            '</div></div>';
    }

    // ==================== INIT ====================
    document.addEventListener('DOMContentLoaded', init);
})();
