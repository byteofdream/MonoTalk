<?php
/**
 * MonoTalk - API текущего пользователя
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/leveling.php';
require_once __DIR__ . '/../includes/trust.php';

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

$user = ensureUserLevelData($user);
$user = ensureUserTrustData($user);
$levelProgress = getLevelProgressData($user);
$trustData = getTrustData($user);

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
    'xp' => (int)$levelProgress['xp'],
    'level' => (int)$levelProgress['level'],
    'level_name' => (string)$levelProgress['level_name'],
    'next_level_xp' => $levelProgress['next_level_xp'],
    'progress_percent' => (int)$levelProgress['progress_percent'],
    'trust' => (int)$trustData['trust'],
    'trust_status' => (string)$trustData['status'],
    'trust_color' => (string)$trustData['color'],
    'trust_icon' => (string)$trustData['icon'],
    'is_trusted' => !empty($trustData['is_trusted']),
    'needs_moderation' => !empty($trustData['needs_moderation']),
    'posts_count' => count(array_filter(
        readData('posts.json'),
        fn($post) => (int)($post['author_id'] ?? 0) === (int)($user['id'] ?? 0)
    )),
];

echo json_encode([
    'success' => true,
    'authenticated' => true,
    'user' => $userInfo,
    'leveling' => $levelProgress,
    'trust_data' => $trustData,
    'subscriptions' => array_values(getUserSubscriptionsData((int)$user['id'])),
]);
