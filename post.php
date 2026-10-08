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

// Счётчик просмотров
$postsData = readData('posts.json');
foreach ($postsData as &$p) {
    if ((int)($p['id'] ?? 0) === $postId) {
        $p['views'] = (int)($p['views'] ?? 0) + 1;
        $post['views'] = $p['views'];
        break;
    }
}
unset($p);
writeData('posts.json', $postsData);

$comments = getCommentsByPostId($postId);
$category = getSubredditById($post['category'] ?? '');
$currentUser = isLoggedIn() ? getCurrentUser() : null;
$postLiked = $currentUser && hasUserLiked($currentUser['id'], 'post', $postId);
$isPostAuthor = $currentUser && (int)($post['author_id'] ?? 0) === (int)$currentUser['id'];
$pageTitle = e($post['title']);

// Дерево комментариев: корни + дети по parent_id
$commentIds = array_flip(array_map(static fn(array $c): int => (int)($c['id'] ?? 0), $comments));
$commentTree = [];
$commentRoots = [];
foreach ($comments as $c) {
    $pid = (int)($c['parent_id'] ?? 0);
    if ($pid > 0 && isset($commentIds[$pid])) {
        $commentTree[$pid][] = $c;
    } else {
        $commentRoots[] = $c;
    }
}

/**
 * Рендер комментария с вложенными ответами
 */
