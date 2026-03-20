<?php
/**
 * MonoTalk - Страница приветствия после регистрации
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

// Редирект если не залогинен
if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$user = getCurrentUser();

// Отмечаем что welcome просмотрен
if (!($user['seen_welcome'] ?? false)) {
    $users = readData('users.json');
    foreach ($users as &$u) {
        if ((int)$u['id'] === (int)$user['id']) {
            $u['seen_welcome'] = true;
            break;
        }
    }
    writeData('users.json', $users);
}

$lang = getLang();
$pageTitle = t('welcome_title');
$popularPosts = getPosts('', 'popular');
$popularPosts = array_slice($popularPosts, 0, 5);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container welcome-page">
    <div class="welcome-card">
        <h1><?= e(t('welcome_hi')) ?>, <?= e($user['username']) ?><?php if (isUserVerifiedById((int)($user['id'] ?? 0))): ?><?= verifiedBadge() ?><?php endif; ?>! 👋</h1>
        <p><?= e(t('welcome_text1')) ?></p>
        <p><?= e(t('welcome_text2')) ?></p>
        <a href="<?= e(BASE_URL) ?>index.php" class="btn-primary"><?= e(t('welcome_home')) ?></a>
        <a href="<?= e(BASE_URL) ?>create.php" class="btn-secondary"><?= e(t('nav_create')) ?></a>
    </div>

    <section class="popular-posts">
        <h2><?= e(t('popular_posts')) ?></h2>
        <?php foreach ($popularPosts as $post): ?>
            <?php $cat = getCategoryById($post['category'] ?? ''); ?>
            <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>" class="post-card-mini">
                <span class="post-category"><?= e($cat['emoji'] ?? '') ?> <?= e(catName($cat, $lang)) ?></span>
                <h3><?= e($post['title']) ?></h3>
                <span class="post-stats">♥ <?= (int)($post['likes'] ?? 0) ?> · 💬 <?= (int)($post['comments_count'] ?? 0) ?></span>
            </a>
        <?php endforeach; ?>
        <?php if (empty($popularPosts)): ?>
            <p class="empty-state"><?= e(t('empty_welcome')) ?> <a href="<?= e(BASE_URL) ?>create.php"><?= e(t('create_first')) ?></a></p>
        <?php endif; ?>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
