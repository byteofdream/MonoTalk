<?php
/**
 * MonoTalk - Админ-панель (только для verified пользователей)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

requireAuth();

$lang = getLang();
$currentUser = getCurrentUser();

if (empty($currentUser['verified'])) {
    header('Location: ' . BASE_URL . '403.php');
    exit;
}

$tab = $_GET['tab'] ?? 'stats';
$allowedTabs = ['stats', 'users', 'posts', 'comments', 'subreddits', 'reports', 'logs'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'stats';

$users = readData('users.json');
$posts = readData('posts.json');
$comments = readData('comments.json');
$subreddits = readData('subreddits.json');
$reports = readData('reports.json');
$openReports = array_filter($reports, fn($r) => ($r['status'] ?? 'open') === 'open');
$logs = readData('moderation_logs.json');
$pageTitle = $lang === 'en' ? 'Admin' : 'Админ';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container settings-page">
    <h1><?= $lang === 'en' ? 'Admin Panel' : 'Админ-панель' ?></h1>

    <div class="settings-tabs">
        <a href="admin.php?tab=stats" class="settings-tab <?= $tab === 'stats' ? 'active' : '' ?>">📊 <?= $lang === 'en' ? 'Stats' : 'Статистика' ?></a>
        <a href="admin.php?tab=users" class="settings-tab <?= $tab === 'users' ? 'active' : '' ?>">👥 <?= $lang === 'en' ? 'Users' : 'Юзеры' ?></a>
        <a href="admin.php?tab=posts" class="settings-tab <?= $tab === 'posts' ? 'active' : '' ?>">📝 <?= $lang === 'en' ? 'Posts' : 'Посты' ?></a>
        <a href="admin.php?tab=comments" class="settings-tab <?= $tab === 'comments' ? 'active' : '' ?>">💬 <?= $lang === 'en' ? 'Comments' : 'Комменты' ?></a>
        <a href="admin.php?tab=subreddits" class="settings-tab <?= $tab === 'subreddits' ? 'active' : '' ?>">📂 <?= $lang === 'en' ? 'Subs' : 'Сабы' ?></a>
        <a href="admin.php?tab=reports" class="settings-tab <?= $tab === 'reports' ? 'active' : '' ?>">🚩 <?= $lang === 'en' ? 'Reports' : 'Жалобы' ?><?= count($openReports) > 0 ? ' (' . count($openReports) . ')' : '' ?></a>
        <a href="admin.php?tab=logs" class="settings-tab <?= $tab === 'logs' ? 'active' : '' ?>">📋 <?= $lang === 'en' ? 'Logs' : 'Логи' ?></a>
    </div>

    <?php if ($tab === 'stats'): ?>
        <div class="settings-content">
            <section class="settings-section">
                <h2><?= $lang === 'en' ? 'Stats' : 'Статистика' ?></h2>
                <p>👥 Пользователей: <strong><?= count($users) ?></strong></p>
                <p>📝 Постов: <strong><?= count($posts) ?></strong></p>
                <p>💬 Комментариев: <strong><?= count($comments) ?></strong></p>
                <p>📂 Сабреддитов: <strong><?= count($subreddits) ?></strong></p>
                <p>🚫 Забанено: <strong><?= count(array_filter($users, fn($u) => ($u['status'] ?? 'active') === 'banned')) ?></strong></p>
                <p>✔ С галочкой: <strong><?= count(array_filter($users, fn($u) => !empty($u['verified']))) ?></strong></p>
            </section>

            <section class="settings-section">
                <h2>🔧 <?= $lang === 'en' ? 'Maintenance mode' : 'Режим техработ' ?></h2>
                <div class="maintenance-toggle-row">
                    <label class="switch">
                        <input type="checkbox" id="maintenanceToggle" <?= isMaintenanceEnabled() ? 'checked' : '' ?>>
                        <span class="slider"></span>
                    </label>
                    <div class="maintenance-toggle-info">
                        <strong id="maintenanceStatusLabel"
                                data-on-text="<?= e($lang === 'en' ? 'Enabled — visitors see the maintenance page' : 'Включён — посетители видят страницу техработ') ?>"
                                data-off-text="<?= e($lang === 'en' ? 'Disabled — the site works as usual' : 'Выключен — сайт работает как обычно') ?>">
                            <?= isMaintenanceEnabled()
                                ? ($lang === 'en' ? 'Enabled — visitors see the maintenance page' : 'Включён — посетители видят страницу техработ')
                                : ($lang === 'en' ? 'Disabled — the site works as usual' : 'Выключен — сайт работает как обычно') ?>
                        </strong>
                        <div class="maintenance-toggle-hint">
                            <?= $lang === 'en'
                                ? 'When enabled, all pages redirect to maintenance.php. Only verified users keep access. Login and registration stay open.'
                                : 'При включении все страницы перенаправляются на maintenance.php. Доступ сохраняют только verified-пользователи. Логин и регистрация остаются открытыми.' ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    <?php endif; ?>

    <?php if ($tab === 'users'): ?>
        <section class="settings-section">
            <h2>Пользователи</h2>
            <input type="text" id="adminUserSearch" class="sidebar-search-input" placeholder="🔍 Поиск по нику..." style="max-width: 320px;">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;" id="adminUserList">
                <?php foreach ($users as $u): ?>
                    <div class="sidebar-card admin-user-card" data-username="<?= e(mb_strtolower($u['username'])) ?>" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                        <div>
                            <strong>u/<?= e($u['username']) ?></strong>
                            <?= !empty($u['verified']) ? verifiedBadge() : '' ?>
                            <?= ($u['status'] ?? 'active') === 'banned' ? ' <span style="color:var(--red)">[BAN]</span>' : '' ?>
                            <div style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= e($u['email'] ?? '') ?> · role: <?= e($u['role'] ?? 'user') ?> · <?= e($u['created_at'] ?? '') ?>
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                            <a href="api/admin_toggle.php?tab=users&id=<?= (int)$u['id'] ?>&action=verify&redirect=<?= urlencode('/admin.php?tab=users') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem;"><?= !empty($u['verified']) ? '✖ галка' : '✔ галка' ?></a>
                            <a href="api/admin_toggle.php?tab=users&id=<?= (int)$u['id'] ?>&action=ban&redirect=<?= urlencode('/admin.php?tab=users') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem;"><?= ($u['status'] ?? 'active') === 'banned' ? 'Разбан' : 'Бан' ?></a>
                            <a href="api/admin_toggle.php?tab=users&id=<?= (int)$u['id'] ?>&action=role&redirect=<?= urlencode('/admin.php?tab=users') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem;">Role: <?= e($u['role'] ?? 'user') ?></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'posts'): ?>
        <section class="settings-section">
            <h2>Посты</h2>
            <input type="text" id="adminPostSearch" class="sidebar-search-input" placeholder="🔍 Поиск по постам..." style="max-width: 320px;">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;" id="adminPostList">
                <?php foreach ($posts as $p): ?>
                    <div class="sidebar-card admin-post-card" data-search="<?= e(mb_strtolower(($p['title'] ?? '') . ' ' . ($p['author_name'] ?? ''))) ?>" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <div>
                            <a href="post.php?id=<?= (int)$p['id'] ?>"><strong><?= e($p['title'] ?? '') ?></strong></a>
                            <div style="color: var(--text-muted); font-size: 0.85rem;">u/<?= e($p['author_name'] ?? '') ?> · ❤ <?= (int)($p['likes'] ?? 0) ?> · 👁 <?= (int)($p['views'] ?? 0) ?> · <?= e($p['created_at'] ?? '') ?></div>
                        </div>
                        <a href="api/admin_toggle.php?tab=posts&id=<?= (int)$p['id'] ?>&action=delete_post&redirect=<?= urlencode('/admin.php?tab=posts') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; color: var(--red);" onclick="return confirm('Удалить пост?')">Удалить</a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($posts)): ?><p class="empty-state">Постов нет</p><?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'comments'): ?>
        <section class="settings-section">
            <h2>Комментарии</h2>
            <input type="text" id="adminCommentSearch" class="sidebar-search-input" placeholder="🔍 Поиск по комментариям..." style="max-width: 320px;">
            <div style="display: flex; flex-direction: column; gap: 0.75rem;" id="adminCommentList">
                <?php foreach ($comments as $c): ?>
                    <div class="sidebar-card admin-comment-card" data-search="<?= e(mb_strtolower(($c['content'] ?? '') . ' ' . ($c['author_name'] ?? ''))) ?>" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <div>
                            <strong>u/<?= e($c['author_name'] ?? '') ?></strong>
                            <div style="color: var(--text-muted); font-size: 0.85rem;"><?= e(mb_substr($c['content'] ?? '', 0, 80)) ?>…</div>
                        </div>
                        <a href="api/admin_toggle.php?tab=comments&id=<?= (int)$c['id'] ?>&action=delete_comment&redirect=<?= urlencode('/admin.php?tab=comments') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; color: var(--red);" onclick="return confirm('Удалить комментарий?')">Удалить</a>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($comments)): ?><p class="empty-state">Комментариев нет</p><?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'subreddits'): ?>
        <section class="settings-section">
            <h2>Сабреддиты</h2>
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <?php foreach ($subreddits as $s): ?>
                    <div class="sidebar-card" style="margin-bottom: 0; display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
                        <div>
                            <strong><?= e($s['emoji'] ?? '') ?> r/<?= e($s['name'] ?? '') ?></strong>
                            <div style="color: var(--text-muted); font-size: 0.85rem;"><?= (int)($s['subscribers_count'] ?? 0) ?> подписчиков</div>
                        </div>
                        <a href="api/admin_toggle.php?tab=subreddits&id=<?= e($s['id']) ?>&action=delete_subreddit&redirect=<?= urlencode('/admin.php?tab=subreddits') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; color: var(--red);" onclick="return confirm('Удалить сабреддит?')">Удалить</a>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'reports'): ?>
        <section class="settings-section">
            <h2>🚩 <?= $lang === 'en' ? 'Reports' : 'Жалобы' ?></h2>
            <?php if (empty($reports)): ?>
                <p class="empty-state"><?= $lang === 'en' ? 'No reports yet.' : 'Жалоб пока нет.' ?></p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                    <?php foreach (array_reverse($reports) as $r): ?>
                        <?php $isOpen = ($r['status'] ?? 'open') === 'open'; ?>
                        <div class="sidebar-card" style="margin-bottom: 0; opacity: <?= $isOpen ? '1' : '0.6' ?>;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                                <div style="min-width: 0;">
                                    <strong>
                                        <?= ($r['target_type'] ?? '') === 'post' ? '📝 ' . ($lang === 'en' ? 'Post' : 'Пост') : '💬 ' . ($lang === 'en' ? 'Comment' : 'Комментарий') ?>
                                        #<?= (int)($r['target_id'] ?? 0) ?>
                                    </strong>
                                    <?= $isOpen
                                        ? ' <span style="color: var(--red); font-size: 0.8rem;">[' . ($lang === 'en' ? 'OPEN' : 'ОТКРЫТА') . ']</span>'
                                        : ' <span style="color: var(--text-muted); font-size: 0.8rem;">[' . ($lang === 'en' ? 'RESOLVED' : 'ЗАКРЫТА') . ']</span>' ?>
                                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-top: 0.25rem;">
                                        <?= $lang === 'en' ? 'Reason' : 'Причина' ?>: <?= e($r['reason'] ?? '') ?>
                                    </div>
                                    <div style="color: var(--text-muted); font-size: 0.85rem;">
                                        <?= $lang === 'en' ? 'Reporter' : 'Жалобщик' ?>: u/<?= e($r['reporter_name'] ?? '') ?> ·
                                        <?= $lang === 'en' ? 'Author' : 'Автор' ?>: u/<?= e($r['author_name'] ?? '') ?> ·
                                        <?= e($r['created_at'] ?? '') ?>
                                        <?php if (!empty($r['post_id'])): ?>
                                            · <a href="post.php?id=<?= (int)$r['post_id'] ?>" target="_blank"><?= $lang === 'en' ? 'Open' : 'Открыть' ?></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($isOpen): ?>
                                <div style="display: flex; gap: 0.4rem; flex-wrap: wrap;">
                                    <?php if (($r['target_type'] ?? '') === 'post'): ?>
                                        <a href="api/admin_toggle.php?id=<?= (int)($r['id'] ?? 0) ?>&action=report_delete_post&redirect=<?= urlencode('/admin.php?tab=reports') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; color: var(--red);" onclick="return confirm('<?= $lang === 'en' ? 'Delete the post?' : 'Удалить пост?' ?>')"><?= $lang === 'en' ? 'Delete post' : 'Удалить пост' ?></a>
                                    <?php else: ?>
                                        <a href="api/admin_toggle.php?id=<?= (int)($r['id'] ?? 0) ?>&action=report_delete_comment&redirect=<?= urlencode('/admin.php?tab=reports') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem; color: var(--red);" onclick="return confirm('<?= $lang === 'en' ? 'Delete the comment?' : 'Удалить комментарий?' ?>')"><?= $lang === 'en' ? 'Delete comment' : 'Удалить коммент.' ?></a>
                                    <?php endif; ?>
                                    <a href="api/admin_toggle.php?id=<?= (int)($r['id'] ?? 0) ?>&action=report_strike&redirect=<?= urlencode('/admin.php?tab=reports') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem;" onclick="return confirm('<?= $lang === 'en' ? 'Give author a strike?' : 'Выдать автору страйк?' ?>')"><?= $lang === 'en' ? 'Strike' : 'Страйк' ?></a>
                                    <a href="api/admin_toggle.php?id=<?= (int)($r['id'] ?? 0) ?>&action=report_resolve&redirect=<?= urlencode('/admin.php?tab=reports') ?>" class="btn-secondary" style="padding: 0.3rem 0.6rem;"><?= $lang === 'en' ? 'Dismiss' : 'Отклонить' ?></a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($tab === 'logs'): ?>
        <section class="settings-section">
            <h2>Логи модерации</h2>
            <?php if (empty($logs)): ?>
                <p class="empty-state">Логов пока нет.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <?php foreach (array_reverse($logs) as $log): ?>
                        <div class="sidebar-card" style="margin-bottom: 0; font-size: 0.9rem;">
                            <strong><?= e($log['action'] ?? '') ?></strong> · <?= e($log['target'] ?? '') ?> · <?= e($log['created_at'] ?? '') ?>
                            <?php if (!empty($log['reason'])): ?><div style="color: var(--text-muted);"><?= e($log['reason']) ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>

<script>
(function() {
    function bindSearch(inputId, cardSelector, attr) {
        var input = document.getElementById(inputId);
        if (!input) return;
        input.addEventListener('input', function() {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll(cardSelector).forEach(function(card) {
                var text = (card.getAttribute(attr) || '');
                card.style.display = (q === '' || text.includes(q)) ? '' : 'none';
            });
        });
    }
    bindSearch('adminUserSearch', '.admin-user-card', 'data-username');
    bindSearch('adminPostSearch', '.admin-post-card', 'data-search');
    bindSearch('adminCommentSearch', '.admin-comment-card', 'data-search');
})();

// Переключатель режима обслуживания
(function() {
    var toggle = document.getElementById('maintenanceToggle');
    if (!toggle) return;
    var label = document.getElementById('maintenanceStatusLabel');
    var busy = false;

    toggle.addEventListener('change', async function() {
        if (busy) return;
        busy = true;
        var want = toggle.checked;
        try {
            var res = await fetch('api/set_maintenance.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ enabled: want })
            });
            var json = await res.json();
            if (json.success) {
                toggle.checked = !!json.enabled;
                if (label) {
                    label.textContent = json.enabled
                        ? (label.dataset.onText || '')
                        : (label.dataset.offText || '');
                }
            } else {
                toggle.checked = !want;
                alert(json.error || 'Ошибка');
            }
        } catch (e) {
            toggle.checked = !want;
            alert('Ошибка сети');
        }
        busy = false;
    });
})();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
