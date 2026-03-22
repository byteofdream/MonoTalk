<?php
/**
 * MonoTalk - API текущего пользователя
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

if (!isLoggedIn()) {
    echo json_encode([
        'success' => true,
        'authenticated' => false,
        'user' => null,
        'subscriptions' => [],
    ]);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    echo json_encode([
        'success' => true,
        'authenticated' => false,
        'user' => null,
        'subscriptions' => [],
    ]);
    exit;
}

$userInfo = [
    'id' => (int)($user['id'] ?? 0),
    'username' => $user['username'] ?? '',
    'email' => $user['email'] ?? '',
    'avatar' => $user['avatar'] ?? null,
    'verified' => !empty($user['verified']),
    'created_at' => $user['created_at'] ?? null,
    'subscriptions_count' => count($user['subscriptions'] ?? []),
    'subscription_ids' => array_values($user['subscriptions'] ?? []),
    'role' => $user['role'] ?? 'user',
    'status' => $user['status'] ?? 'active',
    'seen_welcome' => !empty($user['seen_welcome']),
    'posts_count' => count(array_filter(
        readData('posts.json'),
        fn($post) => (int)($post['author_id'] ?? 0) === (int)($user['id'] ?? 0)
    )),
];

echo json_encode([
    'success' => true,
    'authenticated' => true,
    'user' => $userInfo,
    'subscriptions' => array_values(getUserSubscriptionsData((int)$user['id'])),
]);
