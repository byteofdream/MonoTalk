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
$searchType = $_GET['type'] ?? 'posts'; // posts, subreddits, users
$currentUser = isLoggedIn() ? getCurrentUser() : null;

$posts = [];
$foundSubreddits = [];
$foundUsers = [];

if ($query !== '') {
    $posts = searchPosts($query);
    $foundSubreddits = searchSubreddits($query, $lang);
    $foundUsers = searchUsers($query);
}

$subreddits = getSubreddits();
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
            <!-- Search result tabs -->
            <div class="search-tabs">
                <a href="?q=<?= urlencode($query) ?>&type=posts" class="search-tab <?= $searchType === 'posts' ? 'active' : '' ?>">
                    💬 Посты (<?= count($posts) ?>)
                </a>
                <a href="?q=<?= urlencode($query) ?>&type=subreddits" class="search-tab <?= $searchType === 'subreddits' ? 'active' : '' ?>">
                    📝 Сабреддиты (<?= count($foundSubreddits) ?>)
                </a>
                <a href="?q=<?= urlencode($query) ?>&type=users" class="search-tab <?= $searchType === 'users' ? 'active' : '' ?>">
                    👤 Пользователи (<?= count($foundUsers) ?>)
                </a>
            </div>

            <!-- Posts results -->
            <?php if ($searchType === 'posts' || $searchType === 'all'): ?>
                <?php if (!empty($posts)): ?>
                    <p class="search-results-count"><?= e(t('search_found')) ?>: <?= count($posts) ?> постов</p>
                    <div class="posts-feed">
                <?php foreach ($posts as $i => $post): ?>
                    <?php
                    $cat = getSubredditById($post['category'] ?? '');
                    $postLiked = isLoggedIn() && hasUserLiked(getCurrentUser()['id'], 'post', (int)$post['id']);
                    $preview = getPostPreviewText((string)($post['content'] ?? ''), $excerptLength);
                    $excerpt = $preview['excerpt'];
                    $hasMore = !empty($preview['has_more']);
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
                <?php else: ?>
                    <div class="empty-state-reddit">
                        <p><?= e(t('search_no_results')) ?> «<?= e($query) ?>»</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Subreddits results -->
            <?php if ($searchType === 'subreddits' || $searchType === 'all'): ?>
                <?php if (!empty($foundSubreddits)): ?>
                    <p class="search-results-count">Найдено сабреддитов: <?= count($foundSubreddits) ?></p>
                    <div class="subreddits-results">
                        <?php foreach ($foundSubreddits as $sub): ?>
                            <div class="sub-card-reddit">
                                <div class="sub-header">
                                    <h3 class="sub-name">
                                        <span class="sub-emoji"><?= e($sub['emoji'] ?? '') ?></span>
                                        r/<?= e(catName($sub, $lang)) ?>
                                    </h3>
                                </div>
                                <?php if (!empty($sub['description'])): ?>
                                    <p class="sub-description"><?= e($sub['description']) ?></p>
                                <?php endif; ?>
                                <div class="sub-actions">
                                    <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($sub['id']) ?>" class="btn-secondary">Посмотреть</a>
                                    <?php if ($currentUser): ?>
                                        <button class="btn-secondary subscribe-btn" data-subreddit-id="<?= e($sub['id']) ?>" data-action="subscribe">
                                            Подписаться
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-reddit">
                        <p>Сабреддиты не найдены</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Users results -->
            <?php if ($searchType === 'users' || $searchType === 'all'): ?>
                <?php if (!empty($foundUsers)): ?>
                    <p class="search-results-count">Найдено пользователей: <?= count($foundUsers) ?></p>
                    <div class="users-results">
                        <?php foreach ($foundUsers as $user): ?>
                            <div class="user-card-reddit">
                                <div class="user-header">
                                    <?php if (!empty($user['avatar'])): ?>
                                        <img src="<?= e(BASE_URL . $user['avatar']) ?>" alt="<?= e($user['username']) ?>" class="user-avatar">
                                    <?php else: ?>
                                        <div class="user-avatar-placeholder"><?= mb_substr(e($user['username']), 0, 1) ?></div>
                                    <?php endif; ?>
                                    <div class="user-info">
                                        <h3>
                                            <a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($user['username']) ?>" class="user-link">
                                                u/<?= e($user['username']) ?>
                                                <?= $user['verified'] ? verifiedBadge() : '' ?>
                                            </a>
                                        </h3>
                                        <p class="user-joined">Присоединился <?= e(formatDate($user['created_at'])) ?></p>
                                    </div>
                                </div>
                                <div class="user-actions">
                                    <a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($user['username']) ?>" class="btn-secondary">Профиль</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-reddit">
                        <p>Пользователи не найдены</p>
                    </div>
                <?php endif; ?>
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
                <?php foreach ($subreddits as $cat): ?>
                    <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($cat['id']) ?>" class="category-item">
                        <span class="cat-emoji"><?= e($cat['emoji'] ?? '') ?></span>
                        <span class="cat-name">r/<?= e(catName($cat, $lang)) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
