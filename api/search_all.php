<?php
/**
 * MonoTalk - Универсальный поиск (посты, сабреддиты, пользователи)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST + json_decode(file_get_contents('php://input'), true) ?? []) : $_GET;
$query = trim((string)($input['query'] ?? $input['search'] ?? ''));

if ($query === '') {
    echo json_encode([
        'success' => true,
        'posts' => [],
        'subreddits' => [],
        'users' => []
    ]);
    exit;
}

$posts = searchPosts($query);
$subreddits = searchSubreddits($query);
$users = searchUsers($query);

echo json_encode([
    'success' => true,
    'posts' => array_values($posts),
    'subreddits' => array_values($subreddits),
    'users' => array_values($users)
]);
