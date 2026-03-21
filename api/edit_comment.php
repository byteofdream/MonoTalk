<?php
/**
 * MonoTalk - API редактирования комментария
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
$commentId = (int)($input['comment_id'] ?? 0);
$content = trim($input['content'] ?? '');

if (!$commentId) {
    echo json_encode(['success' => false, 'error' => 'ID комментария не указан']);
    exit;
}

$comments = readData('comments.json');
$commentIndex = null;
$comment = null;

foreach ($comments as $idx => $c) {
    if ((int)$c['id'] === $commentId) {
        $commentIndex = $idx;
        $comment = $c;
        break;
    }
}

if (!$comment) {
    echo json_encode(['success' => false, 'error' => 'Комментарий не найден']);
    exit;
}

$currentUser = getCurrentUser();

// Проверка прав доступа - только автор может редактировать комментарий
if ((int)($comment['author_id'] ?? 0) !== (int)$currentUser['id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'У вас нет прав для редактирования этого комментария']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'error' => 'Комментарий не может быть пустым']);
    exit;
}

$imagePath = $comment['image'] ?? '';

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
    if (!empty($comment['image']) && file_exists(__DIR__ . '/../' . $comment['image'])) {
        unlink(__DIR__ . '/../' . $comment['image']);
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

// Обновляем комментарий
$comments[$commentIndex]['content'] = $content;
$comments[$commentIndex]['image'] = $imagePath;
$comments[$commentIndex]['edited_at'] = date('Y-m-d H:i:s');

writeData('comments.json', $comments);

echo json_encode([
    'success' => true,
    'message' => 'Комментарий успешно отредактирован'
]);
