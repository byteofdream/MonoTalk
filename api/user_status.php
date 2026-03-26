<?php
/**
 * MonoTalk - API user presence by ID
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$payload = json_decode(file_get_contents('php://input'), true);
$input = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? array_merge(is_array($payload) ? $payload : [], $_POST)
    : $_GET;

$userId = (int)($input['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
}

$presence = getUserStatus($userId);
if (!$presence) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'user_id' => $userId,
    'status' => $presence['status'],
    'last_seen' => $presence['last_seen'],
    'presence' => $presence,
]);
