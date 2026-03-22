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
$currentUserId = isLoggedIn() ? (int)((getCurrentUser()['id'] ?? 0)) : 0;
$subscriptionIds = $currentUserId ? getUserSubscriptions($currentUserId) : [];

foreach ($posts as &$post) {
    $post = attachPostApiFields($post, $currentUserId ?: null);
}
unset($post);

foreach ($subreddits as &$subreddit) {
    $subreddit['subscribed_by_me'] = in_array($subreddit['id'] ?? '', $subscriptionIds, true);
}
unset($subreddit);

echo json_encode([
    'success' => true,
    'current_user_id' => $currentUserId ?: null,
    'posts' => array_values($posts),
    'subreddits' => array_values($subreddits),
    'users' => array_values($users)
]);
