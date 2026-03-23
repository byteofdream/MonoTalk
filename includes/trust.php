<?php
/**
 * MonoTalk - trust factor helpers
 *
 * Trust is intentionally kept separate from the XP/level system so both
 * features can evolve independently without breaking each other.
 */

require_once __DIR__ . '/db.php';

const TRUST_MIN = 0;
const TRUST_MAX = 100;
const TRUST_DEFAULT = 50;

function clampTrust(int $trust): int {
    return max(TRUST_MIN, min(TRUST_MAX, $trust));
}

function addTrust(array $user, int $amount): array {
    $updatedUser = $user;
    $updatedUser['trust'] = clampTrust((int)($user['trust'] ?? TRUST_DEFAULT) + max(0, $amount));

    return $updatedUser;
}

function removeTrust(array $user, int $amount): array {
    $updatedUser = $user;
    $updatedUser['trust'] = clampTrust((int)($user['trust'] ?? TRUST_DEFAULT) - max(0, $amount));

    return $updatedUser;
}

function getTrustStatus(int $trust): array {
    $trust = clampTrust($trust);

    if ($trust <= 30) {
        return [
            'label' => 'Suspicious',
            'color' => 'red',
            'icon' => '🔴',
            'class' => 'trust-badge-danger',
            'is_trusted' => false,
            'needs_moderation' => true,
        ];
    }

    if ($trust <= 70) {
        return [
            'label' => 'Neutral',
            'color' => 'yellow',
            'icon' => '🟡',
            'class' => 'trust-badge-warning',
            'is_trusted' => false,
            'needs_moderation' => false,
        ];
    }

    return [
        'label' => 'Trusted',
        'color' => 'green',
        'icon' => '🟢',
        'class' => 'trust-badge-success',
        'is_trusted' => true,
        'needs_moderation' => false,
    ];
}

function getTrustData(array $user): array {
    $trust = clampTrust((int)($user['trust'] ?? TRUST_DEFAULT));
    $status = getTrustStatus($trust);

    return [
        'trust' => $trust,
        'status' => $status['label'],
        'color' => $status['color'],
        'icon' => $status['icon'],
        'badge_class' => $status['class'],
        'is_trusted' => $status['is_trusted'],
        'needs_moderation' => $status['needs_moderation'],
    ];
}

function ensureUserTrustData(array $user): array {
    $user['trust'] = clampTrust((int)($user['trust'] ?? TRUST_DEFAULT));
    return $user;
}

function updateUserTrust(int $userId, int $amount, string $direction = 'add'): ?array {
    $users = readData('users.json');

    foreach ($users as &$user) {
        if ((int)($user['id'] ?? 0) !== $userId) {
            continue;
        }

        $user = ensureUserTrustData($user);
        $user = $direction === 'remove'
            ? removeTrust($user, $amount)
            : addTrust($user, $amount);

        writeData('users.json', $users);

        return [
            'user' => $user,
            'trust' => getTrustData($user),
        ];
    }
    unset($user);

    return null;
}
