<?php
/**
 * MonoTalk - Конфигурация
 * Для хостинга: измените BASE_URL на путь к проекту (например /MonoTalkV2/)
 */

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_URL', '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
// Ссылка на репозиторий проекта (замените на свой)
define('GITHUB_URL', 'https://github.com');
define('MODERATION_MUTE_HOURS', (int)(getenv('MODERATION_MUTE_HOURS') ?: 24));
define('MODERATION_HIGH_STRIKE_THRESHOLD', 3);
define('MODERATION_BAN_STRIKE_THRESHOLD', 5);

// Фолбэк если расширение mbstring не установлено
if (!function_exists('mb_substr')) {
    function mb_substr($s, $start, $len = null) {
        return $len === null ? substr($s, $start) : substr($s, $start, $len);
    }
}
if (!function_exists('mb_strlen')) {
    function mb_strlen($s) { return strlen($s); }
}
if (!function_exists('mb_strtolower')) {
    function mb_strtolower($s) { return strtolower($s); }
}
if (!function_exists('mb_strpos')) {
    function mb_strpos($haystack, $needle) { return strpos($haystack, $needle); }
}

function getTheme(): string {
    $allowed = ['light', 'dark'];
    if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], $allowed)) {
        return $_COOKIE['theme'];
    }
    return 'light';
}
