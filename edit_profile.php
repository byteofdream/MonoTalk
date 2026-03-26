<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

requireAuth();

$user = getCurrentUser();
$pageTitle = t('profile_edit_title');
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container edit-profile-page">
    <div class="profile-editor-card">
        <div class="profile-editor-head">
            <div>
                <h1><?= e(t('profile_edit_title')) ?></h1>
                <p class="profile-editor-subtitle">u/<?= e($user['username'] ?? '') ?></p>
            </div>
            <a href="<?= e(BASE_URL) ?>profile.php" class="btn-secondary"><?= e(t('nav_profile')) ?></a>
        </div>

        <form id="profileForm" class="profile-form" enctype="multipart/form-data">
            <div class="form-group">
                <label><?= e(t('profile_avatar_url')) ?></label>
                <input type="url" name="avatar_url" placeholder="https://..." value="<?= e($user['avatar'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><?= e(t('profile_avatar_file')) ?></label>
                <input type="file" name="avatar" accept="image/*">
            </div>
            <div class="form-group">
                <label for="profileBio"><?= e(t('profile_bio')) ?></label>
                <textarea id="profileBio" name="bio" maxlength="300" placeholder="<?= e(t('profile_bio_placeholder')) ?>"><?= e($user['bio'] ?? '') ?></textarea>
                <div class="profile-char-counter" id="profileBioCounter" data-template="<?= e(t('profile_bio_counter')) ?>">0 / 300</div>
            </div>
            <div class="profile-links-grid">
                <div class="form-group">
                    <label><?= e(t('profile_website')) ?></label>
                    <input type="url" name="website" placeholder="https://example.com" value="<?= e($user['website'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= e(t('profile_github')) ?></label>
                    <input type="url" name="github" placeholder="https://github.com/username" value="<?= e($user['github'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= e(t('profile_telegram')) ?></label>
                    <input type="text" name="telegram" placeholder="@username or https://t.me/username" value="<?= e($user['telegram'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label><?= e(t('profile_discord')) ?></label>
                    <input type="text" name="discord" placeholder="username or https://discord.com/..." value="<?= e($user['discord'] ?? '') ?>">
                </div>
            </div>
            <div class="form-group">
                <label for="otherLinks"><?= e(t('profile_other_links')) ?></label>
                <textarea id="otherLinks" name="other_links" placeholder="https://example.com/link-1&#10;https://example.com/link-2"><?= e(implode("\n", array_values(array_filter($user['other_links'] ?? [], 'is_string')))) ?></textarea>
                <small class="field-hint"><?= e(t('profile_other_links_hint')) ?></small>
            </div>
            <div class="profile-preview-card">
                <h2><?= e(t('profile_preview')) ?></h2>
                <div id="profilePreview" class="profile-preview"
                    data-empty-text="<?= e(t('profile_preview_empty')) ?>"
                    data-website-label="<?= e('🌐 ' . t('profile_link_website')) ?>"
                    data-github-label="💻 GitHub"
                    data-telegram-label="📱 Telegram"
                    data-discord-label="🎮 Discord"
                    data-other-label="<?= e(t('profile_link_other')) ?>"></div>
            </div>
            <button type="submit" class="btn-primary"><?= e(t('profile_save')) ?></button>
        </form>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
