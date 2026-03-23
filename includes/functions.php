<?php
/**
 * MonoTalk - Вспомогательные функции
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/leveling.php';
require_once __DIR__ . '/trust.php';

function getDefaultSubreddits(): array {
    $createdAt = date('Y-m-d H:i:s');

    return [
        [
            'id' => 'games',
            'name' => 'Игры',
            'name_en' => 'Games',
            'emoji' => '🎮',
            'description' => 'Обсуждение игр, новинок и любимых тайтлов.',
            'created_by' => 0,
            'created_at' => $createdAt,
        ],
        [
            'id' => 'programming',
            'name' => 'Программирование',
            'name_en' => 'Programming',
            'emoji' => '💻',
            'description' => 'Код, технологии, архитектура и жизнь разработчика.',
            'created_by' => 0,
            'created_at' => $createdAt,
        ],
        [
            'id' => 'memes',
            'name' => 'Мемы',
            'name_en' => 'Memes',
            'emoji' => '😂',
            'description' => 'Шутки, мемы и всё, что помогает пережить дедлайны.',
            'created_by' => 0,
            'created_at' => $createdAt,
        ],
        [
            'id' => 'discussion',
            'name' => 'Обсуждения',
            'name_en' => 'Discussion',
            'emoji' => '💬',
            'description' => 'Свободное общение на любые темы.',
            'created_by' => 0,
            'created_at' => $createdAt,
        ],
        [
            'id' => 'news',
            'name' => 'Новости',
            'name_en' => 'News',
            'emoji' => '📰',
            'description' => 'Важные новости сообщества и мира вокруг.',
            'created_by' => 0,
            'created_at' => $createdAt,
        ],
    ];
}

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
        return attachSubredditSubscriberCounts($subs);
    }

    $legacy = readData('categories.json');
    if (empty($legacy)) {
        $defaults = getDefaultSubreddits();
        writeData('subreddits.json', $defaults);
        return attachSubredditSubscriberCounts($defaults);
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
    return attachSubredditSubscriberCounts($mapped);
}

function getSubredditSubscriberCount(string $subredditId): int {
    if ($subredditId === '') {
        return 0;
    }

    $users = readData('users.json');
    $count = 0;
    foreach ($users as $user) {
        if (in_array($subredditId, $user['subscriptions'] ?? [], true)) {
            $count++;
        }
    }

    return $count;
}

function attachSubredditSubscriberCounts(array $subreddits): array {
    foreach ($subreddits as &$subreddit) {
        $subreddit['subscribers_count'] = getSubredditSubscriberCount((string)($subreddit['id'] ?? ''));
    }
    unset($subreddit);

    return $subreddits;
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

function getPublicUserInfo(?array $user): ?array {
    if (!$user) {
        return null;
    }

    $user = ensureUserLevelData($user);
    $user = ensureUserTrustData($user);
    $progress = getLevelProgressData($user);
    $trust = getTrustData($user);

    return [
        'id' => (int)($user['id'] ?? 0),
        'username' => $user['username'] ?? '',
        'avatar' => $user['avatar'] ?? null,
        'verified' => !empty($user['verified']),
        'created_at' => $user['created_at'] ?? null,
        'subscriptions_count' => count($user['subscriptions'] ?? []),
        'role' => $user['role'] ?? 'user',
        'status' => $user['status'] ?? 'active',
        'xp' => (int)$progress['xp'],
        'level' => (int)$progress['level'],
        'level_name' => (string)$progress['level_name'],
        'next_level_xp' => $progress['next_level_xp'],
        'progress_percent' => (int)$progress['progress_percent'],
        'trust' => (int)$trust['trust'],
        'trust_status' => (string)$trust['status'],
        'trust_color' => (string)$trust['color'],
        'trust_icon' => (string)$trust['icon'],
        'is_trusted' => !empty($trust['is_trusted']),
        'needs_moderation' => !empty($trust['needs_moderation']),
    ];
}

function attachPostApiFields(array $post, ?int $currentUserId = null): array {
    $authorId = (int)($post['author_id'] ?? 0);
    $author = $authorId > 0 ? getUserById($authorId) : null;

    if ($author) {
        $post['author_name'] = $post['author_name'] ?? ($author['username'] ?? 'Anonymous');
        $post['author_avatar'] = $author['avatar'] ?? '';
        $post['author_info'] = getPublicUserInfo($author);
    } else {
        $post['author_avatar'] = '';
        $post['author_info'] = null;
        $post['author_name'] = $post['author_name'] ?? 'Anonymous';
    }

    $subreddit = getSubredditById((string)($post['category'] ?? ''));
    if ($subreddit) {
        $post['category_info'] = [
            'id' => $subreddit['id'] ?? '',
            'name' => $subreddit['name'] ?? '',
            'name_en' => $subreddit['name_en'] ?? '',
            'emoji' => $subreddit['emoji'] ?? '',
            'description' => $subreddit['description'] ?? '',
            'subscribers_count' => (int)($subreddit['subscribers_count'] ?? 0),
        ];
    }

    $post['liked_by_me'] = $currentUserId ? hasUserLiked($currentUserId, 'post', (int)($post['id'] ?? 0)) : false;

    return $post;
}

function attachCommentApiFields(array $comment, ?int $currentUserId = null): array {
    $authorId = (int)($comment['author_id'] ?? 0);
    $author = $authorId > 0 ? getUserById($authorId) : null;

    if ($author) {
        $comment['author_name'] = $comment['author_name'] ?? ($author['username'] ?? 'Anonymous');
        $comment['author_avatar'] = $author['avatar'] ?? '';
        $comment['author_info'] = getPublicUserInfo($author);
    } else {
        $comment['author_avatar'] = '';
        $comment['author_info'] = null;
        $comment['author_name'] = $comment['author_name'] ?? 'Anonymous';
    }

    $comment['liked_by_me'] = $currentUserId ? hasUserLiked($currentUserId, 'comment', (int)($comment['id'] ?? 0)) : false;

    return $comment;
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
