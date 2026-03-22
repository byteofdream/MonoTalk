<?php
/**
 * MonoTalk - API получения информации о пользователе по ID
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST + json_decode(file_get_contents('php://input'), true) ?? []) : $_GET;

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
}

$user = getUserById($userId);

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

// Публичная информация о пользователе
$userInfo = [
    'id' => $user['id'],
    'username' => $user['username'],
    'avatar' => $user['avatar'] ?? null,
    'verified' => !empty($user['verified']),
    'created_at' => $user['created_at'] ?? null,
    'subscriptions_count' => count($user['subscriptions'] ?? []),
    'role' => $user['role'] ?? 'user',
    'status' => $user['status'] ?? 'active'
];

echo json_encode([
    'success' => true,
    'user' => $userInfo
]);
