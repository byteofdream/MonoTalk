<?php
/**
 * MonoTalk - API получения комментариев поста
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

$comments = getCommentsByPostId($postId);
$currentUserId = isLoggedIn() ? (int)((getCurrentUser()['id'] ?? 0)) : 0;
foreach ($comments as &$comment) {
    $comment = attachCommentApiFields($comment, $currentUserId ?: null);
}
unset($comment);

echo json_encode([
    'success' => true,
    'current_user_id' => $currentUserId ?: null,
    'post_id' => $postId,
    'count' => count($comments),
    'comments' => array_values($comments)
]);
