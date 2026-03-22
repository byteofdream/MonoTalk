<?php
/**
 * MonoTalk - API поиска пользователей
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

$query = trim((string)($input['query'] ?? $input['search'] ?? ''));

if ($query === '') {
    echo json_encode(['success' => true, 'users' => []]);
    exit;
}

$users = searchUsers($query);

echo json_encode([
    'success' => true,
    'count' => count($users),
    'users' => $users
]);
