<?php
/**
 * MonoTalk - user leveling helpers
 *
 * Keeps XP, level calculation, and progress building in one place so we can
 * reuse the same rules across APIs, profile pages, and future reward systems.
 */

require_once __DIR__ . '/db.php';

const USER_LEVELS_FILE = 'levels.json';
const XP_REWARD_POST = 40;
const XP_REWARD_COMMENT = 15;

function getDefaultLevelsConfig(): array {
    return [
        'levels' => [
            ['level' => 1, 'xp_required' => 0, 'name' => 'Newbie'],
            ['level' => 2, 'xp_required' => 60, 'name' => 'Starter'],
            ['level' => 3, 'xp_required' => 140, 'name' => 'Beginner'],
            ['level' => 4, 'xp_required' => 240, 'name' => 'Explorer'],
            ['level' => 5, 'xp_required' => 360, 'name' => 'Talker'],
            ['level' => 6, 'xp_required' => 520, 'name' => 'Contributor'],
            ['level' => 7, 'xp_required' => 720, 'name' => 'Regular'],
            ['level' => 8, 'xp_required' => 980, 'name' => 'Engaged'],
            ['level' => 9, 'xp_required' => 1300, 'name' => 'Active'],
            ['level' => 10, 'xp_required' => 1680, 'name' => 'Skilled'],
            ['level' => 11, 'xp_required' => 2120, 'name' => 'Trusted'],
            ['level' => 12, 'xp_required' => 2640, 'name' => 'Advanced'],
            ['level' => 13, 'xp_required' => 3240, 'name' => 'Veteran'],
            ['level' => 14, 'xp_required' => 3920, 'name' => 'Pro'],
            ['level' => 15, 'xp_required' => 4700, 'name' => 'Specialist'],
            ['level' => 16, 'xp_required' => 5580, 'name' => 'Elite'],
            ['level' => 17, 'xp_required' => 6560, 'name' => 'Champion'],
            ['level' => 18, 'xp_required' => 7660, 'name' => 'Master'],
            ['level' => 19, 'xp_required' => 8880, 'name' => 'Grandmaster'],
            ['level' => 20, 'xp_required' => 10240, 'name' => 'Legend'],
            ['level' => 21, 'xp_required' => 11740, 'name' => 'Mythic'],
            ['level' => 22, 'xp_required' => 13400, 'name' => 'Immortal'],
            ['level' => 23, 'xp_required' => 15240, 'name' => 'Eternal'],
            ['level' => 24, 'xp_required' => 17280, 'name' => 'Cosmic'],
            ['level' => 25, 'xp_required' => 19540, 'name' => 'Forum Titan'],
        ],
    ];
}

function getLevelsConfig(): array {
    $config = readData(USER_LEVELS_FILE);
    if (!isset($config['levels']) || !is_array($config['levels']) || empty($config['levels'])) {
        $config = getDefaultLevelsConfig();
        writeData(USER_LEVELS_FILE, $config);
    }

    $levels = [];
    foreach ($config['levels'] as $entry) {
        $levelNumber = max(1, (int)($entry['level'] ?? 0));
        $xpRequired = max(0, (int)($entry['xp_required'] ?? 0));
        $levels[] = [
            'level' => $levelNumber,
            'xp_required' => $xpRequired,
            'name' => trim((string)($entry['name'] ?? ('Level ' . $levelNumber))) ?: ('Level ' . $levelNumber),
        ];
    }

    usort($levels, function (array $a, array $b): int {
        if ($a['xp_required'] === $b['xp_required']) {
            return $a['level'] <=> $b['level'];
        }
        return $a['xp_required'] <=> $b['xp_required'];
    });

    $normalized = [];
    $seenLevels = [];
    foreach ($levels as $entry) {
        if (isset($seenLevels[$entry['level']])) {
            continue;
        }
        $seenLevels[$entry['level']] = true;
        $normalized[] = $entry;
    }

    if (empty($normalized) || (int)$normalized[0]['xp_required'] !== 0) {
        array_unshift($normalized, [
            'level' => 1,
            'xp_required' => 0,
            'name' => 'Newbie',
        ]);
    }

    return ['levels' => array_values($normalized)];
}

function calculateLevel(int $xp, array $levelsConfig): array {
    $xp = max(0, $xp);
    $levels = $levelsConfig['levels'] ?? [];
    if (empty($levels)) {
        $fallback = getDefaultLevelsConfig();
        $levels = $fallback['levels'];
    }

    $currentLevel = $levels[0];
    foreach ($levels as $level) {
        if ($xp >= (int)$level['xp_required']) {
            $currentLevel = $level;
            continue;
        }
        break;
    }

    return [
        'level' => (int)$currentLevel['level'],
        'name' => (string)$currentLevel['name'],
        'xp_required' => (int)$currentLevel['xp_required'],
    ];
}

