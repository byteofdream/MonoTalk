<?php
/**
 * MonoTalk - API создания поста
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

if (!checkSpamProtection('create_post', 10)) {
    echo json_encode(['success' => false, 'error' => 'Подождите перед созданием нового поста']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');
$category = trim($input['category'] ?? '');
$anonymous = !empty($input['anonymous']);
$imagePath = '';

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Заполните заголовок']);
    exit;
}

if (empty($category) || !getCategoryById($category)) {
    echo json_encode(['success' => false, 'error' => 'Выберите категорию']);
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

    $uploadDir = __DIR__ . '/../uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'post_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = $uploadDir . $filename;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
        echo json_encode(['success' => false, 'error' => 'Не удалось сохранить изображение']);
        exit;
    }
    $imagePath = 'uploads/posts/' . $filename;
}

if (empty($content) && empty($imagePath)) {
    echo json_encode(['success' => false, 'error' => 'Добавьте текст или изображение']);
    exit;
}

$user = getCurrentUser();
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
    'comments_count' => 0
];

$posts[] = $newPost;
writeData('posts.json', $posts);

echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . 'post.php?id=' . $newPost['id'],
    'post_id' => $newPost['id']
]);
