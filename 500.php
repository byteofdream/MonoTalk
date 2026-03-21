<?php
/**
 * MonoTalk - 500 Server Error
 */

http_response_code(500);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$pageTitle = '500';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container error-page">
    <div class="error-card">
        <div class="error-code">500</div>
        <h1><?= $lang === 'en' ? 'Server error' : 'Ошибка сервера' ?></h1>
        <p>
            <?= $lang === 'en'
                ? 'Something went wrong on our side. Please try again later.'
                : 'Что-то пошло не так на нашей стороне. Попробуйте позже.' ?>
        </p>
        <div class="error-actions">
            <a href="<?= e(BASE_URL) ?>index.php" class="btn-primary">
                <?= $lang === 'en' ? 'Go to home' : 'На главную' ?>
            </a>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