function getLevelConfigByNumber(int $levelNumber, array $levelsConfig): ?array {
    foreach (($levelsConfig['levels'] ?? []) as $level) {
        if ((int)($level['level'] ?? 0) === $levelNumber) {
            return $level;
        }
    }

    return null;
}

function getNextLevelXP(int $currentLevel, ?array $levelsConfig = null): ?int {
    $levelsConfig = $levelsConfig ?? getLevelsConfig();
    $nextLevelNumber = $currentLevel + 1;
    $nextLevel = getLevelConfigByNumber($nextLevelNumber, $levelsConfig);

    if ($nextLevel) {
        return (int)$nextLevel['xp_required'];
    }

    return null;
}

function getLevelProgressData(array $user, ?array $levelsConfig = null): array {
    $levelsConfig = $levelsConfig ?? getLevelsConfig();
    $xp = max(0, (int)($user['xp'] ?? 0));
    $currentLevel = calculateLevel($xp, $levelsConfig);
    $nextLevelXp = getNextLevelXP((int)$currentLevel['level'], $levelsConfig);
    $currentLevelFloor = (int)$currentLevel['xp_required'];
    $progressMax = $nextLevelXp ?? $xp;
    $progressInLevel = $nextLevelXp !== null ? max(0, $xp - $currentLevelFloor) : $xp;
    $progressSpan = $nextLevelXp !== null ? max(1, $nextLevelXp - $currentLevelFloor) : max(1, $xp);
    $progressPercent = $nextLevelXp !== null
        ? (int)max(0, min(100, round(($progressInLevel / $progressSpan) * 100)))
        : 100;

    return [
        'xp' => $xp,
        'level' => (int)$currentLevel['level'],
        'level_name' => (string)$currentLevel['name'],
        'current_level_xp' => $currentLevelFloor,
        'next_level_xp' => $nextLevelXp,
        'progress_current' => $xp,
        'progress_max' => $progressMax,
        'progress_percent' => $progressPercent,
        'max_level' => $nextLevelXp === null,
    ];
}

function addXP(array $user, int $amount, ?array $levelsConfig = null): array {
    $levelsConfig = $levelsConfig ?? getLevelsConfig();
    $before = getLevelProgressData($user, $levelsConfig);

    $updatedUser = $user;
    $updatedUser['xp'] = max(0, (int)($user['xp'] ?? 0) + max(0, $amount));

    $afterLevel = calculateLevel((int)$updatedUser['xp'], $levelsConfig);
    $updatedUser['level'] = (int)$afterLevel['level'];

    $after = getLevelProgressData($updatedUser, $levelsConfig);

    return [
        'user' => $updatedUser,
        'progress' => $after,
        'level_up' => (int)$after['level'] > (int)$before['level'],
        'previous_level' => (int)$before['level'],
    ];
}

function saveUserLevelData(array $updatedUser): bool {
    $users = readData('users.json');
    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) === (int)($updatedUser['id'] ?? 0)) {
            $user['xp'] = max(0, (int)($updatedUser['xp'] ?? 0));
            $user['level'] = max(1, (int)($updatedUser['level'] ?? 1));
            writeData('users.json', $users);
            return true;
        }
    }
    unset($user);

    return false;
}

function addXPToUser(int $userId, int $amount, ?array $levelsConfig = null): ?array {
    $levelsConfig = $levelsConfig ?? getLevelsConfig();
    $users = readData('users.json');

    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) !== $userId) {
            continue;
        }

        $result = addXP($user, $amount, $levelsConfig);
        $user = array_merge($user, [
            'xp' => (int)$result['user']['xp'],
            'level' => (int)$result['user']['level'],
        ]);

        writeData('users.json', $users);

        return [
            'user' => $user,
            'progress' => $result['progress'],
            'level_up' => $result['level_up'],
            'previous_level' => $result['previous_level'],
            'added_xp' => max(0, $amount),
            'message' => $result['level_up']
                ? 'You reached level ' . (int)$result['progress']['level'] . '!'
                : null,
        ];
    }
    unset($user);

    return null;
}

function ensureUserLevelData(array $user, ?array $levelsConfig = null): array {
    $levelsConfig = $levelsConfig ?? getLevelsConfig();
    $xp = max(0, (int)($user['xp'] ?? 0));
    $levelInfo = calculateLevel($xp, $levelsConfig);

    $user['xp'] = $xp;
    $user['level'] = (int)$levelInfo['level'];

    return $user;
}
