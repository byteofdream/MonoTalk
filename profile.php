<?php
/**
 * MonoTalk - Профиль пользователя (свой или чужой)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
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
                <h1>u/<?= e($user['username']) ?><?php if (isUserVerifiedById($userId)): ?><?= verifiedBadge() ?><?php endif; ?></h1>
                <p class="profile-date"><?= e(t('profile_since')) ?> <?= e(date('d.m.Y', strtotime($user['created_at'] ?? 'now'))) ?></p>
                <?php if ($isOwnProfile): ?>
                    <p class="profile-badge"><?= e(t('profile_badge')) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($isOwnProfile): ?>
        <form id="profileForm" class="profile-form" enctype="multipart/form-data">
            <div class="form-group">
                <label><?= e(t('profile_avatar_url')) ?></label>
                <input type="url" name="avatar_url" placeholder="https://..." value="<?= e($user['avatar'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= e(t('profile_avatar_file')) ?></label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            <button type="submit" class="btn-primary"><?= e(t('profile_save')) ?></button>
        </form>
        <?php endif; ?>
    </div>

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
