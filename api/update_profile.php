<?php
/**
 * MonoTalk - API обновления профиля
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

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

$input = $_POST;
$user = getCurrentUser();
$userId = (int)$user['id'];

// Обработка аватара (загрузка файла или URL)
$avatar = $user['avatar'] ?? '';

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $path = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
            $avatar = 'uploads/avatars/' . $filename;
        }
    }
} elseif (!empty(trim($input['avatar_url'] ?? ''))) {
    $url = trim($input['avatar_url']);
    if (filter_var($url, FILTER_VALIDATE_URL)) {
        $avatar = $url;
    }
}

$users = readData('users.json');
foreach ($users as &$u) {
    if ((int)$u['id'] === $userId) {
        $u['avatar'] = $avatar;
        break;
    }
}
writeData('users.json', $users);

echo json_encode(['success' => true, 'redirect' => BASE_URL . 'profile.php']);
