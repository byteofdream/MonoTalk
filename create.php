<?php
/**
 * MonoTalk - Создание поста
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

requireAuth();

$lang = getLang();
$pageTitle = t('create_title');
$subreddits = getSubreddits();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container create-page">
    <div class="create-card">
        <h1><?= e(t('create_title')) ?></h1>
        <p class="create-subtitle">
            <?= $lang === 'en' ? 'Write clearly and follow community rules before publishing.' : 'Пишите по делу и соблюдайте правила сообщества перед публикацией.' ?>
        </p>
        <form id="createPostForm" class="create-form" enctype="multipart/form-data">
            <div class="form-group">
                <label for="title"><?= e(t('create_title_label')) ?> *</label>
                <input type="text" id="title" name="title" required placeholder="<?= e(t('create_placeholder')) ?>">
            </div>
            <div class="form-group">
                <label for="category"><?= e(t('create_category_label')) ?> *</label>
                <select id="category" name="category" required class="category-select">
                    <option value=""><?= e(t('create_choose_category')) ?></option>
                    <?php foreach ($subreddits as $sub): ?>
                        <option value="<?= e($sub['id']) ?>"><?= e($sub['emoji'] ?? '') ?> <?= e(catName($sub, $lang)) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="category-help">
                    <?= $lang === 'en' ? 'Tip: choose a subreddit that best matches your topic.' : 'Совет: выберите сабреддит, который лучше всего подходит к теме поста.' ?>
                </p>
                <div class="category-quick-list" aria-label="<?= $lang === 'en' ? 'Quick subreddits' : 'Быстрый выбор сабреддитов' ?>">
                    <?php foreach ($subreddits as $sub): ?>
                        <button type="button" class="category-quick-btn" data-category="<?= e($sub['id']) ?>">
                            <span><?= e($sub['emoji'] ?? '') ?></span>
                            <span><?= e(catName($sub, $lang)) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <?php if (isLoggedIn()): ?>
                    <p class="category-help">
                        <a href="<?= e(BASE_URL) ?>create_subreddit.php"><?= e(t('create_subreddit_link')) ?></a>
                    </p>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="content"><?= e(t('create_content_label')) ?> *</label>
                <div class="format-toolbar" aria-label="<?= e(t('create_format_toolbar')) ?>">
                    <button type="button" class="format-btn" data-format-target="content" data-format-wrap="**">B</button>
                    <button type="button" class="format-btn" data-format-target="content" data-format-wrap="*">I</button>
                </div>
                <textarea id="content" name="content" placeholder="<?= e(t('create_details')) ?>"></textarea>
                <p class="category-help">
                    <?= e(t('create_format_help')) ?>
                </p>
                <p class="category-help">
                    <?= e(t('create_image_flow_help')) ?>
                </p>
            </div>
            <div class="form-group">
                <label for="post_image"><?= $lang === 'en' ? 'Image (optional)' : 'Картинка (необязательно)' ?></label>
                <input type="file" id="post_image" name="image" accept="image/*">
                <p class="category-help"><?= $lang === 'en' ? 'PNG, JPG, GIF, WEBP up to 5 MB.' : 'PNG, JPG, GIF, WEBP до 5 МБ.' ?></p>
            </div>
            <label class="checkbox-label">
                <input type="checkbox" name="anonymous" value="1"> <?= e(t('create_anonymous')) ?>
            </label>
            <label class="checkbox-label checkbox-rules">
                <input type="checkbox" name="agree_rules" value="1" required>
                <?= $lang === 'en' ? 'I agree with the ' : 'Я соглашаюсь с ' ?>
                <a href="<?= e(BASE_URL) ?>rules.php" target="_blank" rel="noopener">
                    <?= $lang === 'en' ? 'community rules' : 'правилами сообщества' ?>
                </a>
            </label>
            <button type="submit" class="btn-primary"><?= e(t('create_submit')) ?></button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
