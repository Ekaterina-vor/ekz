<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Получаем имя текущего пользователя
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем параметры фильтрации по дате
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Формируем SQL запрос с фильтрацией по дате
$sql = "SELECT o.*, u.full_name as waiter_name 
        FROM orders o
        LEFT JOIN users u ON o.waiter_id = u.id 
        WHERE DATE(o.created_at) BETWEEN :start_date AND :end_date
        ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'start_date' => $start_date,
    'end_date' => $end_date
]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - Администратор</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="nav-links">
            <a href="employees.php" class="nav-link">сотрудники</a>
            <a href="shifts.php" class="nav-link">смены</a>
            <a href="orders.php" class="nav-link active">заказы</a>
        </div>
        <a href="../logout.php" class="logout-btn">выйти</a>
    </div>

    <div class="content">
        <div class="filter-container">
            <div class="filter-title" onclick="toggleFilter()">
                Фильтры
            </div>
            <div class="filter-content" id="filterContent">
                <form method="GET" class="filter-form">
                    <div class="filter-section">
                        <h3>Период</h3>
                        <div class="date-inputs">
                            <div class="date-input">
                                <input type="date" name="start_date" value="<?php echo $start_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="date-input">
                                <input type="date" name="end_date" value="<?php echo $end_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="filter-buttons">
                        <button type="submit" class="filter-submit">Применить</button>
                        <button type="button" class="filter-reset" onclick="resetFilters()">Сбросить</button>
                    </div>
                </form>
            </div>
        </div>

        <h2 class="page-title">Заказы</h2>

        <div class="orders-container">
            <?php if (empty($orders)): ?>
                <p class="no-orders">Заказов за выбранный период не найдено</p>
            <?php else: ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-card">
                        <h3>Заказ <?php echo htmlspecialchars($order['id']); ?></h3>
                        <p>Официант: <?php echo htmlspecialchars($order['waiter_name']); ?></p>
                        <p>Статус: <?php echo htmlspecialchars($order['status']); ?></p>
                        <p>Готовность: <?php echo $order['is_ready'] ? 'Готов' : 'В процессе'; ?></p>
                        <p>Создан: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function toggleFilter() {
        const content = document.getElementById('filterContent');
        content.classList.toggle('active');
    }

    function resetFilters() {
        window.location.href = 'orders.php';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        const dateInputs = document.querySelectorAll('input[type="date"]');
        dateInputs.forEach(input => {
            input.max = today;
        });
    });
    </script>
</body>
</html> 