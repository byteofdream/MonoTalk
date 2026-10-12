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

            <fieldset class="profile-form-group">
                <legend><?= e(t('profile_customize')) ?></legend>

                <div class="profile-links-grid">
                    <div class="form-group">
                        <label for="displayName"><?= e(t('profile_display_name')) ?></label>
                        <input type="text" id="displayName" name="display_name" maxlength="30" value="<?= e($user['display_name'] ?? '') ?>">
                        <small class="field-hint"><?= e(t('profile_display_name_hint')) ?></small>
                    </div>
                    <div class="form-group">
                        <label for="profilePronouns"><?= e(t('profile_pronouns')) ?></label>
                        <input type="text" id="profilePronouns" name="pronouns" maxlength="30" placeholder="<?= e(t('profile_pronouns_placeholder')) ?>" value="<?= e($user['pronouns'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="profileLocation"><?= e(t('profile_location')) ?></label>
                        <input type="text" id="profileLocation" name="location" maxlength="50" placeholder="<?= e(t('profile_location_placeholder')) ?>" value="<?= e($user['location'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="statusEmoji"><?= e(t('profile_status_emoji')) ?></label>
                        <input type="text" id="statusEmoji" name="status_emoji" maxlength="8" placeholder="☕" value="<?= e($user['status_emoji'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="statusText"><?= e(t('profile_status_text')) ?></label>
                        <input type="text" id="statusText" name="status_text" maxlength="60" placeholder="<?= e(t('profile_status_placeholder')) ?>" value="<?= e($user['status_text'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bannerUrl"><?= e(t('profile_banner_url')) ?></label>
                        <input type="url" id="bannerUrl" name="banner_url" placeholder="https://..." value="<?= e($user['banner'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="bannerFile"><?= e(t('profile_banner_file')) ?></label>
                        <input type="file" id="bannerFile" name="banner" accept="image/*">
                    </div>
                </div>

                <div class="form-group">
                    <label><?= e(t('profile_accent')) ?></label>
                    <div class="accent-picker" id="accentPicker">
                        <?php
                        $accentPresets = ['', '#147aff', '#7c5cff', '#ff4d8d', '#ff7a00', '#16a34a', '#0ea5e9', '#e11d48', '#0f172a'];
                        $currentAccent = strtolower(trim((string)($user['accent'] ?? '')));
                        foreach ($accentPresets as $preset):
                        ?>
                        <button type="button"
                                class="accent-swatch accent-swatch--auto<?= $currentAccent === $preset ? ' is-active' : '' ?>"
                                data-accent="<?= e($preset) ?>"
                                title="<?= e($preset === '' ? t('profile_accent_auto') : $preset) ?>"
                                style="--swatch: <?= $preset === '' ? 'var(--accent)' : e($preset) ?>"><?= $preset === '' ? 'A' : '' ?></button>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="accent" id="accentInput" value="<?= e($currentAccent) ?>">
                    <small class="field-hint"><?= e(t('profile_accent_hint')) ?></small>
                </div>

                <div class="form-group">
                    <label><?= e(t('profile_privacy')) ?></label>
                    <div class="privacy-toggles">
                        <label class="switch-row">
                            <input type="checkbox" name="hide_xp" value="1" <?= !empty($user['hide_xp']) ? 'checked' : '' ?>>
                            <span><?= e(t('profile_hide_xp')) ?></span>
                        </label>
                        <label class="switch-row">
                            <input type="checkbox" name="hide_ach" value="1" <?= !empty($user['hide_ach']) ? 'checked' : '' ?>>
                            <span><?= e(t('profile_hide_ach')) ?></span>
                        </label>
                        <label class="switch-row">
                            <input type="checkbox" name="hide_links" value="1" <?= !empty($user['hide_links']) ? 'checked' : '' ?>>
                            <span><?= e(t('profile_hide_links')) ?></span>
                        </label>
                    </div>
                </div>
            </fieldset>

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
