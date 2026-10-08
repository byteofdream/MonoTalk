<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$userId = (int)getCurrentUser()['id'];
$list = readData('notifications.json');
foreach ($list as &$n) {
    if ((int)($n['user_id'] ?? 0) === $userId) {
        $n['read'] = true;
    }
}
unset($n);
writeData('notifications.json', $list);
echo json_encode(['success' => true]);
