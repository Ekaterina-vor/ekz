<?php
session_start();
require_once '../config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'waiter') {
    header('Location: ../login.php');
    exit;
}

// Получаем имя текущего пользователя
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем параметры фильтрации
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status_filter']) ? $_GET['status_filter'] : 'все';

// Формируем условия для фильтрации
$where_conditions = ['o.waiter_id = ?'];
$params = [$_SESSION['user_id']];

// Добавляем условие для дат
$where_conditions[] = 'DATE(o.created_at) BETWEEN ? AND ?';
$params[] = $start_date;
$params[] = $end_date;

// Добавляем условие для статуса
if ($status_filter !== 'все') {
    $where_conditions[] = 'o.status = ?';
    $params[] = $status_filter;
}

// Получаем список заказов официанта
$stmt = $pdo->prepare("
    SELECT o.*, 
           GROUP_CONCAT(CONCAT(oi.quantity, 'x ', oi.dish_name, ' (', oi.price, ' руб.)') SEPARATOR '\n') as items,
           SUM(oi.quantity * oi.price) as total_amount
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE " . implode(' AND ', $where_conditions) . "
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка изменения статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND waiter_id = ?");
    $stmt->execute([$status, $order_id, $_SESSION['user_id']]);
    
    // Сохраняем параметры фильтрации при обновлении
    header('Location: orders.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - Официант</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="header-actions">
            <a href="create_order.php" class="create-order-btn">создать заказ</a>
            <a href="../logout.php" class="logout-btn">выйти</a>
        </div>
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
                                <input type="date" name="start_date" value="<?php echo date('Y-m-d', strtotime('-1 month')); ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="date-input">
                                <input type="date" name="end_date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="filter-section">
                        <h3>Статус заказа</h3>
                        <select name="status_filter" class="filter-select">
                            <option value="все" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'все' ? 'selected' : ''; ?>>Все</option>
                            <option value="принят" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'принят' ? 'selected' : ''; ?>>Принятые</option>
                            <option value="оплачен" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'оплачен' ? 'selected' : ''; ?>>Оплаченные</option>
                            <option value="закрыт" <?php echo isset($_GET['status_filter']) && $_GET['status_filter'] === 'закрыт' ? 'selected' : ''; ?>>Закрытые</option>
                        </select>
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
                        <h3>Заказ #<?php echo htmlspecialchars($order['id']); ?></h3>
                        <p>Статус: <?php echo htmlspecialchars($order['status']); ?></p>
                        <p>Создан: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                        <div class="order-items">
                            <h4>Позиции заказа:</h4>
                            <pre><?php echo htmlspecialchars($order['items']); ?></pre>
                            <p class="total-amount">Итого: <?php echo number_format($order['total_amount'], 2); ?> руб.</p>
                        </div>
                        <?php if ($order['status'] === 'готов'): ?>
                            <form method="POST" class="status-form">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <button type="submit" name="mark_paid" class="status-btn paid">Отметить как оплаченный</button>
                            </form>
                        <?php endif; ?>
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