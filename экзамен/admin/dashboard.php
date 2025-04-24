<?php
session_start();
require_once '../config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Получаем имя текущего пользователя
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="nav-links">
            <a href="employees.php" class="nav-link">Сотрудники</a>
            <a href="shifts.php" class="nav-link">Смены</a>
            <a href="orders.php" class="nav-link">Заказы</a>
        </div>
        <a href="../logout.php" class="logout-btn">выйти</a>
    </div>

    <div class="content">
        <h2 class="page-title">Панель администратора</h2>
        <p>Добро пожаловать, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</p>
    </div>
</body>
</html> 