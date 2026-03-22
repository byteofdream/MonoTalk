<?php
/**
 * MonoTalk - subscribe/unsubscribe API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Authorization required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$subredditId = trim($input['subreddit_id'] ?? '');
$action = trim($input['action'] ?? '');

if ($subredditId === '' || !in_array($action, ['subscribe', 'unsubscribe'], true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$user = getCurrentUser();
$result = $action === 'subscribe'
    ? subscribeToSubreddit((int)$user['id'], $subredditId)
    : unsubscribeFromSubreddit((int)$user['id'], $subredditId);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Failed to update subscription']);
    exit;
}

$subreddit = getSubredditById($subredditId);
echo json_encode([
    'success' => true,
    'message' => $action === 'subscribe' ? 'Subscribed' : 'Unsubscribed',
    'action' => $action,
    'subscribed' => $action === 'subscribe',
    'subscribers_count' => (int)($subreddit['subscribers_count'] ?? 0)
]);
