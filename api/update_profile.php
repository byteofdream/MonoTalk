<?php
/**
 * MonoTalk - API profile update and retrieval
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST', 'PUT', 'PATCH'], true)) {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => t('profile_error_auth')]);
    exit;
}

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => t('profile_error_auth')]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'success' => true,
        'user' => getPublicUserInfo($user),
    ]);
    exit;
}

$rawPayload = json_decode(file_get_contents('php://input'), true);
$input = $_POST;
if (in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true) && is_array($rawPayload)) {
    $input = $rawPayload;
}

$userId = (int)$user['id'];
$avatar = $user['avatar'] ?? '';
$bio = sanitizeProfileMultiline($input['bio'] ?? '', 300);
$websiteInput = sanitizeProfileText($input['website'] ?? '', 255);
$githubInput = sanitizeProfileText($input['github'] ?? '', 255);
$telegramInput = sanitizeProfileText($input['telegram'] ?? '', 255);
$discordInput = sanitizeProfileText($input['discord'] ?? '', 255);
$otherLinksRaw = sanitizeProfileMultiline($input['other_links'] ?? '', 4000);

if ($websiteInput !== '' && normalizeExternalUrl($websiteInput) === '') {
    echo json_encode(['success' => false, 'error' => t('profile_error_website')]);
    exit;
}

if ($githubInput !== '' && normalizeExternalUrl($githubInput) === '') {
    echo json_encode(['success' => false, 'error' => t('profile_error_github')]);
    exit;
}

if ($telegramInput !== '' && buildTelegramLink($telegramInput) === null) {
    echo json_encode(['success' => false, 'error' => t('profile_error_telegram')]);
    exit;
}

if ($discordInput !== '' && buildDiscordLink($discordInput) === null) {
    echo json_encode(['success' => false, 'error' => t('profile_error_discord')]);
    exit;
}

$otherLinks = normalizeOtherLinks($otherLinksRaw);
if ($otherLinksRaw !== '' && empty($otherLinks)) {
    echo json_encode(['success' => false, 'error' => t('profile_error_other_links')]);
    exit;
}

if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/avatars/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['avatar']['name'] ?? '', PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
        $filename = 'avatar_' . $userId . '_' . time() . '.' . $ext;
        $path = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $path)) {
            $avatar = 'uploads/avatars/' . $filename;
        }
    }
} elseif (!empty(trim($input['avatar_url'] ?? ''))) {
    $avatarUrl = normalizeExternalUrl($input['avatar_url'] ?? '');
    if ($avatarUrl === '') {
        echo json_encode(['success' => false, 'error' => t('profile_error_avatar')]);
        exit;
    }

    $avatar = $avatarUrl;
}

$users = readData('users.json');
foreach ($users as &$storedUser) {
    if ((int)($storedUser['id'] ?? 0) !== $userId) {
        continue;
    }

    $storedUser['avatar'] = $avatar;
    $storedUser['bio'] = $bio;
    $storedUser['website'] = normalizeExternalUrl($websiteInput);
    $storedUser['github'] = normalizeExternalUrl($githubInput);
    $storedUser['telegram'] = $telegramInput;
    $storedUser['discord'] = $discordInput;
    $storedUser['other_links'] = $otherLinks;
    break;
}
unset($storedUser);

writeData('users.json', $users);

$updatedUser = getCurrentUser(false);

echo json_encode([
    'success' => true,
    'redirect' => BASE_URL . 'profile.php',
    'user' => $updatedUser ? getPublicUserInfo($updatedUser) : null,
]);
