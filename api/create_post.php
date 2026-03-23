<?php
/**
 * MonoTalk - create post API with word-trigger moderation
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/leveling.php';
require_once __DIR__ . '/../includes/trust.php';
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

if (!checkSpamProtection('create_post', 10)) {
    echo json_encode(['success' => false, 'error' => 'Please wait before creating another post']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');
$category = trim($input['category'] ?? '');
$anonymous = !empty($input['anonymous']);
$imagePath = '';

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

if ($category === '' || !getSubredditById($category)) {
    echo json_encode(['success' => false, 'error' => 'Choose a valid subreddit']);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Authorization required']);
    exit;
}
$user = ensureUserTrustData($user);
$trustData = getTrustData($user);

$subscriptions = $user['subscriptions'] ?? [];
if (!in_array($category, $subscriptions, true)) {
    echo json_encode(['success' => false, 'error' => 'Subscribe to this subreddit before posting']);
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

$moderation = moderateSubmissionOrFail($user, trim($title . "\n\n" . $content), [
    'entity' => 'post',
    'action' => 'create',
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

$posts = readData('posts.json');
$newPost = [
    'id' => getNextId('posts.json'),
    'title' => $title,
    'content' => $content,
    'category' => $category,
    'author_id' => $anonymous ? 0 : (int)$user['id'],
    'author_name' => $anonymous ? 'Anonymous' : $user['username'],
    'anonymous' => $anonymous,
    'image' => $imagePath,
    'created_at' => date('Y-m-d H:i:s'),
    'likes' => 0,
    'comments_count' => 0,
    // Low-trust authors are flagged for moderation without changing the
    // existing publish/XP flow.
    'needs_moderation' => !empty($trustData['needs_moderation']),
    'trusted_author' => !empty($trustData['is_trusted']),
];

$posts[] = $newPost;
writeData('posts.json', $posts);

$leveling = addXPToUser((int)$user['id'], XP_REWARD_POST);

echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . 'post.php?id=' . $newPost['id'],
    'post_id' => $newPost['id'],
    'leveling' => $leveling,
    'trust' => $trustData,
    'moderation' => $moderation
]);
