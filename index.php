<?php
/**
 * MonoTalk - Главная страница (Reddit-style)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$pageTitle = t('nav_home');
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'hot';
$posts = getPosts($category, $sort);

// Показываем подписанные сабреддиты если залогирован, иначе - все
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$subreddits = $currentUser ? getUserSubscriptionsData((int)$currentUser['id']) : getSubreddits();

$excerptLength = 500;
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="main-layout">
    <div class="content-area">
        <?php if ($flash = getFlash()): ?>
            <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endif; ?>

        <div class="feed-header">
            <div class="sort-tabs">
                <a href="?<?= http_build_query(array_filter(['category' => $category ?: null]) + ['sort' => 'hot']) ?>" class="sort-tab <?= $sort === 'hot' ? 'active' : '' ?>"><?= e(t('sort_hot')) ?></a>
                <a href="?<?= http_build_query(array_filter(['category' => $category ?: null]) + ['sort' => 'new']) ?>" class="sort-tab <?= $sort === 'new' ? 'active' : '' ?>"><?= e(t('sort_new')) ?></a>
                <a href="?<?= http_build_query(array_filter(['category' => $category ?: null]) + ['sort' => 'popular']) ?>" class="sort-tab <?= $sort === 'popular' ? 'active' : '' ?>"><?= e(t('sort_popular')) ?></a>
                <a href="?<?= http_build_query(array_filter(['category' => $category ?: null]) + ['sort' => 'discussed']) ?>" class="sort-tab <?= $sort === 'discussed' ? 'active' : '' ?>"><?= e(t('sort_discussed')) ?></a>
            </div>
        </div>

        <?php if ($category && $subred = getSubredditById($category)): ?>
            <div class="subreddit-banner">
                <div class="banner-header">
                    <div class="banner-info">
                        <h1 class="banner-title">
                            <span class="banner-emoji"><?= e($subred['emoji'] ?? '') ?></span>
                            r/<?= e(catName($subred, $lang)) ?>
                        </h1>
                        <?php if (!empty($subred['description'])): ?>
                            <p class="banner-description"><?= e($subred['description']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isLoggedIn()): ?>
                        <?php
                        $user = getCurrentUser();
                        $isSubscribed = in_array($subred['id'], $user['subscriptions'] ?? []);
                        ?>
                        <button class="btn-primary subscribe-btn"
                                data-subreddit-id="<?= e($subred['id']) ?>"
                                data-action="<?= $isSubscribed ? 'unsubscribe' : 'subscribe' ?>"
                                data-subscribe-text="<?= e(t('subscribe')) ?>"
                                data-unsubscribe-text="<?= e(t('unsubscribe')) ?>">
                            <?= $isSubscribed ? t('unsubscribe') : t('subscribe') ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="posts-feed">
            <?php foreach ($posts as $i => $post): ?>
                <?php
                $cat = getSubredditById($post['category'] ?? '');
                $postLiked = isLoggedIn() && hasUserLiked(getCurrentUser()['id'], 'post', (int)$post['id']);
                $excerpt = mb_substr($post['content'], 0, $excerptLength);
                $hasMore = mb_strlen($post['content']) > $excerptLength;
                ?>
                <article class="post-card-reddit" data-id="<?= (int)$post['id'] ?>" style="animation-delay: <?= $i * 0.03 ?>s">
                    <div class="post-vote-side">
                        <button class="vote-btn like-btn <?= $postLiked ? 'liked' : '' ?>" data-type="post" data-id="<?= (int)$post['id'] ?>" title="<?= e(t('like_title')) ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                            <span class="vote-icon">▲</span>
                        </button>
                        <span class="vote-count"><?= (int)($post['likes'] ?? 0) ?></span>
                    </div>
                    <div class="post-body">
                        <a href="?category=<?= e($cat['id']) ?>" class="post-category-badge" onclick="event.stopPropagation();"><?= e($cat['emoji'] ?? '') ?> r/<?= e(catName($cat, $lang)) ?></a>
                        <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>" class="post-link">
                            <h2 class="post-title"><?= e($post['title']) ?></h2>
                            <?php if (!empty($post['image'])): ?>
                                <div class="post-image-preview-wrap">
                                    <img src="<?= e(BASE_URL . $post['image']) ?>" alt="" class="post-image-preview">
                                </div>
                            <?php endif; ?>
                            <div class="post-excerpt"><?= nl2br(e($excerpt)) ?><?= $hasMore ? '...' : '' ?></div>
                            <?php if ($hasMore): ?>
                                <span class="read-more"><?= e(t('read_more')) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="post-meta-line">
                            <?= e(t('post_published')) ?> <?php if ((int)($post['author_id'] ?? 0) > 0): $authorId = (int)($post['author_id'] ?? 0); ?><a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($post['author_name'] ?? '') ?>"><strong>u/<?= e($post['author_name'] ?? '') ?></strong><?= isUserVerifiedById($authorId) ? verifiedBadge() : '' ?></a><?php else: ?><strong>u/<?= e($post['author_name'] ?? 'Anonymous') ?></strong><?php endif; ?>
                            <?= e(t('post_in')) ?> <a href="?category=<?= e($post['category'] ?? '') ?>">r/<?= e(catName($cat, $lang)) ?></a>
                            · <?= e(formatDate($post['created_at'] ?? '')) ?>
                        </div>
                        <div class="post-actions-bar">
                            <a href="<?= e(BASE_URL) ?>post.php?id=<?= (int)$post['id'] ?>#comments" class="action-link">
                                💬 <?= (int)($post['comments_count'] ?? 0) ?> <?= e(t('post_comments')) ?>
                            </a>
                            <span class="action-link"><?= e(t('post_share')) ?></span>
                            <span class="action-link"><?= e(t('post_save')) ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if (empty($posts)): ?>
            <div class="empty-state-reddit">
                <p><?= e(t('empty_posts')) ?></p>
                <a href="<?= e(BASE_URL) ?>create.php" class="btn-primary"><?= e(t('btn_create_first')) ?></a>
            </div>
        <?php endif; ?>
    </div>

    <aside class="sidebar">
        <div class="sidebar-card">
            <div class="sidebar-header">
                <h3>r/MonoTalk</h3>
                <p><?= e(t('sidebar_welcome')) ?></p>
            </div>
            <?php if (isLoggedIn()): ?>
                <a href="<?= e(BASE_URL) ?>create.php" class="btn-primary btn-block sidebar-btn"><?= e(t('nav_create')) ?></a>
                <a href="<?= e(BASE_URL) ?>create_subreddit.php" class="btn-secondary btn-block sidebar-btn sidebar-subreddit-btn"><?= e(t('nav_create_subreddit')) ?></a>
            <?php else: ?>
                <a href="<?= e(BASE_URL) ?>register.php" class="btn-primary btn-block sidebar-btn"><?= e(t('sidebar_join')) ?></a>
            <?php endif; ?>
        </div>

        <div class="sidebar-card">
            <h3><?= e(t('sidebar_subs')) ?></h3>
            <input type="text" id="subredditSearch" class="sidebar-search-input" placeholder="<?= e(t('search') ?? 'Поиск') ?> r/...">
            <div class="category-list" id="categoryList">
                <?php foreach ($subreddits as $cat): ?>
                    <a href="?category=<?= e($cat['id']) ?>" class="category-item <?= $category === $cat['id'] ? 'active' : '' ?>" data-name="<?= e(mb_strtolower(catName($cat, $lang))) ?>">
                        <span class="cat-emoji"><?= e($cat['emoji'] ?? '') ?></span>
                        <span class="cat-name">r/<?= e(catName($cat, $lang)) ?></span>
                    </a>
                <?php endforeach; ?>
                <a href="?category=" class="category-item"><?= e(t('sidebar_reset')) ?></a>
            </div>
        </div>

        <div class="sidebar-card">
            <h3><?= e(t('sidebar_rules')) ?></h3>
            <ol class="rules-list">
                <li><?= e(t('rule1')) ?></li>
                <li><?= e(t('rule2')) ?></li>
                <li><?= e(t('rule3')) ?></li>
                <li><?= e(t('rule4')) ?></li>
            </ol>
            <a href="<?= e(BASE_URL) ?>rules.php" class="btn-rules-link"><?= e(t('btn_all_rules')) ?></a>
        </div>


        <div class="sidebar-card">
            <h3><?= e(t('support_title')) ?></h3>
            <p style="margin-bottom: 15px;"><?= e(t('support_text')) ?></p>
            <a style="width: 100%;" href="https://buymeacoffee.com/dreamybyte" class="btn-primary"><center><?= e(t('support_btn')) ?></center></a>
        </div>

        <div class="sidebar-card sidebar-footer">
            <p>MonoTalk © <?= date('Y') ?></p>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
