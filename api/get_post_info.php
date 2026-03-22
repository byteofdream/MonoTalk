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

$currentUserId = isLoggedIn() ? (int)((getCurrentUser()['id'] ?? 0)) : 0;
$post = attachPostApiFields($post, $currentUserId ?: null);

echo json_encode([
    'success' => true,
    'current_user_id' => $currentUserId ?: null,
    'post' => $post
]);
