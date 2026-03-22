<?php
/**
 * MonoTalk - edit post API with word-trigger moderation
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
$postId = (int)($input['post_id'] ?? 0);
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');
$removeImage = !empty($input['remove_image']);

if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'Post ID is required']);
    exit;
}

$posts = readData('posts.json');
$postIndex = null;
$post = null;

foreach ($posts as $index => $item) {
    if ((int)$item['id'] === $postId) {
        $postIndex = $index;
        $post = $item;
        break;
    }
}

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Post not found']);
    exit;
}

$currentUser = getCurrentUser();
if ((int)($post['author_id'] ?? 0) !== (int)$currentUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'No permission to edit this post']);
    exit;
}

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

$imagePath = $post['image'] ?? '';
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

    $uploadDir = __DIR__ . '/../uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = 'post_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'error' => 'Failed to save image']);
        exit;
    }

    $imagePath = 'uploads/posts/' . $filename;
}

if ($content === '' && $imagePath === '') {
    echo json_encode(['success' => false, 'error' => 'Add text or image']);
    exit;
}

$moderation = moderateSubmissionOrFail($currentUser, trim($title . "\n\n" . $content), [
    'entity' => 'post',
    'action' => 'edit',
    'post_id' => $postId,
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

$posts[$postIndex]['title'] = $title;
$posts[$postIndex]['content'] = $content;
$posts[$postIndex]['image'] = $imagePath;
$posts[$postIndex]['edited_at'] = date('Y-m-d H:i:s');
writeData('posts.json', $posts);

echo json_encode([
    'success' => true,
    'message' => 'Post updated',
    'moderation' => $moderation
]);
