<?php
/**
 * Fetches Open Graph metadata from a URL for link previews
 */

function extractFirstUrl($text) {
    if (preg_match('#(https?://[^\s<>"\']+)#i', $text, $m)) {
        return $m[1];
    }
    return null;
}

function fetchLinkPreview($url) {
    if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
        return null;
    }

    $parsed = parse_url($url);
    if (!$parsed || !isset($parsed['host'])) return null;

    // Block private/internal IPs (SSRF protection)
    $host = $parsed['host'];
    $ip = gethostbyname($host);
    if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
        // DNS resolution failed, skip
    } elseif (filter_var($ip, FILTER_VALIDATE_IP)) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return null; // Block private/reserved IPs
        }
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 5,
            'follow_location' => 1,
            'max_redirects' => 3,
            'user_agent' => 'Mozilla/5.0 (compatible; FishingLinkPreview/1.0)',
            'header' => "Accept: text/html\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);

    $html = @file_get_contents($url, false, $ctx, 0, 100000); // Max 100KB
    if ($html === false) return null;

    // Force UTF-8
    if (preg_match('/charset=["\']?([^"\';\s]+)/i', $html, $cm)) {
        $charset = strtolower($cm[1]);
        if ($charset !== 'utf-8') {
            $html = @mb_convert_encoding($html, 'UTF-8', $charset) ?: $html;
        }
    }

    $preview = [
        'url' => $url,
        'title' => null,
        'description' => null,
        'image' => null,
        'domain' => $parsed['host'],
    ];

    // Parse OG tags
    if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']*)["\']/', $html, $m)
        || preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']og:title["\']/', $html, $m)) {
        $preview['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    }

    if (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\']([^"\']*)["\']/', $html, $m)
        || preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']og:description["\']/', $html, $m)) {
        $preview['description'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    }

    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']*)["\']/', $html, $m)
        || preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']og:image["\']/', $html, $m)) {
        $img = trim($m[1]);
        if (filter_var($img, FILTER_VALIDATE_URL)) {
            $preview['image'] = $img;
        }
    }

    // Fallback to <title> tag
    if (empty($preview['title']) && preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $m)) {
        $preview['title'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
    }

    // Fallback to meta description
    if (empty($preview['description'])) {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/', $html, $m)
            || preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']/', $html, $m)) {
            $preview['description'] = html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8');
        }
    }

    // Must have at least a title
    if (empty($preview['title'])) {
        $preview['title'] = $parsed['host'];
    }

    // Truncate
    if (strlen($preview['title']) > 200) $preview['title'] = mb_substr($preview['title'], 0, 200) . '…';
    if ($preview['description'] && strlen($preview['description']) > 300) $preview['description'] = mb_substr($preview['description'], 0, 300) . '…';

    return $preview;
}
