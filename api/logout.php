<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
$wantsJson = $method === 'POST' || strpos($accept, 'application/json') !== false;

logoutUser();

if ($wantsJson) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true]);
    exit;
}

header('Location: ' . BASE_URL . 'index.php');
exit;
