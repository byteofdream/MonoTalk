<?php
/**
 * MonoTalk - API получения информации о посте
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

$postId = isset($input['post_id']) ? (int)$input['post_id'] : 0;

if ($postId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid post_id']);
    exit;
}

$post = getPostById($postId);

if (!$post) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit;
}

// Получаем информацию о категории
$category = getSubredditById($post['category'] ?? '');
if ($category) {
    $post['category_info'] = [
        'id' => $category['id'],
        'name' => $category['name'] ?? '',
        'name_en' => $category['name_en'] ?? '',
        'emoji' => $category['emoji'] ?? '',
        'description' => $category['description'] ?? '',
        'subscribers_count' => $category['subscribers_count'] ?? 0
    ];
}

// Получаем информацию об авторе (публичную)
$authorId = (int)($post['author_id'] ?? 0);
if ($authorId > 0) {
    $author = getUserById($authorId);
    if ($author) {
        $post['author_info'] = [
            'id' => $author['id'],
            'username' => $author['username'],
            'verified' => !empty($author['verified']),
            'created_at' => $author['created_at'] ?? null
        ];
    }
}

echo json_encode([
    'success' => true,
    'post' => $post
]);
