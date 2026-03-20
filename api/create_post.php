<?php
/**
 * MonoTalk - API создания поста
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

if (!checkSpamProtection('create_post', 10)) {
    echo json_encode(['success' => false, 'error' => 'Подождите перед созданием нового поста']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');
$category = trim($input['category'] ?? '');
$anonymous = !empty($input['anonymous']);

if (empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Заполните заголовок и текст']);
    exit;
}

if (empty($category) || !getCategoryById($category)) {
    echo json_encode(['success' => false, 'error' => 'Выберите категорию']);
    exit;
}

$user = getCurrentUser();
$posts = readData('posts.json');

$newPost = [
    'id' => getNextId('posts.json'),
    'title' => $title,
    'content' => $content,
    'category' => $category,
    'author_id' => $anonymous ? 0 : (int)$user['id'],
    'author_name' => $anonymous ? 'Anonymous' : $user['username'],
    'anonymous' => $anonymous,
    'created_at' => date('Y-m-d H:i:s'),
    'likes' => 0,
    'comments_count' => 0
];

$posts[] = $newPost;
writeData('posts.json', $posts);

echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . 'post.php?id=' . $newPost['id'],
    'post_id' => $newPost['id']
]);
