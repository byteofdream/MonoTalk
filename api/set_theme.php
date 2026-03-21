<?php
/**
 * MonoTalk - Установка темы
 */

require_once __DIR__ . '/../includes/config.php';

$allowed = ['light', 'dark'];
$theme = $_POST['theme'] ?? $_GET['theme'] ?? '';

if (in_array($theme, $allowed)) {
    setcookie('theme', $theme, time() + 60 * 60 * 24 * 365, '/');
}

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if (empty($redirect) || strpos($redirect, '://') !== false) {
    $redirect = '/index.php';
}
header('Location: ' . $redirect);
exit;
