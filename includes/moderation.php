<?php
/**
 * MonoTalk - word-trigger moderation and strike system
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

function getModerationWordGroups(): array {
    $groups = readData('bad_words.json');
    if (is_array($groups) && !empty($groups)) {
        return $groups;
    }

    return [
        'high' => [
            'reason' => 'Blocked: abusive, hateful, or explicit content',
            'words' => [
                'nigger',
                'faggot',
                'kill yourself',
                'i will kill',
                'rape',
                'whore',
                'сука',
                'мразь',
                'пидор',
                'пидорас',
                'убью тебя',
                'сдохни',
                'шлюха'
            ]
        ],
        'medium' => [
            'reason' => 'Blocked: toxic or spam-like language',
            'words' => [
                'fuck',
                'bitch',
                'idiot',
                'stupid',
                'debil',
                'идиот',
                'дебил',
                'тупой',
                'мудак',
                'casino',
                'betting',
                'free money',
                'giveaway',
                'telegram'
            ]
        ],
        'nsfw' => [
            'reason' => 'Blocked: NSFW content',
            'words' => [
                'porn',
                'sex',
                'nude',
                'onlyfans',
                'xxx',
                'порно',
                'секс',
                'голая'
            ]
        ]
    ];
}

function normalizeModerationText(string $text): string {
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
    return trim($text);
}

function appendJsonRecord(string $file, array $record): void {
    $rows = readData($file);
    $rows[] = $record;
    writeData($file, $rows);
}

function logModerationAction(string $action, array $payload = []): void {
    appendJsonRecord('moderation_logs.json', [
        'id' => getNextId('moderation_logs.json'),
        'action' => $action,
        'payload' => $payload,
        'created_at' => date('Y-m-d H:i:s')
    ]);
}

function getPostingRestriction(?array $user = null): ?array {
    $user = $user ?? getCurrentUser();
    if (!$user) {
        return ['allowed' => false, 'reason' => 'Authorization required', 'severity' => 'low'];
    }

    if (isUserBanned($user)) {
        return ['allowed' => false, 'reason' => 'Account is banned', 'severity' => 'high'];
    }

    if (isUserMuted($user)) {
        return [
            'allowed' => false,
            'reason' => 'Account is muted until ' . ($user['mute_until'] ?? ''),
            'severity' => 'medium'
        ];
    }

    return null;
}

function detectTriggeredWords(string $text): array {
    $normalized = normalizeModerationText($text);
    $matches = [];

    foreach (getModerationWordGroups() as $severity => $group) {
        foreach ($group['words'] as $word) {
            if (mb_strpos($normalized, normalizeModerationText($word)) !== false) {
                $matches[] = [
                    'word' => $word,
                    'severity' => $severity === 'nsfw' ? 'high' : $severity,
                    'reason' => $group['reason']
                ];
            }
        }
    }

    preg_match_all('~https?://~i', $text, $urlMatches);
    if (count($urlMatches[0]) >= 4) {
        $matches[] = [
            'word' => 'multi_url_spam',
            'severity' => 'medium',
            'reason' => 'Blocked: too many links'
        ];
    }

    if (preg_match('/(.)\1{10,}/u', $text)) {
        $matches[] = [
            'word' => 'flood_pattern',
            'severity' => 'medium',
            'reason' => 'Blocked: flood or spam pattern'
        ];
    }

    return $matches;
}

function addUserStrike(int $userId, string $reason, string $severity, array $context = []): array {
    $users = readData('users.json');
    $updatedUser = null;

    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) !== $userId) {
            continue;
        }

        $user = migrateUserData($user);
        $user['strikes'] = (int)($user['strikes'] ?? 0) + 1;
        $user['last_strike_at'] = date('Y-m-d H:i:s');

        if ($user['strikes'] >= MODERATION_BAN_STRIKE_THRESHOLD) {
            $user['status'] = 'banned';
            $user['banned_at'] = date('Y-m-d H:i:s');
        } elseif ($user['strikes'] >= MODERATION_HIGH_STRIKE_THRESHOLD) {
            $user['status'] = 'muted';
            $user['mute_until'] = date('Y-m-d H:i:s', time() + (MODERATION_MUTE_HOURS * 3600));
        }

        $updatedUser = $user;
        break;
    }
    unset($user);

    if (!$updatedUser) {
        return [];
    }

    writeData('users.json', $users);

    appendJsonRecord('strikes.json', [
        'id' => getNextId('strikes.json'),
        'user_id' => $userId,
        'reason' => $reason,
        'severity' => $severity,
        'context' => $context,
        'created_at' => date('Y-m-d H:i:s')
    ]);

    logModerationAction('strike_added', [
        'user_id' => $userId,
        'strikes' => $updatedUser['strikes'],
        'status' => $updatedUser['status'],
        'reason' => $reason,
        'severity' => $severity
    ]);

    return $updatedUser;
}

function moderateForumText(string $text, array $context = []): array {
    $trimmed = trim($text);
    if ($trimmed === '') {
        return [
            'allowed' => true,
            'reason' => 'No text to moderate',
            'severity' => 'low',
            'source' => 'word_filter'
        ];
    }

    $matches = detectTriggeredWords($trimmed);
    if (empty($matches)) {
        $result = [
            'allowed' => true,
            'reason' => 'No blocked words found',
            'severity' => 'low',
            'source' => 'word_filter',
            'matches' => []
        ];
    } else {
        $priority = ['high' => 3, 'medium' => 2, 'low' => 1];
        usort($matches, function ($a, $b) use ($priority) {
            return ($priority[$b['severity']] ?? 0) <=> ($priority[$a['severity']] ?? 0);
        });

        $topMatch = $matches[0];
        $result = [
            'allowed' => false,
            'reason' => $topMatch['reason'],
            'severity' => $topMatch['severity'],
            'source' => 'word_filter',
            'matches' => array_values(array_unique(array_column($matches, 'word')))
        ];
    }

    logModerationAction('content_checked', [
        'source' => $result['source'],
        'allowed' => $result['allowed'],
        'severity' => $result['severity'],
        'reason' => $result['reason'],
        'context' => $context,
        'matches' => $result['matches'] ?? []
    ]);

    return $result;
}

function moderateSubmissionOrFail(array $user, string $text, array $context = []): array {
    $restriction = getPostingRestriction($user);
    if ($restriction) {
        return $restriction;
    }

    $result = moderateForumText($text, $context);

    if (!$result['allowed'] && $result['severity'] === 'high') {
        $updatedUser = addUserStrike((int)$user['id'], $result['reason'], $result['severity'], $context);
        $result['strike_added'] = true;
        $result['strikes'] = (int)($updatedUser['strikes'] ?? 0);
        $result['status'] = $updatedUser['status'] ?? ($user['status'] ?? 'active');
        $result['mute_until'] = $updatedUser['mute_until'] ?? null;
        $result['banned_at'] = $updatedUser['banned_at'] ?? null;
    }

    return $result;
}
