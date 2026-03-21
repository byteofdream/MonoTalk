<?php
/**
 * MonoTalk - add comment API with word-trigger moderation
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

if (!checkSpamProtection('add_comment', 5)) {
    echo json_encode(['success' => false, 'error' => 'Please wait before posting another comment']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$postId = (int)($input['post_id'] ?? 0);
$content = trim($input['content'] ?? '');
$anonymous = !empty($input['anonymous']);
$imagePath = '';

$post = getPostById($postId);
if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit;
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

$user = getCurrentUser();
$moderation = moderateSubmissionOrFail($user, $content, [
    'entity' => 'comment',
    'action' => 'create',
    'post_id' => $postId,
    'user_id' => (int)$user['id']
]);

if (!$moderation['allowed']) {
    echo json_encode([
        'success' => false,
        'error' => $moderation['reason'],
        'moderation' => $moderation
    ]);
    exit;
}

$comments = readData('comments.json');
$newComment = [
    'id' => getNextId('comments.json'),
    'post_id' => $postId,
    'author_id' => $anonymous ? 0 : (int)$user['id'],
    'author_name' => $anonymous ? 'Anonymous' : $user['username'],
    'content' => $content,
    'anonymous' => $anonymous,
    'image' => $imagePath,
    'created_at' => date('Y-m-d H:i:s'),
    'likes' => 0
];

$verified = ((int)$newComment['author_id'] > 0) ? isUserVerifiedById((int)$newComment['author_id']) : false;

$comments[] = $newComment;
writeData('comments.json', $comments);

$posts = readData('posts.json');
foreach ($posts as &$item) {
    if ((int)$item['id'] === $postId) {
        $item['comments_count'] = (int)($item['comments_count'] ?? 0) + 1;
        break;
    }
}
unset($item);
writeData('posts.json', $posts);

echo json_encode([
    'success' => true,
    'comment' => [
        'id' => $newComment['id'],
        'author_name' => $newComment['author_name'],
        'content' => $newComment['content'],
        'image' => $newComment['image'],
        'created_at' => $newComment['created_at'],
        'likes' => 0,
        'verified' => $verified
    ],
    'moderation' => $moderation
]);
