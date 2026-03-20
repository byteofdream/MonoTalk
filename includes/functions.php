<?php
/**
 * MonoTalk - Вспомогательные функции
 */

require_once __DIR__ . '/db.php';

/**
 * Безопасный вывод текста (защита от XSS)
 */
function e(string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Редирект с сообщением
 */
function redirect(string $url, string $message = '', string $type = 'success'): void {
    if ($message) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
    }
    header('Location: ' . $url);
    exit;
}

/**
 * Получить flash сообщение
 */
function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Получить все категории
 */
function getCategories(): array {
    return readData('categories.json');
}

/**
 * Получить категорию по ID
 */
function getCategoryById(string $id): ?array {
    $categories = getCategories();
    foreach ($categories as $cat) {
        if ($cat['id'] === $id) return $cat;
    }
    return null;
}

/**
 * Получить посты с фильтрацией и сортировкой
 */
function getPosts(string $category = '', string $sort = 'new'): array {
    $posts = readData('posts.json');
    
    if ($category) {
        $posts = array_filter($posts, fn($p) => ($p['category'] ?? '') === $category);
    }
    
    usort($posts, function($a, $b) use ($sort) {
        if ($sort === 'popular') {
            return ($b['likes'] ?? 0) - ($a['likes'] ?? 0);
        }
        if ($sort === 'hot') {
            $scoreA = getPostHotScore($a);
            $scoreB = getPostHotScore($b);
            return $scoreB <=> $scoreA;
        }
        if ($sort === 'discussed') {
            $countA = (int)($a['comments_count'] ?? 0);
            $countB = (int)($b['comments_count'] ?? 0);
            return $countB - $countA;
        }
        return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
    });
    
    return array_values($posts);
}

/**
 * Hot score (Reddit-like): лайки + комментарии, с учётом времени
 */
function getPostHotScore(array $post): float {
    $likes = (int)($post['likes'] ?? 0);
    $comments = (int)($post['comments_count'] ?? 0);
    $points = $likes * 2 + $comments;
    $created = strtotime($post['created_at'] ?? 'now');
    $age = time() - $created;
    $ageHours = max(1, $age / 3600);
    return $points / pow($ageHours + 2, 1.5);
}

/**
 * Поиск постов по запросу (заголовок + содержание)
 */
function searchPosts(string $query): array {
    $query = mb_strtolower(trim($query));
    if ($query === '') return [];
    
    $posts = readData('posts.json');
    $results = [];
    foreach ($posts as $post) {
        $title = mb_strtolower($post['title'] ?? '');
        $content = mb_strtolower($post['content'] ?? '');
        if (mb_strpos($title, $query) !== false || mb_strpos($content, $query) !== false) {
            $results[] = $post;
        }
    }
    usort($results, fn($a, $b) => strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0));
    return $results;
}

/**
 * Получить пост по ID
 */
function getPostById(int $id): ?array {
    $posts = readData('posts.json');
    foreach ($posts as $post) {
        if ((int)$post['id'] === $id) return $post;
    }
    return null;
}

/**
 * Получить комментарии поста
 */
function getCommentsByPostId(int $postId): array {
    $comments = readData('comments.json');
    $result = array_filter($comments, fn($c) => (int)($c['post_id'] ?? 0) === $postId);
    
    usort($result, fn($a, $b) => strtotime($a['created_at'] ?? 0) - strtotime($b['created_at'] ?? 0));
    
    return array_values($result);
}

/**
 * Обновить счётчик лайков у поста
 */
function updatePostLikesCount(int $postId): void {
    $likes = readData('likes.json');
    $count = count(array_filter($likes, fn($l) => 
        ($l['target_type'] ?? '') === 'post' && (int)($l['target_id'] ?? 0) === $postId
    ));
    
    $posts = readData('posts.json');
    foreach ($posts as &$post) {
        if ((int)$post['id'] === $postId) {
            $post['likes'] = $count;
            break;
        }
    }
    writeData('posts.json', $posts);
}

/**
 * Обновить счётчик лайков у комментария
 */
function updateCommentLikesCount(int $commentId): void {
    $likes = readData('likes.json');
    $count = count(array_filter($likes, fn($l) => 
        ($l['target_type'] ?? '') === 'comment' && (int)($l['target_id'] ?? 0) === $commentId
    ));
    
    $comments = readData('comments.json');
    foreach ($comments as &$comment) {
        if ((int)$comment['id'] === $commentId) {
            $comment['likes'] = $count;
            break;
        }
    }
    writeData('comments.json', $comments);
}

/**
 * Проверка - лайкнул ли пользователь
 */
function hasUserLiked(int $userId, string $type, int $targetId): bool {
    $likes = readData('likes.json');
    foreach ($likes as $like) {
        if ((int)($like['user_id'] ?? 0) === $userId && 
            ($like['target_type'] ?? '') === $type && 
            (int)($like['target_id'] ?? 0) === $targetId) {
            return true;
        }
    }
    return false;
}

/**
 * Форматирование даты
 */
function formatDate(string $date): string {
    $ts = strtotime($date);
    $diff = time() - $ts;
    
    if ($diff < 60) return 'только что';
    if ($diff < 3600) return floor($diff / 60) . ' мин. назад';
    if ($diff < 86400) return floor($diff / 3600) . ' ч. назад';
    if ($diff < 604800) return floor($diff / 86400) . ' дн. назад';
    
    return date('d.m.Y H:i', $ts);
}

/**
 * Базовая защита от спама (rate limiting через сессию)
 */
function checkSpamProtection(string $action, int $cooldown = 5): bool {
    $key = 'spam_' . $action;
    if (isset($_SESSION[$key])) {
        if (time() - $_SESSION[$key] < $cooldown) {
            return false;
        }
    }
    $_SESSION[$key] = time();
    return true;
}
