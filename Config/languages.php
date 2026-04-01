<?php
/**
 * Language translations for FISHINGLORY
 * Supports English (en) and Bulgarian (bg)
 *
 * Translations are stored in config/translations/{lang}.php
 */

// Set default language to Bulgarian if not set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'bg';
}

$lang = $_SESSION['lang'];

$translations = [
    'en' => require __DIR__ . '/translations/en.php',
    'bg' => require __DIR__ . '/translations/bg.php',
];

// Function to get translated text
function __($key) {
    global $lang, $translations;
    return $translations[$lang][$key] ?? $key;
}

// Function to get current language
function getCurrentLang() {
    global $lang;
    return $lang;
}

// Function to get available languages
function getAvailableLanguages() {
    return ['en' => 'English', 'bg' => 'Български'];
}
?>