<?php
/**
 * MonoTalk - достижения (achievements)
 *
 * Каталог достижений лежит в data/achievements.json (каждый пункт хранит
 * название и описание сразу на двух языках). Разблокированные и закреплённые
 * на профиле достижения хранятся в users.json:
 *   achievements          - список id, которые уже получены;
 *   achievements_equipped - подмножество, прикреплённое к профилю.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/leveling.php';
require_once __DIR__ . '/trust.php';

function getAchievementsConfig(): array {
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $data = readData('achievements.json');
    $achievements = [];
    foreach (($data['achievements'] ?? []) as $achievement) {
        if (!is_array($achievement) || ($achievement['id'] ?? '') === '') {
            continue;
        }
        $achievements[] = $achievement;
    }

    $config = [
        'max_equipped' => max(1, (int)($data['max_equipped'] ?? 4)),
        'achievements' => $achievements,
    ];

    return $config;
}

function getAchievementsCatalog(): array {
    return getAchievementsConfig()['achievements'];
}

function getAchievementMaxEquipped(): int {
    return getAchievementsConfig()['max_equipped'];
}

function getAchievementById(string $achievementId): ?array {
    foreach (getAchievementsCatalog() as $achievement) {
        if ((string)$achievement['id'] === $achievementId) {
            return $achievement;
        }
    }
    return null;
}

function getAchievementTitle(array $achievement): string {
    $lang = getLang();
    $key = $lang === 'en' ? 'title_en' : 'title_ru';
    return (string)($achievement[$key] ?? $achievement['title_ru'] ?? $achievement['title_en'] ?? '');
}

function getAchievementDesc(array $achievement): string {
    $lang = getLang();
    $key = $lang === 'en' ? 'desc_en' : 'desc_ru';
    return (string)($achievement[$key] ?? $achievement['desc_ru'] ?? $achievement['desc_en'] ?? '');
}

/**
 * Собирает статистику пользователя, нужную для проверки условий.
 */
function getAchievementStats(array $user): array {
    $userId = (int)($user['id'] ?? 0);

    $posts = readData('posts.json');
    $comments = readData('comments.json');
    $subreddits = readData('subreddits.json');

    $postCategory = [];
    $userPosts = [];
    foreach ($posts as $post) {
        $postCategory[(int)($post['id'] ?? 0)] = (string)($post['category'] ?? '');
        if ((int)($post['author_id'] ?? 0) === $userId) {
            $userPosts[] = $post;
        }
    }

    $userComments = [];
    foreach ($comments as $comment) {
        if ((int)($comment['author_id'] ?? 0) === $userId) {
            $userComments[] = $comment;
        }
    }

    $likesReceived = 0;
    $viewsReceived = 0;
    $postLikesMax = 0;
    $nightPosts = 0;

    foreach ($userPosts as $post) {
        $likes = (int)($post['likes'] ?? 0);
        $likesReceived += $likes;
        $postLikesMax = max($postLikesMax, $likes);
        $viewsReceived += (int)($post['views'] ?? 0);

        $createdAt = strtotime((string)($post['created_at'] ?? ''));
        if ($createdAt && (int)date('G', $createdAt) < 5) {
            $nightPosts++;
        }
    }

    $subsCommented = [];
    foreach ($userComments as $comment) {
        $likesReceived += (int)($comment['likes'] ?? 0);
        $categoryId = $postCategory[(int)($comment['post_id'] ?? 0)] ?? '';
        if ($categoryId !== '') {
            $subsCommented[$categoryId] = true;
        }
    }

    $subredditsCreated = 0;
    foreach ($subreddits as $subreddit) {
        if ((int)($subreddit['created_by'] ?? 0) === $userId) {
            $subredditsCreated++;
        }
    }

    $createdAt = strtotime((string)($user['created_at'] ?? ''));
    $ageDays = $createdAt ? max(0, (int)floor((time() - $createdAt) / 86400)) : 0;

    $levelProgress = getLevelProgressData(ensureUserLevelData($user));
    $trustData = getTrustData(ensureUserTrustData($user));

    return [
        'posts' => count($userPosts),
        'comments' => count($userComments),
        'likes_received' => $likesReceived,
        'post_likes_max' => $postLikesMax,
        'views_received' => $viewsReceived,
        'night_posts' => $nightPosts,
        'distinct_subs_commented' => count($subsCommented),
        'subreddits_created' => $subredditsCreated,
        'subscriptions' => count($user['subscriptions'] ?? []),
        'age_days' => $ageDays,
        'level' => (int)$levelProgress['level'],
        'trust' => (int)$trustData['trust'],
        'verified' => !empty($user['verified']),
    ];
}

/**
 * Проверяет одно достижение против статистики.
 *
 * @return array{unlocked: bool, current: int, target: int, percent: int}
 */
