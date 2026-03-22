<?php
/**
 * MonoTalk - edit comment API with word-trigger moderation
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

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$commentId = (int)($input['comment_id'] ?? 0);
$content = trim($input['content'] ?? '');
$removeImage = !empty($input['remove_image']);

if (!$commentId) {
    echo json_encode(['success' => false, 'error' => 'Comment ID is required']);
    exit;
}

$comments = readData('comments.json');
$commentIndex = null;
$comment = null;

foreach ($comments as $index => $item) {
    if ((int)$item['id'] === $commentId) {
        $commentIndex = $index;
        $comment = $item;
        break;
    }
}

if (!$comment) {
    echo json_encode(['success' => false, 'error' => 'Comment not found']);
    exit;
}

$currentUser = getCurrentUser();
if ((int)($comment['author_id'] ?? 0) !== (int)$currentUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No permission to edit this comment']);
    exit;
}

$imagePath = $comment['image'] ?? '';
if ($removeImage && $imagePath !== '' && file_exists(__DIR__ . '/../' . $imagePath)) {
    unlink(__DIR__ . '/../' . $imagePath);
    $imagePath = '';
}

if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Image upload failed']);
        exit;
    }

    if (($_FILES['image']['size'] ?? 0) > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Image must be 5 MB or smaller']);
        exit;
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, GIF, WEBP are allowed']);
        exit;
    }

    if ($imagePath !== '' && file_exists(__DIR__ . '/../' . $imagePath)) {
        unlink(__DIR__ . '/../' . $imagePath);
    }

    $uploadDir = __DIR__ . '/../uploads/comments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'comment_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save image']);
        exit;
    }

    $imagePath = 'uploads/comments/' . $filename;
}

if ($content === '' && $imagePath === '') {
    echo json_encode(['success' => false, 'error' => 'Comment cannot be empty']);
    exit;
}

$moderation = moderateSubmissionOrFail($currentUser, $content, [
    'entity' => 'comment',
    'action' => 'edit',
    'comment_id' => $commentId,
    'user_id' => (int)$currentUser['id']
]);

if (!$moderation['allowed']) {
    echo json_encode([
        'success' => false,
        'error' => $moderation['reason'],
        'moderation' => $moderation
    ]);
    exit;
}

$comments[$commentIndex]['content'] = $content;
$comments[$commentIndex]['image'] = $imagePath;
$comments[$commentIndex]['edited_at'] = date('Y-m-d H:i:s');
writeData('comments.json', $comments);

echo json_encode([
    'success' => true,
    'message' => 'Comment updated',
    'moderation' => $moderation
]);
