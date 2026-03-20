<?php
/**
 * MonoTalk - API авторизации
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Заполните все поля']);
    exit;
}

$user = getUserByUsername($username);
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Неверный логин или пароль']);
    exit;
}

loginUser((int)$user['id']);
$redirect = trim($input['redirect'] ?? '');
// Защита от open redirect
if (empty($redirect) || strpos($redirect, '://') !== false) {
    $redirect = BASE_URL . 'index.php';
}
echo json_encode(['success' => true, 'redirect' => $redirect]);
