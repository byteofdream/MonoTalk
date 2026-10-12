<?php
/**
 * MonoTalk - pin/unpin achievement on profile API
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/lang.php';
require_once __DIR__ . '/../includes/achievements.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => t('api_auth_required')]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$achievementId = trim((string)($input['id'] ?? $input['achievement_id'] ?? ''));
$user = getCurrentUser();

$result = toggleAchievementEquipped((int)$user['id'], $achievementId);

if (empty($result['success'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $result['error'] ?? t('ach_error_invalid')]);
    exit;
}

echo json_encode([
    'success' => true,
    'id' => $achievementId,
    'now_equipped' => !empty($result['now_equipped']),
    'equipped' => array_values($result['equipped']),
    'max_equipped' => getAchievementMaxEquipped(),
    'message' => !empty($result['now_equipped']) ? t('ach_pinned_toast') : t('ach_unpinned_toast'),
]);
