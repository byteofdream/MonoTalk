<?php
/**
 * MonoTalk - сохранение пользовательских опций
 */

require_once __DIR__ . '/../includes/config.php';

$allowed = ['compact', 'hide_images'];
$key = $_GET['key'] ?? '';
$value = $_GET['value'] ?? '0';
$redirect = $_GET['redirect'] ?? BASE_URL . 'settings.php';

if (in_array($key, $allowed, true)) {
    setcookie('opt_' . $key, $value === '1' ? '1' : '0', time() + 60 * 60 * 24 * 365, '/');
}

if (strpos($redirect, '://') !== false) {
    $redirect = BASE_URL . 'settings.php';
}

header('Location: ' . $redirect);
exit;
