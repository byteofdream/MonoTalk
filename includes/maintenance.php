<?php
/**
 * MonoTalk - режим обслуживания (maintenance mode)
 *
 * Когда включён — все страницы (кроме exempt и login/register)
 * редиректят на maintenance.php. Пользователи с галочкой (verified)
 * ходят по сайту свободно.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Включён ли режим обслуживания?
 */
function isMaintenanceEnabled(): bool {
    $data = readData('maintenance.json');
    return !empty($data['enabled']);
}

/**
 * Включить/выключить режим обслуживания
 */
function setMaintenanceMode(bool $enabled): void {
    writeData('maintenance.json', [
        'enabled' => $enabled,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);
}

/**
 * Текущий скрипт вне зоны редиректа?
 * login/register (и их API) + сама maintenance.php + смена языка.
 */
function isMaintenanceExempt(): bool {
    $base = basename($_SERVER['SCRIPT_NAME'] ?? '');
    return in_array($base, [
        'maintenance.php',
        'login.php',
        'register.php',
        'set_language.php',
    ], true);
}

/**
 * Применить режим обслуживания: редирект для всех, кроме verified
 */
function enforceMaintenance(): void {
    if (!isMaintenanceEnabled()) {
        return;
    }
    if (isMaintenanceExempt()) {
        return;
    }

    $user = isLoggedIn() ? getCurrentUser() : null;
    if ($user && !empty($user['verified'])) {
        return; // админ с галочкой ходит свободно
    }

    header('Location: ' . BASE_URL . 'maintenance.php');
    exit;
}
