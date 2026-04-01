<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\SearchService;
use App\Services\AdvancedSearchService;

class SearchController extends Controller
{
    public function search(): void
    {
        $query = trim($_GET['q'] ?? '');
        $type = $_GET['type'] ?? 'all';

        if (strlen($query) < 2) {
            $this->jsonError('Search query too short');
        }
        if (strlen($query) > 100) {
            $this->jsonError('Search query too long');
        }

        $currentUserId = $_SESSION['user_id'] ?? 0;
        $service = $this->service(SearchService::class);
        $results = $service->search($query, $currentUserId, $type);

        // Fix avatar paths
        foreach ($results['users'] as &$user) {
            if (empty($user['avatar_url'])) {
                $user['avatar_url'] = getDefaultAvatarPath();
            }
        }

        $this->jsonOk([
            'query' => htmlspecialchars($query),
            'results' => $results,
        ]);
    }

    /**
     * Advanced search with filters page.
     */
    public function advancedPage(): void
    {
        $pageTitle = __('advanced_search') . ' | FISHINGLORY';
        $pageCss = ['fe/assets/css/gamification.css'];
        $pageJs = ['fe/assets/js/advanced_search.js'];

        $content = function () {
            include dirname(__DIR__, 2) . '/templates/pages/advanced_search.php';
        };
        include dirname(__DIR__, 2) . '/templates/layouts/main.php';
    }

    /**
     * Advanced search API.
     */
    public function advancedSearch(): void
    {
        $currentUserId = $_SESSION['user_id'] ?? 0;

        $filters = [
            'query'        => trim($_GET['q'] ?? ''),
            'type'         => $_GET['type'] ?? 'all',
            'date_from'    => $_GET['date_from'] ?? null,
            'date_to'      => $_GET['date_to'] ?? null,
            'species'      => trim($_GET['species'] ?? ''),
            'location_lat' => $_GET['lat'] ?? null,
            'location_lon' => $_GET['lon'] ?? null,
            'radius_km'    => $_GET['radius'] ?? 50,
            'sort'         => $_GET['sort'] ?? 'relevance',
            'page'         => $_GET['page'] ?? 1,
            'per_page'     => $_GET['per_page'] ?? 15,
        ];

        $service = $this->service(AdvancedSearchService::class);
        $results = $service->search($filters, $currentUserId);

        // Fix avatar paths
        if (isset($results['users'])) {
            foreach ($results['users'] as &$user) {
                if (empty($user['avatar_url'])) {
                    $user['avatar_url'] = getDefaultAvatarPath();
                }
            }
        }

        $this->jsonOk(['results' => $results, 'filters' => $filters]);
    }
}
