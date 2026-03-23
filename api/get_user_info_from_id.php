<?php
/**
 * MonoTalk - API получения информации о пользователе по ID
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

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST + json_decode(file_get_contents('php://input'), true) ?? []) : $_GET;

$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;

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

$userInfo = getPublicUserInfo($user) ?? [];
$userInfo['leveling'] = getLevelProgressData(ensureUserLevelData($user));
$userInfo['trust_data'] = getTrustData(ensureUserTrustData($user));
$userInfo['posts_count'] = count(array_filter(
    readData('posts.json'),
    fn($post) => (int)($post['author_id'] ?? 0) === $userId
));
$currentUser = getCurrentUser();
$userInfo['is_current_user'] = $currentUser ? (int)($currentUser['id'] ?? 0) === $userId : false;

echo json_encode([
    'success' => true,
    'user' => $userInfo
]);
