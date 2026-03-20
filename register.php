<?php
/**
 * MonoTalk - Страница регистрации
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}

$pageTitle = 'Регистрация';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container auth-page">
    <div class="auth-card">
        <h1>Регистрация</h1>
        <form id="registerForm" class="auth-form">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" required minlength="3" autocomplete="username" placeholder="минимум 3 символа">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">Пароль *</label>
                <input type="password" id="password" name="password" required minlength="6" autocomplete="new-password" placeholder="минимум 6 символов">
            </div>
            <button type="submit" class="btn-primary btn-block">Зарегистрироваться</button>
        </form>
        <p class="auth-link">Уже есть аккаунт? <a href="<?= e(BASE_URL) ?>login.php">Войти</a></p>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
