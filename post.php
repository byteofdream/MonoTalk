<?php
/**
 * MonoTalk - Страница поста (Reddit-style)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$postId = (int)($_GET['id'] ?? 0);
$post = getPostById($postId);

if (!$post) {
    header('Location: ' . BASE_URL . '404.php');
    exit;
}

$comments = getCommentsByPostId($postId);
$category = getCategoryById($post['category'] ?? '');
$postLiked = isLoggedIn() && hasUserLiked(getCurrentUser()['id'], 'post', $postId);
$pageTitle = e($post['title']);
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="main-layout post-page-layout">
    <div class="content-area post-content-area">
        <article class="post-full-reddit">
            <div class="post-vote-side post-vote-vertical">
                <button class="vote-btn like-btn <?= $postLiked ? 'liked' : '' ?>" data-type="post" data-id="<?= $postId ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                    <span class="vote-icon">▲</span>
                </button>
                <span class="vote-count"><?= (int)($post['likes'] ?? 0) ?></span>
            </div>
            <div class="post-full-body">
                <span class="post-category-badge"><?= e($category['emoji'] ?? '') ?> r/<?= e(catName($category, $lang)) ?></span>
                <h1 class="post-title"><?= e($post['title']) ?></h1>
                <div class="post-meta-line">
                    <?= e(t('post_published')) ?> <?php if ((int)($post['author_id'] ?? 0) > 0): $authorId = (int)($post['author_id'] ?? 0); ?><a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($post['author_name'] ?? '') ?>"><strong>u/<?= e($post['author_name'] ?? '') ?></strong><?= isUserVerifiedById($authorId) ? verifiedBadge() : '' ?></a><?php else: ?><strong>u/<?= e($post['author_name'] ?? 'Anonymous') ?></strong><?php endif; ?>
                    <?= e(t('post_in')) ?> <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($post['category'] ?? '') ?>">r/<?= e(catName($category, $lang)) ?></a>
                    · <?= e(formatDate($post['created_at'] ?? '')) ?>
                </div>
                <div class="post-content"><?= nl2br(e($post['content'])) ?></div>
                <?php if (!empty($post['image'])): ?>
                    <div class="post-full-image-wrap">
                        <img src="<?= e(BASE_URL . $post['image']) ?>" alt="" class="post-full-image">
                    </div>
                <?php endif; ?>
                <div class="post-actions-bar">
                    <span class="action-link">💬 <?= count($comments) ?> <?= e(t('post_comments_count')) ?></span>
                    <span class="action-link"><?= e(t('post_share')) ?></span>
                    <span class="action-link"><?= e(t('post_save')) ?></span>
                </div>
            </div>
        </article>

        <section id="comments" class="comments-section-reddit">
            <h2>Комментарии (<?= count($comments) ?>)</h2>

            <?php if (isLoggedIn()): ?>
            <form id="commentForm" class="comment-form">
                <input type="hidden" name="post_id" value="<?= $postId ?>">
                <textarea name="content" placeholder="Что вы думаете? Напишите комментарий..."></textarea>
                <div class="form-group">
                    <label><?= $lang === 'en' ? 'Image (optional)' : 'Картинка (необязательно)' ?></label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="comment-form-actions">
                    <label class="checkbox-label">
                        <input type="checkbox" name="anonymous" value="1"> Анонимно
                    </label>
                    <button type="submit" class="btn-primary">Комментировать</button>
                </div>
            </form>
            <?php else: ?>
            <p class="comment-login-prompt"><a href="<?= e(BASE_URL) ?>login.php">Войдите</a>, чтобы оставить комментарий и участвовать в обсуждении.</p>
            <?php endif; ?>

            <div class="comments-list">
                <?php foreach ($comments as $comment): ?>
                    <?php
                    $commentLiked = isLoggedIn() && hasUserLiked(getCurrentUser()['id'], 'comment', (int)$comment['id']);
                    ?>
                    <div class="comment-card-reddit" data-id="<?= (int)$comment['id'] ?>">
                        <div class="comment-vote-side">
                            <button class="vote-btn like-btn like-btn-sm <?= $commentLiked ? 'liked' : '' ?>" data-type="comment" data-id="<?= (int)$comment['id'] ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                                <span class="vote-icon">▲</span>
                            </button>
                            <span class="vote-count"><?= (int)($comment['likes'] ?? 0) ?></span>
                        </div>
                        <div class="comment-body">
                            <div class="comment-header">
                                <span class="comment-author">u/<?= e($comment['author_name'] ?? 'Anonymous') ?><?php if ((int)($comment['author_id'] ?? 0) > 0): $cAuthorId = (int)($comment['author_id'] ?? 0); ?><?= isUserVerifiedById($cAuthorId) ? verifiedBadge() : '' ?><?php endif; ?></span>
                                <span class="comment-date"><?= e(formatDate($comment['created_at'] ?? '')) ?></span>
                            </div>
                            <p class="comment-content"><?= nl2br(e($comment['content'])) ?></p>
                            <?php if (!empty($comment['image'])): ?>
                                <div class="comment-image-wrap">
                                    <img src="<?= e(BASE_URL . $comment['image']) ?>" alt="" class="comment-image">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>

    <aside class="sidebar">
        <div class="sidebar-card">
            <h3><?= e(t('post_about')) ?></h3>
            <dl class="post-stats-dl">
                <dt><?= e(t('post_author')) ?></dt>
                <dd><?php if ((int)($post['author_id'] ?? 0) > 0): $authorId = (int)($post['author_id'] ?? 0); ?><a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($post['author_name'] ?? '') ?>">u/<?= e($post['author_name'] ?? '') ?></a><?= isUserVerifiedById($authorId) ? verifiedBadge() : '' ?><?php else: ?>u/<?= e($post['author_name'] ?? 'Anonymous') ?><?php endif; ?></dd>
                <dt><?= e(t('post_category')) ?></dt>
                <dd><a href="<?= e(BASE_URL) ?>index.php?category=<?= e($post['category'] ?? '') ?>">r/<?= e(catName($category, $lang)) ?></a></dd>
                <dt><?= e(t('post_published_at')) ?></dt>
                <dd><?= e(formatDate($post['created_at'] ?? '')) ?></dd>
                <dt><?= e(t('post_likes')) ?></dt>
                <dd><?= (int)($post['likes'] ?? 0) ?></dd>
            </dl>
        </div>
    </aside>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
