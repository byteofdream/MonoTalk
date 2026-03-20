<?php
/**
 * MonoTalk - API отметки о просмотре welcome
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$user = getCurrentUser();
$userId = (int)$user['id'];

$users = readData('users.json');
foreach ($users as &$u) {
    if ((int)$u['id'] === $userId) {
        $u['seen_welcome'] = true;
        break;
    }
}
writeData('users.json', $users);

echo json_encode(['success' => true]);
