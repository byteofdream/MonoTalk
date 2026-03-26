<?php
/**
 * MonoTalk - API heartbeat for online presence
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

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authorization required']);
    exit;
}

$user = getCurrentUser(false);
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authorization required']);
    exit;
}

$lastSeen = touchUserActivity((int)$user['id']);
$presence = getUserStatus((int)$user['id']);

echo json_encode([
    'success' => true,
    'user_id' => (int)$user['id'],
    'status' => $presence['status'] ?? 'offline',
    'last_seen' => $lastSeen ?? ($presence['last_seen'] ?? null),
    'presence' => $presence,
]);
