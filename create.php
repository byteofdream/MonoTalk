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
        <form id="createPostForm" class="create-form">
            <div class="form-group">
                <label for="title"><?= e(t('create_title_label')) ?> *</label>
                <input type="text" id="title" name="title" required placeholder="<?= e(t('create_placeholder')) ?>">
            </div>
            <div class="form-group">
                <label for="category"><?= e(t('create_category_label')) ?> *</label>
                <select id="category" name="category" required>
                    <option value=""><?= e(t('create_choose_category')) ?></option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['id']) ?>"><?= e($cat['emoji']) ?> <?= e(catName($cat, $lang)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="content"><?= e(t('create_content_label')) ?> *</label>
                <textarea id="content" name="content" required placeholder="<?= e(t('create_details')) ?>"></textarea>
            </div>
            <label class="checkbox-label">
                <input type="checkbox" name="anonymous" value="1"> <?= e(t('create_anonymous')) ?>
            </label>
            <button type="submit" class="btn-primary"><?= e(t('create_submit')) ?></button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
