<?php
session_start();
// Очищаем все сообщения об ошибках при прямом входе на страницу
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !isset($_GET['error'])) {
    unset($_SESSION['error']);
}
// Сохраняем ошибку в сессию, если она пришла через GET
if (isset($_GET['error'])) {
    $_SESSION['error'] = $_GET['error'];
    // Редиректим на эту же страницу, но без параметров
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-form">
        <h1>Авторизация</h1>
        <?php if (isset($_SESSION['error']) && $_SESSION['error'] === 'invalid_credentials'): ?>
            <div class="error-message">Неверный логин или пароль</div>
            <?php unset($_SESSION['error']); // Очищаем сообщение об ошибке после показа ?>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <input type="text" name="login" placeholder="логин" required>
            <input type="password" name="password" placeholder="пароль" required>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
