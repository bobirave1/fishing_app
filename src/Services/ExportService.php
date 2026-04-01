<?php

namespace App\Services;

/**
 * Export catches as GPX (XML) or printable HTML (PDF via browser print).
 */
class ExportService
{
    public function __construct(private \PDO $pdo) {}

    /**
     * Generate GPX XML for a user's catches.
     *
     * @param int   $userId
     * @param array $filters Keys: date_from, date_to, species
     * @return string GPX XML
     */
    public function exportGpx(int $userId, array $filters = []): string
    {
        $catches = $this->getCatches($userId, $filters);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<gpx version="1.1" creator="FISHINGLORY"' .
                ' xmlns="http://www.topografix.com/GPX/1/1"' .
                ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' .
                ' xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd">' . "\n";

        $xml .= "  <metadata>\n";
        $xml .= "    <name>FISHINGLORY Catches Export</name>\n";
        $xml .= "    <time>" . date('c') . "</time>\n";
        $xml .= "  </metadata>\n";

        foreach ($catches as $c) {
            if (empty($c['latitude']) || empty($c['longitude'])) continue;

            $lat = htmlspecialchars($c['latitude'], ENT_XML1);
            $lon = htmlspecialchars($c['longitude'], ENT_XML1);
            $name = htmlspecialchars($c['fish_species'] ?? 'Unknown', ENT_XML1);
            $desc = htmlspecialchars($this->buildDescription($c), ENT_XML1);
            $time = !empty($c['catch_date']) ? htmlspecialchars($c['catch_date'] . 'T12:00:00Z', ENT_XML1) : '';

            $xml .= "  <wpt lat=\"{$lat}\" lon=\"{$lon}\">\n";
            $xml .= "    <name>{$name}</name>\n";
            $xml .= "    <desc>{$desc}</desc>\n";
            if ($time) {
                $xml .= "    <time>{$time}</time>\n";
            }
            $xml .= "    <type>Fishing Catch</type>\n";
            $xml .= "  </wpt>\n";
        }

        $xml .= "</gpx>\n";
        return $xml;
    }

    /**
     * Generate printable HTML for catches (browser print → PDF).
     */
    public function exportHtml(int $userId, array $filters = []): string
    {
        $catches = $this->getCatches($userId, $filters);

        // Get user info
        $stmt = $this->pdo->prepare("
            SELECT u.username, u.full_name FROM users u WHERE u.id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);
        $displayName = htmlspecialchars($user['full_name'] ?: $user['username']);

        $lang = $_SESSION['lang'] ?? 'en';
        $isBg = $lang === 'bg';

        $title = $isBg ? 'Дневник на улова' : 'Catch Journal';
        $dateRangeText = '';
        if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? '—';
            $to = $filters['date_to'] ?? ($isBg ? 'днес' : 'today');
            $dateRangeText = "({$from} — {$to})";
        }

