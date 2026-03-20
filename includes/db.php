<?php
/**
 * MonoTalk - JSON файловое хранилище
 * Работа с данными через JSON файлы
 */

define('DATA_DIR', __DIR__ . '/../data/');

/**
 * Чтение данных из JSON файла
 */
function readData(string $file): array {
    $path = DATA_DIR . $file;
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

/**
 * Запись данных в JSON файл
 */
function writeData(string $file, array $data): bool {
    $path = DATA_DIR . $file;
    $dir = dirname($path);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) !== false;
}

/**
 * Получить следующий ID для сущности
 */
function getNextId(string $file, string $idKey = 'id'): int {
    $data = readData($file);
    if (empty($data)) return 1;
    $ids = array_column($data, $idKey);
    $ids = array_filter($ids, 'is_numeric');
    return $ids ? (max($ids) + 1) : 1;
}
