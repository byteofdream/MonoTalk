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
require_once __DIR__ . '/includes/achievements.php';

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

// Достижения: на своём профиле догоняем разблокировки, пропущенные где-то ещё
if ($isOwnProfile) {
    syncUserAchievements($user);
    foreach (readData('users.json') as $freshUser) {
        if ((int)($freshUser['id'] ?? 0) === $userId) {
            $user = $freshUser;
            break;
        }
    }
}

// Приватность и кастомизация профиля
$hideXp = !empty($user['hide_xp']);
$hideAch = !empty($user['hide_ach']);
$hideLinks = !empty($user['hide_links']);
$profileAccent = trim((string)($user['accent'] ?? ''));
$profileBanner = trim((string)($user['banner'] ?? ''));

// Достижения для отображения
$achievementStates = ($hideAch && !$isOwnProfile) ? [] : getUserAchievementStates($user);
$equippedAchievements = array_values(array_filter($achievementStates, fn($s) => $s['equipped']));
$unlockedCount = count(array_filter($achievementStates, fn($s) => $s['unlocked']));
$achievementTotal = count($achievementStates);
$achievementsHidden = $hideAch && !$isOwnProfile;
$achMaxEquipped = getAchievementMaxEquipped();

// Посты пользователя (не анонимные)
$allPosts = readData('posts.json');
$userPosts = array_filter($allPosts, fn($p) => (int)($p['author_id'] ?? 0) === $userId);
$user = ensureUserLevelData($user);
$user = ensureUserTrustData($user);
$levelProgress = getLevelProgressData($user);
$trustData = getTrustData($user);
$levelsConfig = getLevelsConfig();
$userPosts = array_reverse(array_values($userPosts));

// Компактный виджет уровня: подпись прогресса
$xpRemaining = !empty($levelProgress['max_level'])
    ? 0
    : max(0, (int)$levelProgress['next_level_xp'] - (int)$levelProgress['xp']);
$xpTarget = $levelProgress['next_level_xp'] ?? (int)$levelProgress['xp'];
$xpLabel = number_format((int)$levelProgress['xp']) . ' / ' . number_format((int)$xpTarget);

