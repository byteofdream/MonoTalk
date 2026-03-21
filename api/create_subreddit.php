<?php
/**
 * MonoTalk - API создания сабреддита
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

if (!checkSpamProtection('create_subreddit', 20)) {
    echo json_encode(['success' => false, 'error' => 'Подождите перед созданием нового сабреддита']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$name = trim($input['name'] ?? '');
$description = trim($input['description'] ?? '');
$emoji = trim($input['emoji'] ?? '');

if (mb_strlen($name) < 3 || mb_strlen($name) > 30) {
    echo json_encode(['success' => false, 'error' => 'Название должно быть от 3 до 30 символов']);
    exit;
}

function makeSubredditId(string $name): string {
    $id = mb_strtolower($name, 'UTF-8');
    $id = preg_replace('/\s+/u', '_', $id);
    $id = preg_replace('/[^\p{L}\p{N}_-]/u', '', $id);
    $id = trim($id, '_-');
    return $id;
}

$id = makeSubredditId($name);
if ($id === '' || mb_strlen($id) < 3 || mb_strlen($id) > 30) {
    echo json_encode(['success' => false, 'error' => 'Не удалось сформировать ID сабреддита из названия']);
    exit;
}

if ($emoji === '') {
    $emoji = '🧵';
}

if (mb_strlen($emoji) > 4) {
    echo json_encode(['success' => false, 'error' => 'Эмодзи слишком длинное']);
    exit;
}

$subs = getSubreddits();
foreach ($subs as $sub) {
    if (($sub['id'] ?? '') === $id) {
        echo json_encode(['success' => false, 'error' => 'Сабреддит с таким именем уже существует']);
        exit;
    }
    if (mb_strtolower($sub['name'] ?? '', 'UTF-8') === mb_strtolower($name, 'UTF-8')) {
        echo json_encode(['success' => false, 'error' => 'Сабреддит с таким названием уже существует']);
        exit;
    }
}

$user = getCurrentUser();
$newSub = [
    'id' => $id,
    'name' => $name,
    'emoji' => $emoji,
    'description' => $description,
    'created_by' => (int)$user['id'],
    'created_at' => date('Y-m-d H:i:s'),
];

$subs[] = $newSub;
writeData('subreddits.json', $subs);

echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . 'index.php?category=' . urlencode($id),
    'subreddit' => $newSub,
]);
