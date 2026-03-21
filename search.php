<?php
/**
 * MonoTalk - Поиск по топикам
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$query = trim($_GET['q'] ?? '');
$posts = $query !== '' ? searchPosts($query) : [];
$categories = getCategories();
$excerptLength = 300;

$pageTitle = $query ? t('search_title') . ': ' . $query : t('search_title');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="main-layout search-page">
    <div class="content-area">
        <div class="search-header">
            <h1><?= e(t('search_title')) ?></h1>
            <form class="search-form" action="search.php" method="get">
                <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e(t('search_placeholder')) ?>" class="search-input" required minlength="2">
                <button type="submit" class="btn-primary"><?= e(t('search_btn')) ?></button>
            </form>
        </div>

        <?php if ($query !== ''): ?>
            <p class="search-results-count"><?= e(t('search_found')) ?>: <?= count($posts) ?></p>
            <div class="posts-feed">
                <?php foreach ($posts as $i => $post): ?>
                    <?php
                    $cat = getCategoryById($post['category'] ?? '');
                    $postLiked = isLoggedIn() && hasUserLiked(getCurrentUser()['id'], 'post', (int)$post['id']);
                    $excerpt = mb_substr($post['content'], 0, $excerptLength);
                    $hasMore = mb_strlen($post['content']) > $excerptLength;
                    ?>
                    <article class="post-card-reddit" data-id="<?= (int)$post['id'] ?>">
                        <div class="post-vote-side">
                            <button class="vote-btn like-btn <?= $postLiked ? 'liked' : '' ?>" data-type="post" data-id="<?= (int)$post['id'] ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                                <span class="vote-icon">▲</span>
                            </button>
                            <span class="vote-count"><?= (int)($post['likes'] ?? 0) ?></span>
                        </div>
                        <div class="post-body">
                            <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>" class="post-link">
                                <span class="post-category-badge"><?= e($cat['emoji'] ?? '') ?> r/<?= e(catName($cat, $lang)) ?></span>
                                <h2 class="post-title"><?= e($post['title']) ?></h2>
                                <?php if (!empty($post['image'])): ?>
                                    <div class="post-image-preview-wrap">
                                        <img src="<?= e(BASE_URL . $post['image']) ?>" alt="" class="post-image-preview">
                                    </div>
                                <?php endif; ?>
                                <div class="post-excerpt"><?= nl2br(e($excerpt)) ?><?= $hasMore ? '...' : '' ?></div>
                            </a>
                            <div class="post-meta-line">
                                <?= e(t('post_published')) ?> <?php if ((int)($post['author_id'] ?? 0) > 0): $authorId = (int)($post['author_id'] ?? 0); ?><a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($post['author_name'] ?? '') ?>"><strong>u/<?= e($post['author_name'] ?? '') ?></strong><?= isUserVerifiedById($authorId) ? verifiedBadge() : '' ?></a><?php else: ?><strong>u/<?= e($post['author_name'] ?? 'Anonymous') ?></strong><?php endif; ?>
                                <?= e(t('post_in')) ?> <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($post['category'] ?? '') ?>">r/<?= e(catName($cat, $lang)) ?></a>
                                · <?= e(formatDate($post['created_at'] ?? '')) ?>
                            </div>
                            <div class="post-actions-bar">
                                <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>#comments" class="action-link">
                                    💬 <?= (int)($post['comments_count'] ?? 0) ?> комментариев
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if (empty($posts)): ?>
                <div class="empty-state-reddit">
                    <p><?= e(t('search_no_results')) ?> «<?= e($query) ?>»</p>
                    <p><?= e(t('search_try_other')) ?></p>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="search-hint">
                <p>Введите поисковый запрос (минимум 2 символа) для поиска по заголовкам и содержимому постов.</p>
            </div>
        <?php endif; ?>
    </div>

    <aside class="sidebar">
        <div class="sidebar-card">
            <h3>Советы по поиску</h3>
            <ul class="search-tips">
                <li>Используйте ключевые слова</li>
                <li>Попробуйте разные формулировки</li>
                <li>Поиск идёт по заголовку и тексту</li>
            </ul>
        </div>
        <div class="sidebar-card">
            <h3><?= e(t('sidebar_categories')) ?></h3>
            <div class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($cat['id']) ?>" class="category-item">
                        <span class="cat-emoji"><?= e($cat['emoji']) ?></span>
                        <span class="cat-name">r/<?= e(catName($cat, $lang)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
