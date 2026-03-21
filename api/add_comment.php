<?php
/**
 * MonoTalk - API добавления комментария
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
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

if (!checkSpamProtection('add_comment', 5)) {
    echo json_encode(['success' => false, 'error' => 'Подождите перед новым комментарием']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$postId = (int)($input['post_id'] ?? 0);
$content = trim($input['content'] ?? '');
$anonymous = !empty($input['anonymous']);
$imagePath = '';

$post = getPostById($postId);
if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Пост не найден']);
    exit;
}

if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if (($_FILES['image']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Ошибка загрузки изображения']);
        exit;
    }
    $maxSize = 5 * 1024 * 1024;
    if (($_FILES['image']['size'] ?? 0) > $maxSize) {
        echo json_encode(['success' => false, 'error' => 'Изображение должно быть не больше 5 МБ']);
        exit;
    }
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        echo json_encode(['success' => false, 'error' => 'Допустимы только JPG, PNG, GIF, WEBP']);
        exit;
    }
    $uploadDir = __DIR__ . '/../uploads/comments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'comment_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'error' => 'Не удалось сохранить изображение']);
        exit;
    }
    $imagePath = 'uploads/comments/' . $filename;
}

if (empty($content) && empty($imagePath)) {
    echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
    exit;
}

$user = getCurrentUser();
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

// Обновляем счётчик комментариев у поста
$posts = readData('posts.json');
foreach ($posts as &$p) {
    if ((int)$p['id'] === $postId) {
        $p['comments_count'] = ($p['comments_count'] ?? 0) + 1;
        break;
    }
}
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
    ]
]);
