<?php
/**
 * MonoTalk - Авторизация и сессии
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Запуск сессии если не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Проверка авторизации
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Миграция данных пользователя (добавление недостающих полей)
 */
function migrateUserData(array $user): array {
    $needsSave = false;
    
    // Проверяем наличие поля subscriptions
    if (!isset($user['subscriptions'])) {
        $user['subscriptions'] = [];
        $needsSave = true;
    }
    
    if (!isset($user['role']) || !in_array($user['role'], ['user', 'mod', 'admin'], true)) {
        $user['role'] = 'user';
        $needsSave = true;
    }
    
    if (!isset($user['strikes']) || !is_numeric($user['strikes'])) {
        $user['strikes'] = 0;
        $needsSave = true;
    }
    
    if (!array_key_exists('mute_until', $user)) {
        $user['mute_until'] = null;
        $needsSave = true;
    }
    
    if (!array_key_exists('banned_at', $user)) {
        $user['banned_at'] = null;
        $needsSave = true;
    }
    
    if (!isset($user['status']) || !in_array($user['status'], ['active', 'muted', 'banned'], true)) {
        $user['status'] = 'active';
        $needsSave = true;
    }
    
    // Добавьте другие поля, которые могут появиться в будущем
    // if (!isset($user['some_new_field'])) {
    //     $user['some_new_field'] = default_value;
    //     $needsSave = true;
    // }
    
    // Если были изменения - сохраняем в JSON
    if ($needsSave) {
        $users = readData('users.json');
        foreach ($users as &$u) {
            if ((int)$u['id'] === (int)$user['id']) {
                $u = $user;
                break;
            }
        }
        writeData('users.json', $users);
    }
    
    return $user;
}

/**
 * Получить текущего пользователя
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    
    $users = readData('users.json');
    foreach ($users as $user) {
        if ((int)$user['id'] === (int)$_SESSION['user_id']) {
            return migrateUserData($user);
        }
    }
    return null;
}

/**
 * Получить пользователя по ID
 */
function getUserById(int $id): ?array {
    $users = readData('users.json');
    foreach ($users as $user) {
        if ((int)$user['id'] === $id) {
            return migrateUserData($user);
        }
    }
    return null;
}

/**
 * Проверка "верифицирован" по ID
 */
function isUserVerifiedById(int $id): bool {
    $user = getUserById($id);
    return !empty($user['verified']);
}

/**
 * HTML бейдж верификации
 */
function verifiedBadge(): string {
    return '<span class="verified-badge" title="Verified" aria-label="Verified">✔</span>';
}

/**
 * Получить пользователя по username
 */
function getUserByUsername(string $username): ?array {
    $users = readData('users.json');
    foreach ($users as $user) {
        if (strtolower($user['username']) === strtolower($username)) {
            return migrateUserData($user);
        }
    }
    return null;
}

/**
 * Логин пользователя
 */
function loginUser(int $userId): void {
    // Проверяем и мигрируем данные пользователя при входе
    $user = getUserById($userId);
    if ($user) {
        migrateUserData($user);
    }
    $_SESSION['user_id'] = $userId;
}

/**
 * Выход из системы
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Требовать авторизацию (редирект если не залогинен)
 */
function requireAuth(): void {
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? '');
        header('Location: ' . BASE_URL . 'login.php?redirect=' . $redirect);
        exit;
    }
}

function getUserRole(?array $user = null): string {
    $user = $user ?? getCurrentUser();
    return $user['role'] ?? 'user';
}

function hasRole(string $requiredRole, ?array $user = null): bool {
    $levels = ['user' => 1, 'mod' => 2, 'admin' => 3];
    $currentRole = getUserRole($user);
    return ($levels[$currentRole] ?? 0) >= ($levels[$requiredRole] ?? PHP_INT_MAX);
}

function requireRole(string $requiredRole): void {
    requireAuth();
    if (!hasRole($requiredRole)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Недостаточно прав'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function isUserBanned(?array $user = null): bool {
    $user = $user ?? getCurrentUser();
    return !empty($user['banned_at']) || (($user['status'] ?? 'active') === 'banned');
}

function isUserMuted(?array $user = null): bool {
    $user = $user ?? getCurrentUser();
    if (!$user) {
        return false;
    }

    $muteUntil = $user['mute_until'] ?? null;
    if (!$muteUntil) {
        return false;
    }

    return strtotime($muteUntil) > time();
}
