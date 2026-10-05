<?php
/**
 * MonoTalk - API избранного
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
$postId = (int)($input['post_id'] ?? 0);
$userId = (int)getCurrentUser()['id'];

if ($postId <= 0 || !getPostById($postId)) {
    echo json_encode(['success' => false, 'error' => 'Пост не найден']);
    exit;
}

$favorites = readData('favorites.json');
$found = null;
foreach ($favorites as $i => $f) {
    if ((int)$f['user_id'] === $userId && (int)$f['post_id'] === $postId) {
        $found = $i;
        break;
    }
}

$saved = ($found === null);
if ($found !== null) {
    array_splice($favorites, $found, 1);
} else {
    $favorites[] = ['user_id' => $userId, 'post_id' => $postId, 'created_at' => date('Y-m-d H:i:s')];
}
writeData('favorites.json', $favorites);

echo json_encode(['success' => true, 'saved' => $saved]);
