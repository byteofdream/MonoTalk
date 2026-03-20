<?php
/**
 * MonoTalk - Конфигурация
 * Для хостинга: измените BASE_URL на путь к проекту (например /MonoTalkV2/)
 */

define('BASE_URL', '/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
// Ссылка на репозиторий проекта (замените на свой)
define('GITHUB_URL', 'https://github.com');

function getTheme(): string {
    $allowed = ['light', 'dark'];
    if (isset($_COOKIE['theme']) && in_array($_COOKIE['theme'], $allowed)) {
        return $_COOKIE['theme'];
    }
    return 'light';
}
