<?php
/**
 * MonoTalk - Страница входа
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'Вход';
$redirect = $_GET['redirect'] ?? BASE_URL . 'index.php';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container auth-page">
    <div class="auth-card">
        <h1>Вход</h1>
        <form id="loginForm" class="auth-form">
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn-primary btn-block">Войти</button>
        </form>
        <p class="auth-link">Нет аккаунта? <a href="<?= e(BASE_URL) ?>register.php">Регистрация</a></p>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