$pageTitle = $user['username'];
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container profile-page">
    <div class="profile-card"<?= $profileAccent !== '' ? ' style="--profile-accent: ' . e($profileAccent) . '"' : '' ?>>
        <?php if ($profileBanner !== '' || $profileAccent !== ''): ?>
            <div class="profile-banner"<?php if ($profileBanner !== ''): ?> style="background-image: url('<?= e($profileBanner) ?>')"<?php endif; ?>></div>
        <?php endif; ?>
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

                <?php
                $displayName = trim((string)($user['display_name'] ?? ''));
                $pronouns = trim((string)($user['pronouns'] ?? ''));
                $location = trim((string)($user['location'] ?? ''));
                $statusEmoji = trim((string)($user['status_emoji'] ?? ''));
                $statusText = trim((string)($user['status_text'] ?? ''));

                $metaParts = [];
                if ($displayName !== '') {
                    $metaParts[] = '<span class="profile-display-name">' . e($displayName) . '</span>';
                }
                if ($pronouns !== '') {
                    $metaParts[] = '<span class="profile-meta-item">' . e($pronouns) . '</span>';
                }
                if ($location !== '') {
                    $metaParts[] = '<span class="profile-meta-item">📍 ' . e($location) . '</span>';
                }
                ?>
                <?php if (!empty($metaParts)): ?>
                    <p class="profile-meta"><?= implode('<span class="profile-meta-sep">·</span>', $metaParts) ?></p>
                <?php endif; ?>

                <p class="profile-status-line">
                    <?php if ($statusEmoji !== '' || $statusText !== ''): ?>
                        <span class="profile-status-chip"><?= e(trim($statusEmoji . ' ' . $statusText)) ?></span>
                    <?php endif; ?>
                    <?= renderUserStatusBadgeById($userId, 'user-status-badge user-status-badge-lg') ?>
                </p>

                <p class="profile-trust-line">
                    <span class="trust-badge <?= e($trustData['badge_class']) ?>">
                        <span class="trust-badge-icon"><?= e($trustData['icon']) ?></span>
                        <span><?= e($trustData['status']) ?> (<?= (int)$trustData['trust'] ?>%)</span>
                    </span>
                    <?php if (!empty($trustData['is_trusted'])): ?>
                        <span class="trust-inline-note"><?= e(t('trust_note_trusted')) ?></span>
                    <?php elseif (!empty($trustData['needs_moderation'])): ?>
                        <span class="trust-inline-note"><?= e(t('trust_note_moderation')) ?></span>
                    <?php endif; ?>
                </p>

                <?php $profileLinks = buildUserProfileLinks($user); ?>
                <?php if ($isOwnProfile): ?>
                    <p class="profile-badge"><?= e(t('profile_badge')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$hideXp): ?>
        <section class="level-card" id="profileLevelCard" data-leveling='<?= e(json_encode($levelProgress, JSON_UNESCAPED_UNICODE)) ?>'
                 data-note-max="<?= e(t('level_note_max')) ?>"
                 data-note-next="<?= e(t('level_note_next')) ?>">
            <div class="level-card-row">
                <span class="level-pill" data-level-value><?= (int)$levelProgress['level'] ?></span>
                <div class="level-card-copy">
                    <div class="level-card-top">
                        <span class="level-card-name" data-level-name><?= e($levelProgress['level_name']) ?></span>
                        <span class="level-card-xp" data-level-progress-text><?= e($xpLabel) ?></span>
                    </div>
                    <div class="level-bar">
                        <span class="level-bar-fill" data-level-progress-bar style="width: <?= (int)$levelProgress['progress_percent'] ?>%"></span>
                    </div>
                </div>
            </div>
            <p class="level-progress-note" data-level-note><?= e($levelProgress['max_level'] ? t('level_note_max') : str_replace('%d', number_format($xpRemaining), t('level_note_next'))) ?></p>
        </section>
        <?php endif; ?>

    </div>

    <?php if (!empty($user['bio'])): ?>
    <section class="profile-bio-card sidebar-card">
        <h2><?= e(t('profile_bio')) ?></h2>
        <p class="profile-bio profile-bio-panel"><?= nl2br(e($user['bio'])) ?></p>
    </section>
    <?php endif; ?>

    <?php if (!empty($profileLinks) && !$hideLinks): ?>
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

    <?php if ($achievementsHidden): ?>
    <section class="ach-section sidebar-card">
        <h2><?= e(t('ach_title')) ?></h2>
        <p class="empty-state"><?= e(t('ach_hidden')) ?></p>
    </section>
    <?php elseif (!$hideAch): ?>
    <section class="ach-section sidebar-card" id="profileAchievements"
             data-max-equipped="<?= (int)$achMaxEquipped ?>"
             data-msg-pin="<?= e(t('ach_pinned_toast')) ?>"
             data-msg-unpin="<?= e(t('ach_unpinned_toast')) ?>"
             data-msg-limit="<?= e(str_replace('%d', (string)$achMaxEquipped, t('ach_error_limit'))) ?>"
             data-title-pin="<?= e(t('ach_pin')) ?>"
             data-title-unpin="<?= e(t('ach_unpin')) ?>"
             data-empty-showcase="<?= e(t('ach_showcase_empty')) ?>">
        <div class="ach-head">
            <h2><?= e(t('ach_title')) ?></h2>
            <div class="ach-head-actions">
                <span class="ach-count"><?= e(str_replace(['%1', '%2'], [(string)$unlockedCount, (string)$achievementTotal], t('ach_count'))) ?></span>
                <button type="button" class="ach-expand-btn" data-ach-expand aria-expanded="false"
                        data-label-open="<?= e(t('ach_show_all')) ?>"
                        data-label-close="<?= e(t('ach_hide_all')) ?>">
                    <span class="ach-expand-icon" aria-hidden="true">▾</span>
                    <span class="ach-expand-label" data-ach-expand-label><?= e(t('ach_show_all')) ?></span>
                </button>
            </div>
        </div>

        <div class="ach-showcase">
            <p class="ach-showcase-label"><?= e(t('ach_showcase')) ?></p>
            <div class="ach-showcase-list" data-ach-showcase>
                <?php if (empty($equippedAchievements)): ?>
                    <p class="ach-showcase-empty" data-ach-showcase-empty><?= e($isOwnProfile ? t('ach_showcase_empty') : '—') ?></p>
                <?php else: ?>
                    <?php foreach ($equippedAchievements as $state): ?>
                        <div class="ach-chip ach-chip--equipped" data-ach-chip="<?= e($state['id']) ?>">
                            <span class="ach-chip-icon"><?= e($state['icon']) ?></span>
                            <span class="ach-chip-title"><?= e($state['title']) ?></span>
                            <?php if ($isOwnProfile): ?>
                                <button type="button" class="ach-pin-btn is-active" data-ach-toggle="<?= e($state['id']) ?>" title="<?= e(t('ach_unpin')) ?>">📌</button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php if ($isOwnProfile): ?>
                <p class="ach-showcase-hint"><?= e(str_replace('%d', (string)$achMaxEquipped, t('ach_showcase_hint'))) ?></p>
            <?php endif; ?>
        </div>

        <div class="ach-panel" data-ach-panel>
            <div class="ach-grid">
                <?php foreach ($achievementStates as $state): ?>
                    <div class="ach-card<?= $state['unlocked'] ? '' : ' is-locked' ?><?= $state['equipped'] ? ' is-equipped' : '' ?>"
                         data-ach-card="<?= e($state['id']) ?>">
                        <span class="ach-icon"><?= e($state['icon']) ?></span>
                        <div class="ach-body">
                            <strong class="ach-title"><?= e($state['title']) ?></strong>
                            <span class="ach-desc"><?= e($state['desc']) ?></span>
                            <?php if ($state['show_progress']): ?>
                                <span class="ach-progress">
                                    <span class="ach-progress-bar" style="width: <?= (int)$state['percent'] ?>%"></span>
                                </span>
                                <span class="ach-progress-text"><?= e(str_replace(['%1', '%2'], [(string)$state['current'], (string)$state['target']], t('ach_progress'))) ?></span>
                            <?php elseif (!$state['unlocked']): ?>
                                <span class="ach-progress-text ach-locked-text"><?= e(t('ach_locked')) ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isOwnProfile && $state['unlocked']): ?>
                            <button type="button" class="ach-pin-btn<?= $state['equipped'] ? ' is-active' : '' ?>"
                                    data-ach-toggle="<?= e($state['id']) ?>"
                                    aria-pressed="<?= $state['equipped'] ? 'true' : 'false' ?>"
                                    title="<?= e($state['equipped'] ? t('ach_unpin') : t('ach_pin')) ?>">📌</button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if (empty($achievementStates)): ?>
                <p class="empty-state"><?= e(t('ach_empty')) ?></p>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($isOwnProfile): $favPosts = getUserFavoritePosts((int)$user['id']); ?>
    <section class="user-posts">
        <h2><?= $lang === 'en' ? 'Saved posts' : 'Избранные посты' ?></h2>
        <?php foreach ($favPosts as $post): ?>
            <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>" class="post-card-mini">
                <h3><?= e($post['title']) ?></h3>
                <span class="post-stats">♥ <?= (int)($post['likes'] ?? 0) ?> · 💬 <?= (int)($post['comments_count'] ?? 0) ?> · 👁 <?= (int)($post['views'] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (empty($favPosts)): ?>
            <p class="empty-state"><?= $lang === 'en' ? 'No saved posts yet.' : 'Пока нет сохранённых постов.' ?></p>
        <?php endif; ?>
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
