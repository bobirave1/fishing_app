<?php
/**
 * Handles global POST actions like language and theme switching
 */
function handleGlobalActions() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verifyCsrfToken($csrf)) {
            http_response_code(400);
            die('Invalid CSRF token');
        }

        $action = $_POST['action'];
        if ($action === 'switch_lang' && isset($_POST['lang'])) {
            $newLang = (string) $_POST['lang'];
            if (in_array($newLang, ['bg', 'en'], true)) {
                $_SESSION['lang'] = $newLang;
            }
        } elseif ($action === 'switch_theme' && isset($_POST['theme'])) {
            $newTheme = (string) $_POST['theme'];
            if (in_array($newTheme, ['light', 'dark'], true)) {
                $_SESSION['theme'] = $newTheme;
            }
        }
        header('Location: ' . ($_SERVER['REQUEST_URI'] ?? '/'));
        exit;
    }
}