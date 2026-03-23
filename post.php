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
$category = getSubredditById($post['category'] ?? '');
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$postLiked = $currentUser && hasUserLiked($currentUser['id'], 'post', $postId);
$isPostAuthor = $currentUser && (int)($post['author_id'] ?? 0) === (int)$currentUser['id'];
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
                <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($category['id']) ?>" class="post-category-badge"><?= e($category['emoji'] ?? '') ?> r/<?= e(catName($category, $lang)) ?></a>
                <h1 class="post-title"><?= e($post['title']) ?></h1>
                <div class="post-meta-line">
                    <?= e(t('post_published')) ?> <?php if ((int)($post['author_id'] ?? 0) > 0): $authorId = (int)($post['author_id'] ?? 0); ?><a href="<?= e(BASE_URL) ?>profile.php?user=<?= e($post['author_name'] ?? '') ?>"><strong>u/<?= e($post['author_name'] ?? '') ?></strong><?= isUserVerifiedById($authorId) ? verifiedBadge() : '' ?></a><?php else: ?><strong>u/<?= e($post['author_name'] ?? 'Anonymous') ?></strong><?php endif; ?>
                    <?= e(t('post_in')) ?> <a href="<?= e(BASE_URL) ?>index.php?category=<?= e($post['category'] ?? '') ?>">r/<?= e(catName($category, $lang)) ?></a>
                    · <?= e(formatDate($post['created_at'] ?? '')) ?>
                    <?php if ($isPostAuthor): ?>
                    · <button class="edit-post-btn" data-post-id="<?= $postId ?>" title="Редактировать пост">✏️ Редактировать</button>
                    <?php endif; ?>
                </div>
                <?= renderPostContentBlocks((string)($post['content'] ?? ''), (string)($post['image'] ?? ''), BASE_URL) ?>
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
                <textarea name="content" placeholder="Что вы думаете? Напишите комментарий..." class="post-textarea"></textarea>
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
                    $commentLiked = $currentUser && hasUserLiked($currentUser['id'], 'comment', (int)$comment['id']);
                    $isCommentAuthor = $currentUser && (int)($comment['author_id'] ?? 0) === (int)$currentUser['id'];
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
                                <div>
                                    <span class="comment-author">u/<?= e($comment['author_name'] ?? 'Anonymous') ?><?php if ((int)($comment['author_id'] ?? 0) > 0): $cAuthorId = (int)($comment['author_id'] ?? 0); ?><?= isUserVerifiedById($cAuthorId) ? verifiedBadge() : '' ?><?php endif; ?></span>
                                    <span class="comment-date"> · <?= e(formatDate($comment['created_at'] ?? '')) ?></span>
                                </div>
                                <?php if ($isCommentAuthor): ?>
                                <button class="edit-comment-btn" data-comment-id="<?= (int)$comment['id'] ?>" title="Редактировать комментарий">✏️</button>
                                <?php endif; ?>
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
</main>

<!-- Modal для редактирования поста -->
<div id="editPostModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Редактировать пост</h2>
            <button class="modal-close" data-close-modal="editPostModal">&times;</button>
        </div>
        <form id="editPostForm" class="post-form">
            <input type="hidden" name="post_id" id="editPostId">
            
            <div class="form-group">
                <label for="editPostTitle">Заголовок *</label>
                <input type="text" id="editPostTitle" name="title" required placeholder="Введите заголовок поста">
                <small>Форматирование: **жирный** и *курсив*.</small>
            </div>
            
            <div class="form-group">
                <label for="editPostContent">Содержание</label>
                <textarea id="editPostContent" name="content" placeholder="Содержание поста (опционально)"></textarea>
            </div>
            
            <div class="form-group">
                <label for="editPostImage">Изображение (опционально)</label>
                <input type="file" id="editPostImage" name="image" accept="image/*">
                <small>Максимум 5 МБ. Форматы: JPG, PNG, GIF, WEBP</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-modal="editPostModal">Отмена</button>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal для редактирования комментария -->
<div id="editCommentModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Редактировать комментарий</h2>
            <button class="modal-close" data-close-modal="editCommentModal">&times;</button>
        </div>
        <form id="editCommentForm" class="post-form">
            <input type="hidden" name="comment_id" id="editCommentId">
            
            <div class="form-group">
                <label for="editCommentContent">Комментарий *</label>
                <textarea id="editCommentContent" name="content" required placeholder="Содержание комментария"></textarea>
            </div>
            
            <div class="form-group">
                <label for="editCommentImage">Изображение (опционально)</label>
                <input type="file" id="editCommentImage" name="image" accept="image/*">
                <small>Максимум 5 МБ. Форматы: JPG, PNG, GIF, WEBP</small>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-modal="editCommentModal">Отмена</button>
                <button type="submit" class="btn-primary">Сохранить изменения</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?= e(BASE_URL) ?>';
    
    // Modal handling
    document.querySelectorAll('[data-close-modal]').forEach(btn => {
        btn.addEventListener('click', function() {
            const modalId = this.getAttribute('data-close-modal');
            document.getElementById(modalId).style.display = 'none';
        });
    });
    
    // Close modal when clicking overlay
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.style.display = 'none';
            }
        });
    });
    
    // Edit post button handler
    document.querySelectorAll('.edit-post-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const postId = this.getAttribute('data-post-id');
            
            // Получаем текущие значения поста
            const titleEl = document.querySelector('.post-title');
            const contentEl = document.querySelector('.post-content');
            
            document.getElementById('editPostId').value = postId;
            document.getElementById('editPostTitle').value = titleEl.textContent.trim();
            document.getElementById('editPostContent').value = contentEl.dataset.rawContent || contentEl.textContent.trim();
            
            document.getElementById('editPostModal').style.display = 'flex';
        });
    });
    
    // Edit comment button handler
    document.querySelectorAll('.edit-comment-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const commentId = this.getAttribute('data-comment-id');
            const commentCard = this.closest('.comment-card-reddit');
            const contentEl = commentCard.querySelector('.comment-content');
            
            document.getElementById('editCommentId').value = commentId;
            document.getElementById('editCommentContent').value = contentEl.textContent.trim();
            
            document.getElementById('editCommentModal').style.display = 'flex';
        });
    });
    
    // Submit edit post form
    document.getElementById('editPostForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch(baseUrl + 'api/edit_post.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Пост успешно отредактирован!');
                location.reload();
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            }
        } catch (error) {
            alert('Ошибка при отправке: ' + error.message);
        }
    });
    
    // Submit edit comment form
    document.getElementById('editCommentForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch(baseUrl + 'api/edit_comment.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Комментарий успешно отредактирован!');
                location.reload();
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            }
        } catch (error) {
            alert('Ошибка при отправке: ' + error.message);
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