function evaluateAchievement(array $achievement, array $stats): array {
    $criteria = is_array($achievement['criteria'] ?? null) ? $achievement['criteria'] : [];
    $type = (string)($criteria['type'] ?? '');
    $target = max(1, (int)($criteria['value'] ?? 1));

    switch ($type) {
        case 'posts':
            $current = (int)$stats['posts'];
            break;
        case 'comments':
            $current = (int)$stats['comments'];
            break;
        case 'likes_received':
            $current = (int)$stats['likes_received'];
            break;
        case 'post_likes_max':
            $current = (int)$stats['post_likes_max'];
            break;
        case 'views_received':
            $current = (int)$stats['views_received'];
            break;
        case 'night_posts':
            $current = (int)$stats['night_posts'];
            break;
        case 'distinct_subs_commented':
            $current = (int)$stats['distinct_subs_commented'];
            break;
        case 'subreddits_created':
            $current = (int)$stats['subreddits_created'];
            break;
        case 'subscriptions':
            $current = (int)$stats['subscriptions'];
            break;
        case 'age_days':
            $current = (int)$stats['age_days'];
            break;
        case 'level':
            $current = (int)$stats['level'];
            break;
        case 'trust':
            $current = (int)$stats['trust'];
            break;
        case 'verified':
            $current = !empty($stats['verified']) ? 1 : 0;
            break;
        default:
            $current = 0;
    }

    $unlocked = $current >= $target;
    $shown = min($current, $target);

    return [
        'unlocked' => $unlocked,
        'current' => $shown,
        'target' => $target,
        'percent' => $target > 0 ? min(100, (int)floor($shown / $target * 100)) : 0,
    ];
}

/**
 * Проверяет каталог и разблокирует новые достижения.
 *
 * @return array Нововыполненные достижения (для уведомления/ответа API).
 */
function syncUserAchievements(?array $user = null): array {
    if ($user === null) {
        if (!function_exists('isLoggedIn') || !isLoggedIn()) {
            return [];
        }
        $user = getCurrentUser();
    }

    if (!$user || (int)($user['id'] ?? 0) <= 0) {
        return [];
    }

    $stats = getAchievementStats($user);
    $unlockedIds = array_values(array_unique(array_map('strval', $user['achievements'] ?? [])));
    $unlockedSet = array_flip($unlockedIds);

    $newly = [];
    foreach (getAchievementsCatalog() as $achievement) {
        $id = (string)$achievement['id'];
        if (isset($unlockedSet[$id])) {
            continue;
        }
        $result = evaluateAchievement($achievement, $stats);
        if ($result['unlocked']) {
            $unlockedIds[] = $id;
            $unlockedSet[$id] = true;
            $newly[] = $achievement;
        }
    }

    if (!empty($newly)) {
        $users = readData('users.json');
        foreach ($users as &$entry) {
            if ((int)($entry['id'] ?? 0) === (int)$user['id']) {
                $entry['achievements'] = $unlockedIds;
                writeData('users.json', $users);
                break;
            }
        }
        unset($entry);

        foreach ($newly as $achievement) {
            pushNotification(
                (int)$user['id'],
                'achievement',
                t('ach_unlocked_prefix') . ' ' . ($achievement['icon'] ?? '') . ' ' . getAchievementTitle($achievement),
                BASE_URL . 'profile.php?user=' . urlencode((string)$user['username'])
            );
        }
    }

    return $newly;
}

/**
 * Полный список достижений с состоянием для конкретного пользователя.
 */
function getUserAchievementStates(array $user): array {
    $stats = getAchievementStats($user);
    $unlocked = array_values(array_unique(array_map('strval', $user['achievements'] ?? [])));
    $equipped = array_values(array_unique(array_map('strval', $user['achievements_equipped'] ?? [])));

    $states = [];
    foreach (getAchievementsCatalog() as $achievement) {
        $id = (string)$achievement['id'];
        $result = evaluateAchievement($achievement, $stats);
        $isUnlocked = in_array($id, $unlocked, true);

        $states[] = [
            'id' => $id,
            'icon' => (string)($achievement['icon'] ?? '🏅'),
            'title' => getAchievementTitle($achievement),
            'desc' => getAchievementDesc($achievement),
            'unlocked' => $isUnlocked,
            'equipped' => in_array($id, $equipped, true),
            'current' => $result['current'],
            'target' => $result['target'],
            'percent' => $isUnlocked ? 100 : $result['percent'],
            'show_progress' => !$isUnlocked && $result['target'] > 1,
        ];
    }

    return $states;
}

/**
 * Прикрепляет/открепляет достижение к профилю.
 */
function toggleAchievementEquipped(int $userId, string $achievementId): array {
    $achievementId = trim($achievementId);
    if ($achievementId === '' || !getAchievementById($achievementId)) {
        return ['success' => false, 'error' => t('ach_error_invalid')];
    }

    $maxEquipped = getAchievementMaxEquipped();
    $users = readData('users.json');

    foreach ($users as &$entry) {
        if ((int)($entry['id'] ?? 0) !== $userId) {
            continue;
        }

        $unlockedIds = array_values(array_unique(array_map('strval', $entry['achievements'] ?? [])));
        if (!in_array($achievementId, $unlockedIds, true)) {
            unset($entry);
            return ['success' => false, 'error' => t('ach_error_locked')];
        }

        $equipped = array_values(array_unique(array_map('strval', $entry['achievements_equipped'] ?? [])));
        $wasEquipped = in_array($achievementId, $equipped, true);

        if ($wasEquipped) {
            $equipped = array_values(array_filter($equipped, fn($id) => $id !== $achievementId));
        } else {
            if (count($equipped) >= $maxEquipped) {
                unset($entry);
                return ['success' => false, 'error' => str_replace('%d', (string)$maxEquipped, t('ach_error_limit'))];
            }
            $equipped[] = $achievementId;
        }

        $entry['achievements_equipped'] = $equipped;
        writeData('users.json', $users);
        unset($entry);

        return [
            'success' => true,
            'equipped' => $equipped,
            'now_equipped' => !$wasEquipped,
        ];
    }
    unset($entry);

    return ['success' => false, 'error' => t('ach_error_user')];
}
