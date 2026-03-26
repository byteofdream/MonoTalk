<?php
/**
 * MonoTalk - Профиль пользователя (свой или чужой)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/leveling.php';
require_once __DIR__ . '/includes/trust.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();

// Определяем, чей профиль смотрим
$requestedUser = trim($_GET['user'] ?? $_GET['username'] ?? '');
$currentUser = isLoggedIn() ? getCurrentUser() : null;

if ($requestedUser !== '') {
    // Смотрим чужой профиль
    $user = getUserByUsername($requestedUser);
    if (!$user) {
        header('Location: ' . BASE_URL . '404.php');
        exit;
    }
    $isOwnProfile = false;
} else {
    // Свой профиль — нужна авторизация
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php?redirect=' . urlencode(BASE_URL . 'profile.php'));
        exit;
    }
    $user = $currentUser;
    $isOwnProfile = true;
}

$userId = (int)$user['id'];

// Посты пользователя (не анонимные)
$allPosts = readData('posts.json');
$userPosts = array_filter($allPosts, fn($p) => (int)($p['author_id'] ?? 0) === $userId);
$user = ensureUserLevelData($user);
$user = ensureUserTrustData($user);
$levelProgress = getLevelProgressData($user);
$trustData = getTrustData($user);
$levelsConfig = getLevelsConfig();
$allLevels = $levelsConfig['levels'] ?? [];
$totalLevels = count($allLevels);
$nextLevelNumber = !empty($levelProgress['max_level']) ? (int)$levelProgress['level'] : (int)$levelProgress['level'] + 1;
$nextLevelName = $levelProgress['max_level'] ? $levelProgress['level_name'] : ((getLevelConfigByNumber($nextLevelNumber, $levelsConfig)['name'] ?? ('Level ' . $nextLevelNumber)));
$xpRemaining = !empty($levelProgress['max_level']) ? 0 : max(0, (int)$levelProgress['next_level_xp'] - (int)$levelProgress['xp']);
$levelTrackPercent = $totalLevels > 1 ? (int)round((((int)$levelProgress['level'] - 1) / ($totalLevels - 1)) * 100) : 100;
$progressLabelMax = $levelProgress['next_level_xp'] ?? $levelProgress['progress_max'];
$userPosts = array_reverse(array_values($userPosts));

$pageTitle = $user['username'];
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container profile-page">
    <div class="profile-card">
        <div class="profile-header">
            <?php if (!empty($user['avatar'])): ?>
                <img src="<?= e(strpos($user['avatar'], 'http') === 0 ? $user['avatar'] : BASE_URL . $user['avatar']) ?>" alt="" class="profile-avatar">
            <?php else: ?>
                <div class="profile-avatar-placeholder"><?= e(mb_substr($user['username'], 0, 1)) ?></div>
            <?php endif; ?>
            <div class="profile-info">
                <div class="profile-title-row">
                    <h1>u/<?= e($user['username']) ?><?php if (isUserVerifiedById($userId)): ?><?= verifiedBadge() ?><?php endif; ?></h1>
                    <?php if ($isOwnProfile): ?>
                        <a href="<?= e(BASE_URL) ?>edit_profile.php" class="profile-edit-icon-btn" title="<?= e(t('profile_edit')) ?>" aria-label="<?= e(t('profile_edit')) ?>">
                            <img src="<?= e(BASE_URL) ?>assets/icons/edit.svg" alt="" class="nav-icon--light">
                            <img src="<?= e(BASE_URL) ?>assets/icons/edit-white.svg" alt="" class="nav-icon--dark">
                        </a>
                    <?php endif; ?>
                </div>
                <p class="profile-date"><?= e(t('profile_since')) ?> <?= e(date('d.m.Y', strtotime($user['created_at'] ?? 'now'))) ?></p>
                <p class="profile-status-line"><?= renderUserStatusBadgeById($userId, 'user-status-badge user-status-badge-lg') ?></p>
                <?php $profileLinks = buildUserProfileLinks($user); ?>
                <?php if ($isOwnProfile): ?>
                    <p class="profile-badge"><?= e(t('profile_badge')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <section class="level-card" id="profileLevelCard" data-leveling='<?= e(json_encode($levelProgress, JSON_UNESCAPED_UNICODE)) ?>'>
            <div class="level-card-glow"></div>
            <div class="level-card-header">
                <div class="level-card-copy">
                    <p class="level-card-label">Progression</p>
                    <h2>Level <span data-level-value><?= (int)$levelProgress['level'] ?></span></h2>
                    <p class="level-rank-line">
                        Current rank:
                        <strong data-level-name><?= e($levelProgress['level_name']) ?></strong>
                    </p>
                    <p class="trust-inline-row">
                        <span class="trust-badge <?= e($trustData['badge_class']) ?>">
                            <span class="trust-badge-icon"><?= e($trustData['icon']) ?></span>
                            <span><?= e($trustData['status']) ?> (<?= (int)$trustData['trust'] ?>%)</span>
                        </span>
                        <?php if (!empty($trustData['is_trusted'])): ?>
                            <span class="trust-inline-note">trusted user</span>
                        <?php elseif (!empty($trustData['needs_moderation'])): ?>
                            <span class="trust-inline-note">posts need moderation</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="level-badge-stack">
                    <span class="level-chip">Rank #<?= (int)$levelProgress['level'] ?></span>
                    <span class="level-chip level-chip-soft">of <?= (int)$totalLevels ?></span>
                </div>
            </div>
            <div class="level-hero">
                <div class="level-orb">
                    <span class="level-orb-value"><?= (int)$levelProgress['level'] ?></span>
                </div>
                <div class="level-hero-copy">
                    <p class="level-next-label">Next unlock</p>
                    <h3>Level <?= (int)$nextLevelNumber ?> - <?= e($nextLevelName) ?></h3>
                    <p class="level-next-text">
                        <?php if (!empty($levelProgress['max_level'])): ?>
                            You already reached the highest configured level.
                        <?php else: ?>
                            <?= (int)$xpRemaining ?> XP left to reach the next milestone.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="level-stats-grid">
                <div class="level-stat-box">
                    <span class="level-stat-label">Total XP</span>
                    <strong class="level-stat-value"><?= number_format((int)$levelProgress['xp']) ?></strong>
                </div>
                <div class="level-stat-box">
                    <span class="level-stat-label">Target XP</span>
                    <strong class="level-stat-value" data-level-progress-text><?= number_format((int)$levelProgress['xp']) ?> / <?= number_format((int)$progressLabelMax) ?></strong>
                </div>
                <div class="level-stat-box">
                    <span class="level-stat-label">Progress</span>
                    <strong class="level-stat-value"><?= (int)$levelProgress['progress_percent'] ?>%</strong>
                </div>
                <div class="level-stat-box">
                    <span class="level-stat-label">Trust factor</span>
                    <strong class="level-stat-value"><?= (int)$trustData['trust'] ?>%</strong>
                </div>
            </div>
            <div class="xp-progress-wrap">
                <div class="xp-progress-labels">
                    <span>Current</span>
                    <span>Next level</span>
                </div>
                <div class="xp-progress" aria-label="XP progress">
                    <div class="xp-progress-bar" data-level-progress-bar style="width: <?= (int)$levelProgress['progress_percent'] ?>%"></div>
                </div>
            </div>
            <div class="level-track">
                <div class="level-track-line"></div>
                <div class="level-track-fill" style="width: <?= (int)$levelTrackPercent ?>%"></div>
                <span class="level-track-start">Lv 1</span>
                <span class="level-track-current">Lv <?= (int)$levelProgress['level'] ?></span>
                <span class="level-track-end">Lv <?= (int)$totalLevels ?></span>
            </div>
            <p class="level-progress-note" data-level-note>
                <?php if (!empty($levelProgress['max_level'])): ?>
                    Max level reached
                <?php else: ?>
                    <?= (int)$xpRemaining ?> XP until next level
                <?php endif; ?>
            </p>
        </section>

    </div>

    <?php if (!empty($user['bio'])): ?>
    <section class="profile-bio-card sidebar-card">
        <h2><?= e(t('profile_bio')) ?></h2>
        <p class="profile-bio profile-bio-panel"><?= nl2br(e($user['bio'])) ?></p>
    </section>
    <?php endif; ?>

    <?php if (!empty($profileLinks)): ?>
    <section class="profile-links-panel sidebar-card">
        <h2><?= e(t('profile_links')) ?></h2>
        <div class="profile-links-list profile-links-list-panel">
            <?php foreach ($profileLinks as $link): ?>
                <a href="<?= e($link['href']) ?>" target="_blank" rel="noopener noreferrer nofollow" class="profile-link-chip profile-link-chip--<?= e($link['type']) ?>">
                    <?= e($link['label']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <section class="user-posts">
        <h2><?= $isOwnProfile ? e(t('profile_my_posts')) : e(t('profile_user_posts')) ?></h2>
        <?php foreach ($userPosts as $post): ?>
            <?php $cat = getCategoryById($post['category'] ?? ''); ?>
            <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>" class="post-card-mini">
                <span class="post-category"><?= e($cat['emoji'] ?? '') ?> <?= e(catName($cat, $lang)) ?></span>
                <h3><?= e($post['title']) ?></h3>
                <span class="post-stats">♥ <?= (int)($post['likes'] ?? 0) ?> · 💬 <?= (int)($post['comments_count'] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (empty($userPosts)): ?>
            <p class="empty-state"><?= $isOwnProfile ? e(t('profile_no_posts')) : e(t('profile_no_posts_other')) ?> <?= $isOwnProfile ? '<a href="' . e(BASE_URL) . 'create.php">' . e(t('create_first')) . '</a>' : '' ?></p>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
