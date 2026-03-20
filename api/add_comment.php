<?php
/**
 * MonoTalk - API добавления комментария
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

if (!checkSpamProtection('add_comment', 5)) {
    echo json_encode(['success' => false, 'error' => 'Подождите перед новым комментарием']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$postId = (int)($input['post_id'] ?? 0);
$content = trim($input['content'] ?? '');
$anonymous = !empty($input['anonymous']);

if (empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
    exit;
}

$post = getPostById($postId);
if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Пост не найден']);
    exit;
}

$user = getCurrentUser();
$comments = readData('comments.json');

$newComment = [
    'id' => getNextId('comments.json'),
    'post_id' => $postId,
    'author_id' => $anonymous ? 0 : (int)$user['id'],
    'author_name' => $anonymous ? 'Anonymous' : $user['username'],
    'content' => $content,
    'anonymous' => $anonymous,
    'created_at' => date('Y-m-d H:i:s'),
    'likes' => 0
];

$verified = ((int)$newComment['author_id'] > 0) ? isUserVerifiedById((int)$newComment['author_id']) : false;

$comments[] = $newComment;
writeData('comments.json', $comments);

// Обновляем счётчик комментариев у поста
$posts = readData('posts.json');
foreach ($posts as &$p) {
    if ((int)$p['id'] === $postId) {
        $p['comments_count'] = ($p['comments_count'] ?? 0) + 1;
        break;
    }
}
writeData('posts.json', $posts);

echo json_encode([
    'success' => true,
    'comment' => [
        'id' => $newComment['id'],
        'author_name' => $newComment['author_name'],
        'content' => $newComment['content'],
        'created_at' => $newComment['created_at'],
        'likes' => 0,
        'verified' => $verified
    ]
]);
