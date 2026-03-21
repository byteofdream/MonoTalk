<?php
/**
 * MonoTalk - 404 Not Found
 */

http_response_code(404);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$pageTitle = '404';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container error-page">
    <div class="error-card">
        <div class="error-code">404</div>
        <h1><?= $lang === 'en' ? 'Page not found' : 'Страница не найдена' ?></h1>
        <p>
            <?= $lang === 'en'
                ? 'The page you are looking for does not exist or was moved.'
                : 'Страница, которую вы ищете, не существует или была перемещена.' ?>
        </p>
        <div class="error-actions">
            <a href="<?= e(BASE_URL) ?>index.php" class="btn-primary">
                <?= $lang === 'en' ? 'Go to home' : 'На главную' ?>
            </a>
            <a href="<?= e(BASE_URL) ?>news.php" class="btn-secondary">
                <?= $lang === 'en' ? 'View news' : 'Смотреть новости' ?>
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
