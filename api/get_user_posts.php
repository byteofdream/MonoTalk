<?php
/**
 * MonoTalk - API получения постов пользователя
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

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$sort = trim((string)($input['sort'] ?? 'new'));
$limit = isset($input['limit']) ? (int)$input['limit'] : 0;
$offset = isset($input['offset']) ? max(0, (int)$input['offset']) : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user_id']);
    exit;
}

$user = getUserById($userId);
if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$posts = readData('posts.json');
$posts = array_filter($posts, fn($p) => (int)($p['author_id'] ?? 0) === $userId);
$currentUserId = isLoggedIn() ? (int)((getCurrentUser()['id'] ?? 0)) : 0;

usort($posts, function($a, $b) use ($sort) {
    if ($sort === 'popular') {
        return ($b['likes'] ?? 0) - ($a['likes'] ?? 0);
    }
    if ($sort === 'hot') {
        return getPostHotScore($b) <=> getPostHotScore($a);
    }
    if ($sort === 'discussed') {
        return ((int)($b['comments_count'] ?? 0)) - ((int)($a['comments_count'] ?? 0));
    }
    return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
});

$total = count($posts);

if ($offset > 0 || $limit > 0) {
    $posts = array_slice($posts, $offset, $limit > 0 ? $limit : null);
}

foreach ($posts as &$post) {
    $post = attachPostApiFields($post, $currentUserId ?: null);
}
unset($post);

echo json_encode([
    'success' => true,
    'current_user_id' => $currentUserId ?: null,
    'user_id' => $userId,
    'total' => $total,
    'count' => count($posts),
    'sort' => $sort,
    'posts' => array_values($posts)
]);
