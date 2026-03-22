<?php
/**
 * MonoTalk - Конфигурация
 * Для хостинга: измените BASE_URL на путь к проекту (например /MonoTalkV2/)
 */

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'UTC');

define('BASE_URL', '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
// Ссылка на репозиторий проекта (замените на свой)
define('GITHUB_URL', 'https://github.com');
define('MODERATION_MUTE_HOURS', (int)(getenv('MODERATION_MUTE_HOURS') ?: 24));
define('MODERATION_HIGH_STRIKE_THRESHOLD', 3);
define('MODERATION_BAN_STRIKE_THRESHOLD', 5);

function getTheme(): string {
    $allowed = ['light', 'dark'];
    if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], $allowed)) {
        return $_COOKIE['theme'];
    }
    return 'light';
}