        $html = '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="UTF-8">';
        $html .= '<title>' . $title . ' — ' . $displayName . '</title>';
        $html .= '<style>
            body { font-family: Georgia, serif; max-width: 800px; margin: 0 auto; padding: 20px; color: #333; }
            h1 { text-align: center; color: #1a5b2c; border-bottom: 2px solid #1a5b2c; padding-bottom: 10px; }
            .meta { text-align: center; color: #666; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 14px; }
            th { background: #1a5b2c; color: #fff; padding: 8px 12px; text-align: left; }
            td { padding: 8px 12px; border-bottom: 1px solid #ddd; }
            tr:nth-child(even) { background: #f9f9f9; }
            .summary { margin-top: 20px; padding: 15px; background: #e8f5e9; border-radius: 8px; }
            .footer { text-align: center; margin-top: 30px; color: #999; font-size: 12px; }
            @media print { body { margin: 0; } .no-print { display: none; } }
        </style></head><body>';

        $html .= '<h1>🐟 ' . $title . '</h1>';
        $html .= '<div class="meta">' . $displayName . ' ' . $dateRangeText . '</div>';

        // Summary
        $totalCatches = count($catches);
        $totalWeight = array_sum(array_column($catches, 'weight'));
        $species = count(array_unique(array_column($catches, 'fish_species')));

        $html .= '<div class="summary">';
        $html .= '<strong>' . ($isBg ? 'Обобщение' : 'Summary') . ':</strong> ';
        $html .= ($isBg ? "{$totalCatches} улова" : "{$totalCatches} catches") . ' · ';
        $html .= ($isBg ? "{$species} вида" : "{$species} species") . ' · ';
        $html .= ($isBg ? number_format($totalWeight, 2) . ' кг общо тегло' : number_format($totalWeight, 2) . ' kg total weight');
        $html .= '</div>';

        // Table
        $html .= '<table><thead><tr>';
        $html .= '<th>' . ($isBg ? 'Дата' : 'Date') . '</th>';
        $html .= '<th>' . ($isBg ? 'Вид' : 'Species') . '</th>';
        $html .= '<th>' . ($isBg ? 'Тегло (кг)' : 'Weight (kg)') . '</th>';
        $html .= '<th>' . ($isBg ? 'Дължина (см)' : 'Length (cm)') . '</th>';
        $html .= '<th>' . ($isBg ? 'Стръв' : 'Bait') . '</th>';
        $html .= '<th>' . ($isBg ? 'Водоем' : 'Location') . '</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($catches as $c) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($c['catch_date'] ?? '—') . '</td>';
            $html .= '<td>' . htmlspecialchars($c['fish_species'] ?? '—') . '</td>';
            $html .= '<td>' . ($c['weight'] ? number_format((float)$c['weight'], 2) : '—') . '</td>';
            $html .= '<td>' . ($c['length'] ? number_format((float)$c['length'], 1) : '—') . '</td>';
            $html .= '<td>' . htmlspecialchars($c['bait'] ?? '—') . '</td>';
            $html .= '<td>' . htmlspecialchars($c['waterbody_name'] ?? '—') . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';

        $html .= '<div class="no-print" style="text-align:center;margin:20px 0;">';
        $html .= '<button onclick="window.print()" style="padding:10px 30px;font-size:16px;cursor:pointer;background:#1a5b2c;color:#fff;border:none;border-radius:6px;">';
        $html .= '🖨️ ' . ($isBg ? 'Принтирай / Запази PDF' : 'Print / Save as PDF') . '</button></div>';

        $html .= '<div class="footer">FISHINGLORY — ' . date('Y-m-d H:i') . '</div>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Get catches for export with optional filters.
     */
    private function getCatches(int $userId, array $filters): array
    {
        $conditions = ['p.user_id = ?'];
        $params = [$userId];

        if (!empty($filters['date_from'])) {
            $conditions[] = 'fc.catch_date >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 'fc.catch_date <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['species'])) {
            $escaped = addcslashes($filters['species'], '%_\\');
            $conditions[] = 'fc.fish_species LIKE ?';
            $params[] = "%{$escaped}%";
        }

        $where = implode(' AND ', $conditions);

        $stmt = $this->pdo->prepare("
            SELECT fc.*, w.name AS waterbody_name, w.latitude, w.longitude
            FROM fish_catches fc
            JOIN posts p ON fc.post_id = p.id
            LEFT JOIN waterbodies w ON fc.waterbody_id = w.id
            WHERE {$where}
            ORDER BY fc.catch_date DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Build a text description for a GPX waypoint.
     */
    private function buildDescription(array $catch): string
    {
        $parts = [];
        if (!empty($catch['weight'])) $parts[] = "Weight: {$catch['weight']}kg";
        if (!empty($catch['length'])) $parts[] = "Length: {$catch['length']}cm";
        if (!empty($catch['bait']))   $parts[] = "Bait: {$catch['bait']}";
        if (!empty($catch['waterbody_name'])) $parts[] = "Location: {$catch['waterbody_name']}";
        if (!empty($catch['catch_date'])) $parts[] = "Date: {$catch['catch_date']}";
        return implode(' | ', $parts);
    }
}
