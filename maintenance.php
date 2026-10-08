<?php
/**
 * MonoTalk - Страница режима обслуживания (техработы)
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/lang.php';

$lang = getLang();
$isRu = $lang === 'ru';

// Если режим обслуживания выключен — отправляем на главную
if (!isMaintenanceEnabled()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

// Verified-пользователи (с галочкой) ходят по сайту свободно и
// могут открыть /maintenance.php вручную для просмотра.

$pageTitle = $isRu ? 'Техработы' : 'Maintenance';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container auth-page">
    <div class="auth-card maintenance-card">
        <div class="maintenance-icon" aria-hidden="true">🔧</div>
        <h1><?= $isRu ? 'Мы подкручиваем винтики' : 'Tightening some bolts' ?></h1>

        <p class="maintenance-text">
            <?= $isRu
                ? 'Мотор форума заглушен: обновляем ядро, вытираем пыль с кеша и проверяем пару проводков. Уже собираем всё обратно.'
                : 'The forum engine is off: we\'re updating the core, dusting off the cache and checking a couple of wires. Putting it all back together now.' ?>
        </p>

        <div class="maintenance-spinner" aria-hidden="true"></div>

        <p class="maintenance-sub">
            <?= $isRu
                ? 'Обычно хватает пары минут — как раз выпейте кофе.'
                : 'Usually just a couple of minutes — perfect time to grab a coffee.' ?>
        </p>

        <p class="maintenance-hint">
            <?= $isRu
                ? 'Спасибо за терпение. И не забудьте нажать CTRL + SHIFT + R.'
                : 'Thanks for your patience. And don\'t forget to hit CTRL + SHIFT + R.' ?>
        </p>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
