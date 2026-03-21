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
 * Получить все сабреддиты
 */
function getSubreddits(): array {
    $subs = readData('subreddits.json');
    if (!empty($subs)) {
        return $subs;
    }

    $legacy = readData('categories.json');
    if (empty($legacy)) {
        return [];
    }

    $mapped = array_map(function($cat) {
        return [
            'id' => $cat['id'] ?? '',
            'name' => $cat['name'] ?? ($cat['name_en'] ?? ''),
            'name_en' => $cat['name_en'] ?? ($cat['name'] ?? ''),
            'emoji' => $cat['emoji'] ?? '',
            'description' => $cat['description'] ?? '',
            'created_by' => $cat['created_by'] ?? 0,
            'created_at' => $cat['created_at'] ?? date('Y-m-d H:i:s'),
        ];
    }, $legacy);

    writeData('subreddits.json', $mapped);
    return $mapped;
}

/**
 * Получить сабреддит по ID
 */
function getSubredditById(string $id): ?array {
    $subs = getSubreddits();
    foreach ($subs as $sub) {
        if (($sub['id'] ?? '') === $id) return $sub;
    }
    return null;
}

/**
 * Получить все категории (legacy)
 */
function getCategories(): array {
    return getSubreddits();
}

/**
 * Получить категорию по ID (legacy)
 */
function getCategoryById(string $id): ?array {
    return getSubredditById($id);
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

/**
 * Получить подписки пользователя (массив ID сабреддитов)
 */
function getUserSubscriptions(int $userId): array {
    $user = getUserById($userId);
    if (!$user) return [];
    return $user['subscriptions'] ?? [];
}

/**
 * Получить полные данные подписанных сабреддитов пользователя
 */
function getUserSubscriptionsData(int $userId): array {
    $subscriptionIds = getUserSubscriptions($userId);
    if (empty($subscriptionIds)) return [];
    
    $allSubreddits = getSubreddits();
    $result = [];
    
    foreach ($allSubreddits as $sub) {
        if (in_array($sub['id'] ?? '', $subscriptionIds, true)) {
            $result[] = $sub;
        }
    }
    
    return $result;
}

/**
 * Подписать пользователя на сабреддит
 */
function subscribeToSubreddit(int $userId, string $subredditId): bool {
    $users = readData('users.json');
    $subreddit = getSubredditById($subredditId);
    
    if (!$subreddit) return false;
    
    foreach ($users as &$user) {
        if ((int)$user['id'] === $userId) {
            if (!isset($user['subscriptions'])) {
                $user['subscriptions'] = [];
            }
            
            if (!in_array($subredditId, $user['subscriptions'], true)) {
                $user['subscriptions'][] = $subredditId;
            }
            
            writeData('users.json', $users);
            return true;
        }
    }
    
    return false;
}

/**
 * Отписать пользователя от сабреддита
 */
function unsubscribeFromSubreddit(int $userId, string $subredditId): bool {
    $users = readData('users.json');
    
    foreach ($users as &$user) {
        if ((int)$user['id'] === $userId) {
            if (isset($user['subscriptions'])) {
                $user['subscriptions'] = array_filter(
                    $user['subscriptions'],
                    fn($id) => $id !== $subredditId
                );
                $user['subscriptions'] = array_values($user['subscriptions']);
            }
            
            writeData('users.json', $users);
            return true;
        }
    }
    
    return false;
}

/**
 * Поиск сабреддитов по названию
 */
function searchSubreddits(string $query, string $lang = 'ru'): array {
    $query = mb_strtolower(trim($query));
    if (strlen($query) < 2) return [];
    
    $subreddits = getSubreddits();
    $results = [];
    
    foreach ($subreddits as $sub) {
        $nameKey = $lang === 'en' ? 'name_en' : 'name';
        $name = mb_strtolower($sub[$nameKey] ?? '');
        $description = mb_strtolower($sub['description'] ?? '');
        
        if (mb_strpos($name, $query) !== false || mb_strpos($description, $query) !== false) {
            $results[] = $sub;
        }
    }
    
    return $results;
}

/**
 * Поиск пользователей по никнейму
 */
function searchUsers(string $query): array {
    $query = mb_strtolower(trim($query));
    if (strlen($query) < 2) return [];
    
    $users = readData('users.json');
    $results = [];
    
    foreach ($users as $user) {
        $username = mb_strtolower($user['username'] ?? '');
        
        if (mb_strpos($username, $query) !== false) {
            $results[] = [
                'id' => $user['id'] ?? 0,
                'username' => $user['username'] ?? '',
                'avatar' => $user['avatar'] ?? '',
                'verified' => $user['verified'] ?? false,
                'created_at' => $user['created_at'] ?? ''
            ];
        }
    }
    
    return $results;
}
