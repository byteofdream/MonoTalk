<?php
/**
 * MonoTalk - API регистрации
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/leveling.php';
require_once __DIR__ . '/../includes/trust.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$email = trim($input['email'] ?? '');

// Валидация
if (strlen($username) < 3) {
    echo json_encode(['success' => false, 'error' => 'Username минимум 3 символа']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'error' => 'Пароль минимум 6 символов']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
    echo json_encode(['success' => false, 'error' => 'Username только буквы, цифры и _']);
    exit;
}

if (getUserByUsername($username)) {
    echo json_encode(['success' => false, 'error' => 'Username уже занят']);
    exit;
}

$levelsConfig = getLevelsConfig();
$startingLevel = calculateLevel(0, $levelsConfig);

$users = readData('users.json');
$newUser = [
    'id' => getNextId('users.json'),
    'username' => $username,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'email' => $email,
    'avatar' => '',
    'created_at' => date('Y-m-d H:i:s'),
    'seen_welcome' => false,
    'verified' => false,
    'subscriptions' => [],
    'role' => 'user',
    'xp' => 0,
    'level' => (int)$startingLevel['level'],
    'trust' => TRUST_DEFAULT,
    'strikes' => 0,
    'mute_until' => null,
    'banned_at' => null,
    'status' => 'active',
    'bio' => '',
    'website' => '',
    'github' => '',
    'telegram' => '',
    'discord' => '',
    'other_links' => []
];

$users[] = $newUser;
writeData('users.json', $users);

loginUser((int)$newUser['id']);
echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . 'welcome.php',
    'user_id' => $newUser['id'],
    'presence' => getUserStatus((int)$newUser['id']),
]);