function renderCommentCard(array $comment, ?array $currentUser, string $lang, int $depth = 0): string {
    global $commentTree;
    $id = (int)$comment['id'];
    $commentLiked = $currentUser && hasUserLiked((int)$currentUser['id'], 'comment', $id);
    $isCommentAuthor = $currentUser && (int)($comment['author_id'] ?? 0) === (int)$currentUser['id'];
    $contentHtml = linkifyMentions(nl2br(e((string)($comment['content'] ?? ''))));
    $verified = ((int)($comment['author_id'] ?? 0) > 0) ? isUserVerifiedById((int)$comment['author_id']) : false;

    $html = '<div class="comment-card-reddit" data-id="' . $id . '" data-parent-id="' . (int)($comment['parent_id'] ?? 0) . '" data-likes="' . (int)($comment['likes'] ?? 0) . '" data-created="' . e($comment['created_at'] ?? '') . '">';
    $html .= '<div class="comment-vote-side">';
    $html .= '<button class="vote-btn like-btn like-btn-sm' . ($commentLiked ? ' liked' : '') . '" data-type="comment" data-id="' . $id . '"' . (!$currentUser ? ' disabled' : '') . '><span class="vote-icon">♥</span></button>';
    $html .= '<span class="vote-count">' . (int)($comment['likes'] ?? 0) . '</span>';
    $html .= '</div>';
    $html .= '<div class="comment-body">';
    $html .= '<div class="comment-header"><div>';
    $html .= '<span class="comment-author">u/' . e($comment['author_name'] ?? 'Anonymous') . ($verified ? verifiedBadge() : '') . '</span>';
    $html .= '<span class="comment-date"> · ' . e(formatDate($comment['created_at'] ?? '')) . '</span>';
    $html .= '</div><div class="comment-inline-actions">';
    if ($currentUser) {
        $html .= '<button class="comment-action-btn reply-btn" data-comment-id="' . $id . '" data-author="' . e($comment['author_name'] ?? '') . '">💬 ' . e(t('comment_reply')) . '</button>';
    }
    if ($isCommentAuthor) {
        $html .= '<button class="edit-comment-btn" data-comment-id="' . $id . '" title="' . ($lang === 'en' ? 'Edit comment' : 'Редактировать комментарий') . '">✏️</button>';
    }
    if ($currentUser && !$isCommentAuthor) {
        $html .= '<button class="comment-action-btn report-btn" data-target-type="comment" data-target-id="' . $id . '">🚩 ' . e(t('report_open')) . '</button>';
    }
    $html .= '</div></div>';
    $html .= '<p class="comment-content">' . $contentHtml . '</p>';
    if (!empty($comment['image'])) {
        $html .= '<div class="comment-image-wrap"><img src="' . e(BASE_URL . $comment['image']) . '" alt="" class="comment-image"></div>';
    }
    $children = $commentTree[$id] ?? [];
    if (!empty($children)) {
        $html .= '<div class="comment-children">';
        foreach ($children as $child) {
            $html .= renderCommentCard($child, $currentUser, $lang, $depth + 1);
        }
        $html .= '</div>';
    }
    $html .= '</div></div>';

    return $html;
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="main-layout post-page-layout">
    <div class="content-area post-content-area">
        <div id="postSkeleton">
            <div class="skeleton-card"><div class="skeleton-line w40"></div><div class="skeleton-line w80"></div><div class="skeleton-line w60"></div></div>
        </div>
        <div id="postContent">
        <article class="post-full-reddit">
            <div class="post-vote-side post-vote-vertical">
                <button class="vote-btn like-btn <?= $postLiked ? 'liked' : '' ?>" data-type="post" data-id="<?= $postId ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>>
                    <span class="vote-icon">♥</span>
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
                    <button class="action-link copy-link-btn" data-url="<?= e(BASE_URL) ?>post.php?id=<?= $postId ?>">📋 Скопировать ссылку</button>
                    <span class="action-link share-post" data-url="<?= e(BASE_URL) ?>post.php?id=<?= $postId ?>"><?= e(t('post_share')) ?></span>
                    <?php $favSaved = $currentUser && hasUserFavorited($currentUser['id'], $postId); ?>
                    <button class="action-link fav-btn <?= $favSaved ? 'saved' : '' ?>" data-post-id="<?= $postId ?>" <?= !isLoggedIn() ? 'disabled' : '' ?>><?= $favSaved ? '★' : '☆' ?> <?= $lang === 'en' ? 'Save' : 'Сохранить' ?></button>
                    <span class="action-link">👁 <?= (int)($post['views'] ?? 0) ?></span>
                    <?php if ($currentUser && !$isPostAuthor): ?>
                    <button class="action-link report-btn" data-target-type="post" data-target-id="<?= $postId ?>">🚩 <?= e(t('report_open')) ?></button>
                    <?php endif; ?>
                </div>
            </div>
        </article>

        <section id="comments" class="comments-section-reddit">
            <div class="comments-head">
                <h2>Комментарии (<?= count($comments) ?>)</h2>
                <select id="commentSort" class="filter-select">
                    <option value="new"><?= $lang === 'en' ? 'Newest' : 'Сначала новые' ?></option>
                    <option value="old"><?= $lang === 'en' ? 'Oldest' : 'Сначала старые' ?></option>
                    <option value="top"><?= $lang === 'en' ? 'Top' : 'По лайкам' ?></option>
                </select>
            </div>

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
                <?php foreach ($commentRoots as $comment): ?>
                    <?= renderCommentCard($comment, $currentUser, $lang) ?>
                <?php endforeach; ?>
            </div>
        </section>
        </div><!-- /postContent -->
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
                <dt><?= $lang === 'en' ? 'Views' : 'Просмотры' ?></dt>
                <dd><?= (int)($post['views'] ?? 0) ?></dd>
            </dl>
        </div>
    </aside>
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

<!-- Modal для жалобы -->
<div id="reportModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>🚩 <?= e(t('report_title')) ?></h2>
            <button class="modal-close" data-close-modal="reportModal">&times;</button>
        </div>
        <form id="reportForm" class="post-form">
            <input type="hidden" name="target_type" id="reportTargetType">
            <input type="hidden" name="target_id" id="reportTargetId">

            <div class="form-group">
                <label for="reportReason"><?= e(t('report_reason_label')) ?> *</label>
                <textarea id="reportReason" name="reason" required maxlength="500" placeholder="<?= e(t('report_placeholder')) ?>"></textarea>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary" data-close-modal="reportModal"><?= $lang === 'en' ? 'Cancel' : 'Отмена' ?></button>
                <button type="submit" class="btn-primary"><?= e(t('report_submit')) ?></button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseUrl = '<?= e(BASE_URL) ?>';
    
// Скопировать ссылку на пост
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.copy-link-btn');
    if (!btn) return;
    const url = new URL(btn.dataset.url, location.origin).href;
    copyText(url);
    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(
                () => toast('Ссылка скопирована', 'success'),
                () => fallbackCopy(text)
            );
        } else {
            fallbackCopy(text);
        }
    }
    function fallbackCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try {
            document.execCommand('copy');
            toast('Ссылка скопирована', 'success');
        } catch {
            toast('Не удалось скопировать', 'error');
        }
        ta.remove();
    }
});
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
                toast('Пост успешно отредактирован!', 'success');
                location.reload();
            } else {
                toast('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
            }
        } catch (error) {
            toast('Ошибка при отправке: ' + error.message, 'error');
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
                toast('Комментарий успешно отредактирован!', 'success');
                location.reload();
            } else {
                toast('Ошибка: ' + (data.error || 'Неизвестная ошибка'), 'error');
            }
        } catch (error) {
            toast('Ошибка при отправке: ' + error.message, 'error');
        }
    });

    // ---- Ответ на комментарий и жалобы (привязка для любых карточек) ----
    window.bindCommentCardActions = function(root) {
        root.querySelectorAll('.reply-btn').forEach(btn => {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function() {
                const card = this.closest('.comment-card-reddit');
                const body = card.querySelector(':scope > .comment-body');
                const existing = body.querySelector(':scope > .reply-form-inline');
                if (existing) { existing.remove(); return; }
                document.querySelectorAll('.reply-form-inline').forEach(f => f.remove());

                const author = this.dataset.author || '';
                const form = document.createElement('form');
                form.className = 'comment-form reply-form-inline';
                form.innerHTML =
                    '<textarea name="content" class="post-textarea" required placeholder="' + escapeAttr('Ваш ответ' + (author ? ' u/' + author : '') + '...') + '"></textarea>' +
                    '<div class="comment-form-actions">' +
                    '<button type="button" class="btn-secondary reply-cancel">' + escapeHtml('<?= $lang === 'en' ? 'Cancel' : 'Отмена' ?>') + '</button>' +
                    '<button type="submit" class="btn-primary">' + escapeHtml('<?= e(t('comment_reply')) ?>') + '</button>' +
                    '</div>';
                body.appendChild(form);
                form.querySelector('textarea').focus();

                form.querySelector('.reply-cancel').addEventListener('click', () => form.remove());

                form.addEventListener('submit', async function(ev) {
                    ev.preventDefault();
                    const textarea = form.querySelector('textarea');
                    const content = textarea.value.trim();
                    if (!content) return;

                    const submitBtn = form.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;

                    const fd = new FormData();
                    fd.append('post_id', '<?= $postId ?>');
                    fd.append('content', content);
                    fd.append('parent_id', card.dataset.id);

                    try {
                        const res = await fetch(baseUrl + 'api/add_comment.php', { method: 'POST', body: fd });
                        const json = await res.json();
                        if (json.success) {
                            window.LevelSystem?.applyLevelingUpdate?.(json.leveling);
                            location.reload();
                        } else {
                            toast(json.error || 'Ошибка', 'error');
                            submitBtn.disabled = false;
                        }
                    } catch (err) {
                        toast('Ошибка сети', 'error');
                        submitBtn.disabled = false;
                    }
                });
            });
        });

        root.querySelectorAll('.report-btn').forEach(btn => {
            if (btn.dataset.bound) return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', function() {
                document.getElementById('reportTargetType').value = this.dataset.targetType;
                document.getElementById('reportTargetId').value = this.dataset.targetId;
                document.getElementById('reportReason').value = '';
                document.getElementById('reportModal').style.display = 'flex';
            });
        });
    };
    bindCommentCardActions(document);

    // ---- Жалобы: отправка формы ----
    const reportModal = document.getElementById('reportModal');
    const reportForm = document.getElementById('reportForm');

    if (reportForm) {
        reportForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const payload = {
                target_type: document.getElementById('reportTargetType').value,
                target_id: document.getElementById('reportTargetId').value,
                reason: document.getElementById('reportReason').value.trim()
            };
            try {
                const res = await fetch(baseUrl + 'api/report.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (json.success) {
                    toast('<?= e(t('report_success')) ?>', 'success');
                    reportModal.style.display = 'none';
                } else {
                    toast(json.error || '<?= e(t('report_error')) ?>', 'error');
                }
            } catch (err) {
                toast('Ошибка сети', 'error');
            }
        });
    }

    function escapeAttr(text) {
        return escapeHtml(text).replace(/"/g, '&quot;');
    }
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
