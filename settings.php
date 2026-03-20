<?php
/**
 * MonoTalk - Настройки (с вкладками)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$theme = getTheme();
$tab = $_GET['tab'] ?? 'general';
$allowedTabs = ['general', 'appearance', 'about'];
if (!in_array($tab, $allowedTabs)) $tab = 'general';

$titles = [
    'general' => $lang === 'en' ? 'General' : 'Общие',
    'appearance' => $lang === 'en' ? 'Appearance' : 'Внешний вид',
    'about' => $lang === 'en' ? 'About' : 'О форуме',
];
$pageTitle = $titles[$tab] ?? 'Settings';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container settings-page">
    <h1><?= $lang === 'en' ? 'Settings' : 'Настройки' ?></h1>

    <div class="settings-tabs">
        <a href="?tab=general" class="settings-tab <?= $tab === 'general' ? 'active' : '' ?>"><?= e($titles['general']) ?></a>
        <a href="?tab=appearance" class="settings-tab <?= $tab === 'appearance' ? 'active' : '' ?>"><?= e($titles['appearance']) ?></a>
        <a href="?tab=about" class="settings-tab <?= $tab === 'about' ? 'active' : '' ?>"><?= e($titles['about']) ?></a>
    </div>

    <div class="settings-content">
        <?php if ($tab === 'general'): ?>
            <section class="settings-section">
                <h2><?= $lang === 'en' ? 'Language' : 'Язык' ?></h2>
                <p><?= $lang === 'en' ? 'Choose the interface language:' : 'Выберите язык интерфейса:' ?></p>
                <div class="lang-options">
                    <a href="<?= e(BASE_URL) ?>api/set_language.php?lang=ru&redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/settings.php') ?>" class="lang-option <?= $lang === 'ru' ? 'active' : '' ?>">Русский</a>
                    <a href="<?= e(BASE_URL) ?>api/set_language.php?lang=en&redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/settings.php') ?>" class="lang-option <?= $lang === 'en' ? 'active' : '' ?>">English</a>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tab === 'appearance'): ?>
            <section class="settings-section">
                <h2><?= $lang === 'en' ? 'Theme' : 'Тема' ?></h2>
                <p><?= $lang === 'en' ? 'Choose light or dark theme:' : 'Выберите светлую или тёмную тему:' ?></p>
                <div class="theme-options">
                    <a href="<?= e(BASE_URL) ?>api/set_theme.php?theme=light&redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/settings.php?tab=appearance') ?>" class="theme-option <?= $theme === 'light' ? 'active' : '' ?>" title="<?= $lang === 'en' ? 'Light' : 'Светлая' ?>">
                        <span class="theme-preview theme-light"></span>
                        <span><?= $lang === 'en' ? 'Light' : 'Светлая' ?></span>
                    </a>
                    <a href="<?= e(BASE_URL) ?>api/set_theme.php?theme=dark&redirect=<?= urlencode($_SERVER['REQUEST_URI'] ?? '/settings.php?tab=appearance') ?>" class="theme-option <?= $theme === 'dark' ? 'active' : '' ?>" title="<?= $lang === 'en' ? 'Dark' : 'Тёмная' ?>">
                        <span class="theme-preview theme-dark"></span>
                        <span><?= $lang === 'en' ? 'Dark' : 'Тёмная' ?></span>
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($tab === 'about'): ?>
            <section class="settings-section about-section">
                <h2><?= $lang === 'en' ? 'About MonoTalk' : 'О MonoTalk' ?></h2>
                <?php if ($lang === 'en'): ?>
                    <p>MonoTalk is a modern PHP forum without frameworks. Simple, fast, and easy to deploy on any hosting.</p>
                    <p><strong>Features:</strong> registration, posts, comments, likes, categories, search, user profiles.</p>
                    <p><strong>Tech stack:</strong> PHP, JSON storage, vanilla JavaScript.</p>
                <?php else: ?>
                    <p>MonoTalk — современный форум на чистом PHP без фреймворков. Простой, быстрый и удобный для размещения на любом хостинге.</p>
                    <p><strong>Возможности:</strong> регистрация, посты, комментарии, лайки, категории, поиск, профили пользователей.</p>
                    <p><strong>Технологии:</strong> PHP, JSON-хранилище, vanilla JavaScript.</p>
                <?php endif; ?>
                <div class="about-github">
                    <a href="<?= e(GITHUB_URL) ?>" target="_blank" rel="noopener" class="btn-primary github-btn">
                        GitHub →
                    </a>
                </div>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
