<?php
/**
 * MonoTalk - login API
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

if ($username === '' || $password === '') {
    echo json_encode(['success' => false, 'error' => 'Fill in all fields']);
    exit;
}

$user = getUserByUsername($username);
if (!$user || !password_verify($password, $user['password'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid username or password']);
    exit;
}

if (isUserBanned($user)) {
    echo json_encode(['success' => false, 'error' => 'Account is banned']);
    exit;
}

loginUser((int)$user['id']);
$redirect = trim($input['redirect'] ?? '');
if ($redirect === '' || strpos($redirect, '://') !== false) {
    $redirect = BASE_URL . 'index.php';
}

echo json_encode([
    'success' => true,
    'redirect' => $redirect,
    'user_id' => (int)$user['id'],
    'user' => [
        'id' => (int)$user['id'],
        'username' => $user['username'],
        'avatar' => $user['avatar'] ?? null
    ],
    'presence' => getUserStatus((int)$user['id']),
]);
