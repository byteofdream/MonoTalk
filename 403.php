<?php
/**
 * MonoTalk - 403 Forbidden
 */

http_response_code(403);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$pageTitle = '403';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container error-page">
    <div class="error-card">
        <div class="error-code">403</div>
        <h1><?= $lang === 'en' ? 'Access denied' : 'Доступ запрещен' ?></h1>
        <p>
            <?= $lang === 'en'
                ? 'You do not have permission to access this page.'
                : 'У вас нет прав для доступа к этой странице.' ?>
        </p>
        <div class="error-actions">
            <a href="<?= e(BASE_URL) ?>index.php" class="btn-primary">
                <?= $lang === 'en' ? 'Go to home' : 'На главную' ?>
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
