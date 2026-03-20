<?php
/**
 * MonoTalk - Установка языка
 */

require_once __DIR__ . '/../includes/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowed = ['ru', 'en'];
$lang = $_POST['lang'] ?? $_GET['lang'] ?? '';

if (in_array($lang, $allowed)) {
    $_SESSION['lang'] = $lang;
    setcookie('lang', $lang, time() + 60 * 60 * 24 * 365, '/');
}

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if (empty($redirect) || strpos($redirect, '://') !== false) {
    $redirect = '/index.php';
}
header('Location: ' . $redirect);
exit;
