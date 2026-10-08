<?php
/**
 * MonoTalk - создание жалобы на пост/комментарий
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/moderation.php';

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

if (!checkSpamProtection('create_report', 10)) {
    echo json_encode(['success' => false, 'error' => 'Please wait before sending another report']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$type = $input['target_type'] ?? '';
$targetId = (int)($input['target_id'] ?? 0);
$reason = trim($input['reason'] ?? '');

if (!in_array($type, ['post', 'comment'], true) || $targetId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid report target']);
    exit;
}

if ($reason === '') {
    echo json_encode(['success' => false, 'error' => 'Report reason is required']);
    exit;
}

$reason = mb_substr($reason, 0, 500);

$user = getCurrentUser();

// Находим контент и его автора
if ($type === 'post') {
    $target = getPostById($targetId);
    $postId = $targetId;
} else {
    $target = null;
    foreach (readData('comments.json') as $c) {
        if ((int)($c['id'] ?? 0) === $targetId) {
            $target = $c;
            break;
        }
    }
    $postId = $target ? (int)($target['post_id'] ?? 0) : 0;
}

if (!$target) {
    echo json_encode(['success' => false, 'error' => 'Content not found']);
    exit;
}

$authorId = (int)($target['author_id'] ?? 0);
if ($authorId === (int)$user['id']) {
    echo json_encode(['success' => false, 'error' => 'You cannot report your own content']);
    exit;
}

// Повторная жалoba от того же пользователя на тот же контент
$reports = readData('reports.json');
foreach ($reports as $r) {
    if ((int)($r['reporter_id'] ?? 0) === (int)$user['id']
        && ($r['target_type'] ?? '') === $type
        && (int)($r['target_id'] ?? 0) === $targetId
        && ($r['status'] ?? 'open') === 'open') {
        echo json_encode(['success' => false, 'error' => 'You have already reported this content']);
        exit;
    }
}

$reports[] = [
    'id' => getNextId('reports.json'),
    'target_type' => $type,
    'target_id' => $targetId,
    'post_id' => $postId,
    'reporter_id' => (int)$user['id'],
    'reporter_name' => $user['username'],
    'author_id' => $authorId,
    'author_name' => $target['author_name'] ?? 'Anonymous',
    'reason' => $reason,
    'status' => 'open',
    'created_at' => date('Y-m-d H:i:s'),
];
writeData('reports.json', $reports);

logModerationAction('report_created', [
    'target_type' => $type,
    'target_id' => $targetId,
    'reporter_id' => (int)$user['id'],
    'author_id' => $authorId,
    'reason' => $reason,
]);

echo json_encode(['success' => true]);
