<?php
/**
 * MonoTalk - API лайков
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
$type = $input['type'] ?? ''; // 'post' или 'comment'
$targetId = (int)($input['target_id'] ?? 0);
$user = getCurrentUser();
$userId = (int)$user['id'];

if (!in_array($type, ['post', 'comment']) || $targetId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Неверные данные']);
    exit;
}

$likes = readData('likes.json');
$found = null;
foreach ($likes as $i => $like) {
    if ((int)$like['user_id'] === $userId && 
        $like['target_type'] === $type && 
        (int)$like['target_id'] === $targetId) {
        $found = $i;
        break;
    }
}

$isLike = ($found === null);

if ($found !== null) {
    array_splice($likes, $found, 1);
} else {
    $likes[] = [
        'user_id' => $userId,
        'target_type' => $type,
        'target_id' => $targetId,
        'created_at' => date('Y-m-d H:i:s')
    ];
}
writeData('likes.json', $likes);

if ($type === 'post') {
    updatePostLikesCount($targetId);
} else {
    updateCommentLikesCount($targetId);
}

$count = 0;
if ($type === 'post') {
    $target = getPostById($targetId);
    $count = $target['likes'] ?? 0;
} else {
    $comments = readData('comments.json');
    foreach ($comments as $c) {
        if ((int)$c['id'] === $targetId) {
            $count = $c['likes'] ?? 0;
            break;
        }
    }
}

echo json_encode(['success' => true, 'liked' => $isLike, 'count' => $count]);
