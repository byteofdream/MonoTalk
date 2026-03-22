<?php
/**
 * MonoTalk - API получения постов
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

$category = trim((string)($input['category'] ?? ''));
$sort = trim((string)($input['sort'] ?? 'hot'));
$limit = isset($input['limit']) ? (int)$input['limit'] : 0;
$offset = isset($input['offset']) ? max(0, (int)$input['offset']) : 0;
$search = trim((string)($input['search'] ?? ''));

$allowedSorts = ['hot', 'new', 'popular', 'discussed'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'new';
}

if ($search !== '') {
    $posts = searchPosts($search);
    if ($category !== '') {
        $posts = array_filter($posts, fn($p) => ($p['category'] ?? '') === $category);
    }
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
    $posts = array_values($posts);
} else {
    $posts = getPosts($category, $sort);
}

// Прикрепляем данные авторов (аватарки) прямо к постам
$currentUserId = isLoggedIn() ? (int)((getCurrentUser()['id'] ?? 0)) : 0;
foreach ($posts as &$post) {
    $post = attachPostApiFields($post, $currentUserId ?: null);
}
unset($post);

$total = count($posts);

if ($offset > 0 || $limit > 0) {
    $posts = array_slice($posts, $offset, $limit > 0 ? $limit : null);
}

echo json_encode([
    'success' => true,
    'current_user_id' => $currentUserId ?: null,
    'total' => $total,
    'count' => count($posts),
    'category' => $category,
    'sort' => $sort,
    'posts' => array_values($posts),
]);
