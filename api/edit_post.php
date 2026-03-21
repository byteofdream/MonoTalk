<?php
/**
 * MonoTalk - API редактирования поста
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

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$postId = (int)($input['post_id'] ?? 0);
$title = trim($input['title'] ?? '');
$content = trim($input['content'] ?? '');

if (!$postId) {
    echo json_encode(['success' => false, 'error' => 'ID поста не указан']);
    exit;
}

$posts = readData('posts.json');
$postIndex = null;
$post = null;

foreach ($posts as $idx => $p) {
    if ((int)$p['id'] === $postId) {
        $postIndex = $idx;
        $post = $p;
        break;
    }
}

if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Пост не найден']);
    exit;
}

$currentUser = getCurrentUser();

// Проверка прав доступа - только автор может редактировать пост
if ((int)($post['author_id'] ?? 0) !== (int)$currentUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'У вас нет прав для редактирования этого поста']);
    exit;
}

if (empty($title)) {
    echo json_encode(['success' => false, 'error' => 'Заполните заголовок']);
    exit;
}

$imagePath = $post['image'] ?? '';

// Обработка новой картинки
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

    // Удаляем старую картинку если была
    if (!empty($post['image']) && file_exists(__DIR__ . '/../' . $post['image'])) {
        unlink(__DIR__ . '/../' . $post['image']);
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

// Проверка, что не оба поля пусты
if (empty($content) && empty($imagePath)) {
    echo json_encode(['success' => false, 'error' => 'Добавьте текст или изображение']);
    exit;
}

// Обновляем пост
$posts[$postIndex]['title'] = $title;
$posts[$postIndex]['content'] = $content;
$posts[$postIndex]['image'] = $imagePath;
$posts[$postIndex]['edited_at'] = date('Y-m-d H:i:s');

writeData('posts.json', $posts);

echo json_encode([
    'success' => true,
    'message' => 'Пост успешно отредактирован'
]);
