<?php
/**
 * MonoTalk - переключение режима обслуживания (только verified)
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

requireAuth();
$currentUser = getCurrentUser();
if (empty($currentUser['verified'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admins only']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
// Если передан явный статус — ставим его, иначе переключаем
if (array_key_exists('enabled', $input)) {
    $enabled = (bool)$input['enabled'];
} else {
    $enabled = !isMaintenanceEnabled();
}

setMaintenanceMode($enabled);

echo json_encode([
    'success' => true,
    'enabled' => $enabled,
]);
