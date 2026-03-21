<?php
/**
 * MonoTalk - Создание сабреддита
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

requireAuth();

$lang = getLang();
$pageTitle = t('create_subreddit_title');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container create-page">
    <div class="create-card">
        <h1><?= e(t('create_subreddit_title')) ?></h1>
        <p class="create-subtitle">
            <?= $lang === 'en'
                ? 'Create a community with a clear topic and short description.'
                : 'Создайте сообщество с понятной темой и коротким описанием.' ?>
        </p>
        <form id="createSubredditForm" class="create-form">
            <div class="form-group">
                <label for="subreddit_name"><?= e(t('create_subreddit_name_label')) ?> *</label>
                <input type="text" id="subreddit_name" name="name" required placeholder="<?= $lang === 'en' ? 'Example: Gaming, Music, Tips' : 'Например: Игры, Музыка, Советы' ?>">
                <p class="category-help">
                    <?= $lang === 'en' ? '3-30 characters. A readable name works best.' : '3-30 символов. Лучше использовать понятное название.' ?>
                </p>
            </div>
            <div class="form-group">
                <label for="subreddit_desc"><?= e(t('create_subreddit_desc_label')) ?></label>
                <textarea id="subreddit_desc" name="description" placeholder="<?= $lang === 'en' ? 'What is this subreddit about?' : 'О чем этот сабреддит?' ?>"></textarea>
            </div>
            <div class="form-group">
                <label for="subreddit_emoji"><?= e(t('create_subreddit_emoji_label')) ?></label>
                <input type="text" id="subreddit_emoji" name="emoji" maxlength="4" placeholder="<?= $lang === 'en' ? 'Optional, for example: 🧵' : 'Необязательно, например: 🧵' ?>">
            </div>
            <button type="submit" class="btn-primary"><?= e(t('create_subreddit_submit')) ?></button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
