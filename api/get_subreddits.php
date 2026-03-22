<?php
/**
 * MonoTalk - API получения списка сабреддитов
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = $_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST + json_decode(file_get_contents('php://input'), true) ?? []) : $_GET;

$limit = isset($input['limit']) ? (int)$input['limit'] : 0;
$offset = isset($input['offset']) ? max(0, (int)$input['offset']) : 0;
$search = trim((string)($input['search'] ?? ''));

$subreddits = getSubreddits();

if ($search !== '') {
    $searchLower = mb_strtolower($search);
    $subreddits = array_filter($subreddits, function($sub) use ($searchLower) {
        $name = mb_strtolower($sub['name'] ?? '');
        $nameEn = mb_strtolower($sub['name_en'] ?? '');
        $description = mb_strtolower($sub['description'] ?? '');
        return mb_strpos($name, $searchLower) !== false ||
               mb_strpos($nameEn, $searchLower) !== false ||
               mb_strpos($description, $searchLower) !== false;
    });
    $subreddits = array_values($subreddits);
}

$total = count($subreddits);

if ($offset > 0 || $limit > 0) {
    $subreddits = array_slice($subreddits, $offset, $limit > 0 ? $limit : null);
}

echo json_encode([
    'success' => true,
    'total' => $total,
    'count' => count($subreddits),
    'subreddits' => array_values($subreddits)
]);
