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
$categories = getCategories();
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container create-page">
    <div class="create-card">
        <h1><?= e(t('create_title')) ?></h1>
        <p class="create-subtitle">
            <?= $lang === 'en' ? 'Write clearly and follow community rules before publishing.' : 'Пишите по делу и соблюдайте правила сообщества перед публикацией.' ?>
        </p>
        <form id="createPostForm" class="create-form">
            <div class="form-group">
                <label for="title"><?= e(t('create_title_label')) ?> *</label>
                <input type="text" id="title" name="title" required placeholder="<?= e(t('create_placeholder')) ?>">
            </div>
            <div class="form-group">
                <label for="category"><?= e(t('create_category_label')) ?> *</label>
                <select id="category" name="category" required class="category-select">
                    <option value=""><?= e(t('create_choose_category')) ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['id']) ?>"><?= e($cat['emoji']) ?> <?= e(catName($cat, $lang)) ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="category-help">
                    <?= $lang === 'en' ? 'Tip: choose a category that best matches your topic.' : 'Совет: выберите категорию, которая лучше всего подходит к теме поста.' ?>
                </p>
                <div class="category-quick-list" aria-label="<?= $lang === 'en' ? 'Quick categories' : 'Быстрый выбор категорий' ?>">
                    <?php foreach ($categories as $cat): ?>
                        <button type="button" class="category-quick-btn" data-category="<?= e($cat['id']) ?>">
                            <span><?= e($cat['emoji']) ?></span>
                            <span><?= e(catName($cat, $lang)) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="form-group">
                <label for="content"><?= e(t('create_content_label')) ?> *</label>
                <textarea id="content" name="content" required placeholder="<?= e(t('create_details')) ?>"></textarea>
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
