<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\ExportService;

class ExportController extends Controller
{
    /**
     * Export page (filter + export buttons).
     */
    public function page(): void
    {
        $this->requireAuth();
        $pageTitle = __('export_catches') . ' | FISHINGLORY';
        $pageCss = ['fe/assets/css/gamification.css'];
        $pageJs = ['fe/assets/js/export.js'];

        $content = function () {
            include dirname(__DIR__, 2) . '/templates/pages/export.php';
        };
        include dirname(__DIR__, 2) . '/templates/layouts/main.php';
    }

    /**
     * Download GPX file.
     */
    public function gpx(): void
    {
        $userId = $this->requireAuth();
        $filters = $this->getFilters();

        $service = $this->service(ExportService::class);
        $gpx = $service->exportGpx($userId, $filters);

        $filename = 'fishinglory_catches_' . date('Y-m-d') . '.gpx';
        header('Content-Type: application/gpx+xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($gpx));
        echo $gpx;
        exit;
    }

    /**
     * Render printable HTML (user prints to PDF via browser).
     */
    public function pdf(): void
    {
        $userId = $this->requireAuth();
        $filters = $this->getFilters();

        $service = $this->service(ExportService::class);
        echo $service->exportHtml($userId, $filters);
        exit;
    }

    private function getFilters(): array
    {
        return [
            'date_from' => $_GET['date_from'] ?? null,
            'date_to'   => $_GET['date_to']   ?? null,
            'species'   => trim($_GET['species'] ?? ''),
        ];
    }
}
