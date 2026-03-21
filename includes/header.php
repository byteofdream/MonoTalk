<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/lang.php';

$currentLang = getLang();
if (!isset($pageTitle)) $pageTitle = 'MonoTalk';
$currentUrl = htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/');
$currentTheme = getTheme();
?>
<!DOCTYPE html>
<html lang="<?= $currentLang === 'en' ? 'en' : 'ru' ?>" data-theme="<?= e($currentTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - MonoTalk</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(BASE_URL) ?>favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(BASE_URL) ?>assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="<?= e(BASE_URL) ?>index.php" class="logo">
            <img src="<?= e(BASE_URL) ?>assets/icons/brand.svg" alt="" class="logo-mark logo-mark--light">
            <img src="<?= e(BASE_URL) ?>assets/icons/brand-white.svg" alt="" class="logo-mark logo-mark--dark">
            <span>MonoTalk</span>
        </a>
        <form class="nav-search" action="<?= e(BASE_URL) ?>search.php" method="get">
            <input type="search" name="q" placeholder="<?= e(t('nav_search_placeholder')) ?>" minlength="2" class="nav-search-input">
            <button type="submit" class="nav-search-btn" title="<?= e(t('nav_search_placeholder')) ?>">
                <img src="<?= e(BASE_URL) ?>assets/icons/search.svg" alt="" class="nav-search-icon nav-search-icon--light">
                <img src="<?= e(BASE_URL) ?>assets/icons/search-white.svg" alt="" class="nav-search-icon nav-search-icon--dark">
            </button>
        </form>
        <div class="nav-links">
            <a href="<?= e(BASE_URL) ?>index.php"><?= e(t('nav_home')) ?></a>
            <a href="<?= e(BASE_URL) ?>news.php">
                <img src="<?= e(BASE_URL) ?>assets/icons/news.svg" alt="" class="nav-icon nav-icon--light">
                <img src="<?= e(BASE_URL) ?>assets/icons/news-white.svg" alt="" class="nav-icon nav-icon--dark">
                <?= e(t('nav_news')) ?>
            </a>
            <a href="<?= e(BASE_URL) ?>settings.php">
                <img src="<?= e(BASE_URL) ?>assets/icons/settings.svg" alt="" class="nav-icon nav-icon--light">
                <img src="<?= e(BASE_URL) ?>assets/icons/settings-white.svg" alt="" class="nav-icon nav-icon--dark">
                <?= e(t('nav_settings')) ?>
            </a>
            <div class="lang-switcher">
                <button class="lang-btn" aria-haspopup="true" aria-expanded="false" title="Язык / Language">🌐 <?= $currentLang === 'en' ? 'EN' : 'RU' ?></button>
                <div class="lang-dropdown">
                    <a href="<?= e(BASE_URL) ?>api/set_language.php?lang=ru&redirect=<?= urlencode($currentUrl) ?>"><?= e(t('lang_ru')) ?></a>
                    <a href="<?= e(BASE_URL) ?>api/set_language.php?lang=en&redirect=<?= urlencode($currentUrl) ?>"><?= e(t('lang_en')) ?></a>
                </div>
            </div>
            <?php if (isLoggedIn()): ?>
                <a href="<?= e(BASE_URL) ?>create.php">
                    <img src="<?= e(BASE_URL) ?>assets/icons/plus-circle.svg" alt="" class="nav-icon nav-icon--light">
                    <img src="<?= e(BASE_URL) ?>assets/icons/plus-circle-white.svg" alt="" class="nav-icon nav-icon--dark">
                    <?= e(t('nav_create')) ?>
                </a>
                <div class="dropdown">
                    <button class="dropdown-btn" aria-haspopup="true" aria-expanded="false">
                        <?php $u = getCurrentUser(); ?>
                        <?php if (!empty($u['avatar'])): ?>
                            <img src="<?= e(strpos($u['avatar'], 'http') === 0 ? $u['avatar'] : BASE_URL . $u['avatar']) ?>" alt="" class="nav-avatar">
                        <?php else: ?>
                            <span class="nav-avatar-placeholder"><?= e(mb_substr($u['username'], 0, 1)) ?></span>
                        <?php endif; ?>
                        <span class="dropdown-arrow">▼</span>
                    </button>
                    <div class="dropdown-menu">
                        <a href="<?= e(BASE_URL) ?>profile.php"><?= e(t('nav_profile')) ?></a>
                        <a href="<?= e(BASE_URL) ?>api/logout.php"><?= e(t('nav_logout')) ?></a>
                    </div>
                </div>
            <?php else: ?>
                <a href="<?= e(BASE_URL) ?>login.php"><?= e(t('nav_login')) ?></a>
                <a href="<?= e(BASE_URL) ?>register.php" class="btn-primary"><?= e(t('nav_register')) ?></a>
            <?php endif; ?>
        </div>
    </nav>
