<?php
/**
 * MonoTalk - Новости форума
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/lang.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$lang = getLang();
$news = readData('news.json');
$pageTitle = $lang === 'en' ? 'News' : 'Новости';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container news-page">
    <h1><?= $lang === 'en' ? 'Forum News' : 'Новости форума' ?></h1>
    <p class="news-intro"><?= $lang === 'en' ? 'Latest updates and announcements about MonoTalk.' : 'Свежие обновления и анонсы MonoTalk.' ?></p>

    <div class="news-list">
        <?php foreach (array_reverse($news) as $item): ?>
            <article class="news-card">
                <time class="news-date" datetime="<?= e($item['date'] ?? '') ?>"><?= e(date('d.m.Y', strtotime($item['date'] ?? 'now'))) ?></time>
                <h2><?= e($lang === 'en' ? ($item['title_en'] ?? $item['title_ru']) : ($item['title_ru'] ?? '')) ?></h2>
                <p><?= nl2br(e($lang === 'en' ? ($item['content_en'] ?? $item['content_ru']) : ($item['content_ru'] ?? ''))) ?></p>
            </article>
        <?php endforeach; ?>
    </div>

    <?php if (empty($news)): ?>
        <p class="empty-state"><?= $lang === 'en' ? 'No news yet.' : 'Пока нет новостей.' ?></p>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
