<?php
/**
 * MonoTalk - API для подписки/отписки на сабреддиты
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$subredditId = trim($input['subreddit_id'] ?? '');
$action = trim($input['action'] ?? ''); // 'subscribe' или 'unsubscribe'

if (!$subredditId || !in_array($action, ['subscribe', 'unsubscribe'], true)) {
    echo json_encode(['success' => false, 'error' => 'Неверные параметры']);
    exit;
}

$user = getCurrentUser();
$result = false;

if ($action === 'subscribe') {
    $result = subscribeToSubreddit((int)$user['id'], $subredditId);
} else {
    $result = unsubscribeFromSubreddit((int)$user['id'], $subredditId);
}

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => $action === 'subscribe' ? 'Вы подписались' : 'Вы отписались',
        'action' => $action
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Ошибка при обновлении подписки']);
}
