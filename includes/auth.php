<?php
/**
 * MonoTalk - Авторизация и сессии
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/leveling.php';
require_once __DIR__ . '/trust.php';

// Запуск сессии если не запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('USER_ONLINE_TIMEOUT', 300);
define('USER_ACTIVITY_THROTTLE', 60);

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
    $levelsConfig = getLevelsConfig();
    
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

    if (!array_key_exists('last_seen', $user)) {
        $user['last_seen'] = $user['created_at'] ?? date('Y-m-d H:i:s');
        $needsSave = true;
    }

    if (!isset($user['bio']) || !is_string($user['bio'])) {
        $user['bio'] = '';
        $needsSave = true;
    }

    foreach (['website', 'github', 'telegram', 'discord'] as $profileField) {
        if (!isset($user[$profileField]) || !is_string($user[$profileField])) {
            $user[$profileField] = '';
            $needsSave = true;
        }
    }

    if (!isset($user['other_links']) || !is_array($user['other_links'])) {
        $user['other_links'] = [];
        $needsSave = true;
    }

    $normalizedTrustUser = ensureUserTrustData($user);
    if ((int)($normalizedTrustUser['trust'] ?? TRUST_DEFAULT) !== (int)($user['trust'] ?? -1)) {
        $user['trust'] = (int)$normalizedTrustUser['trust'];
        $needsSave = true;
    }

    $normalizedLevelUser = ensureUserLevelData($user, $levelsConfig);
    if ((int)($normalizedLevelUser['xp'] ?? 0) !== (int)($user['xp'] ?? -1)) {
        $user['xp'] = (int)$normalizedLevelUser['xp'];
        $needsSave = true;
    }
    if ((int)($normalizedLevelUser['level'] ?? 0) !== (int)($user['level'] ?? 0)) {
        $user['level'] = (int)$normalizedLevelUser['level'];
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

function getLastSeenTimestamp(?string $lastSeen): ?int {
    if (!$lastSeen) {
        return null;
    }

    $timestamp = strtotime($lastSeen);
    return $timestamp !== false ? $timestamp : null;
}

function updateUserLastSeen(int $userId, ?int $timestamp = null): ?string {
    if ($userId <= 0) {
        return null;
    }

    $timestamp = $timestamp ?? time();
    $formatted = date('Y-m-d H:i:s', $timestamp);
    $users = readData('users.json');
    $updated = false;

    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) === $userId) {
            $user['last_seen'] = $formatted;
            $updated = true;
            break;
        }
    }
    unset($user);

    if ($updated) {
        writeData('users.json', $users);
        return $formatted;
    }

    return null;
}

function touchUserActivity(int $userId, bool $force = false): ?string {
    if ($userId <= 0) {
        return null;
    }

    $sessionKey = 'last_seen_touch_' . $userId;
    $now = time();
    $lastTouch = isset($_SESSION[$sessionKey]) ? (int)$_SESSION[$sessionKey] : 0;

    if (!$force && $lastTouch > 0 && ($now - $lastTouch) < USER_ACTIVITY_THROTTLE) {
        $user = getUserById($userId);
        return $user['last_seen'] ?? null;
    }

    $user = getUserById($userId);
    if (!$user) {
        return null;
    }

    $lastSeenTs = getLastSeenTimestamp($user['last_seen'] ?? null);
    if (!$force && $lastSeenTs !== null && ($now - $lastSeenTs) < USER_ACTIVITY_THROTTLE) {
        $_SESSION[$sessionKey] = $now;
        return $user['last_seen'] ?? null;
    }

    $updated = updateUserLastSeen($userId, $now);
    if ($updated !== null) {
        $_SESSION[$sessionKey] = $now;
    }

    return $updated;
}

function getUserStatus(int $userId): ?array {
    $user = getUserById($userId);
    if (!$user) {
        return null;
    }

    $lastSeen = $user['last_seen'] ?? null;
    $lastSeenTs = getLastSeenTimestamp($lastSeen);
    $minutesAgo = $lastSeenTs !== null ? max(0, (int)floor((time() - $lastSeenTs) / 60)) : null;
    $isOnline = $lastSeenTs !== null && (time() - $lastSeenTs) < USER_ONLINE_TIMEOUT;

    return [
        'status' => $isOnline ? 'online' : 'offline',
        'last_seen' => $lastSeen,
        'last_seen_timestamp' => $lastSeenTs,
        'minutes_ago' => $minutesAgo,
        'is_online' => $isOnline,
    ];
}

/**
 * Получить текущего пользователя
 */
function getCurrentUser(bool $touchActivity = true): ?array {
    if (!isLoggedIn()) return null;
    
    $users = readData('users.json');
    foreach ($users as $user) {
        if ((int)$user['id'] === (int)$_SESSION['user_id']) {
            $user = migrateUserData($user);
            if ($touchActivity) {
                $lastSeen = touchUserActivity((int)$user['id']);
                if ($lastSeen !== null) {
                    $user['last_seen'] = $lastSeen;
                }
            }
            return $user;
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
    touchUserActivity($userId, true);
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
