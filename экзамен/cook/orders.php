<?php
session_start();
require_once '../config.php';

// Проверка авторизации и роли
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'cook') {
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
$ready_filter = isset($_GET['ready_filter']) ? $_GET['ready_filter'] : 'все';

// Формируем SQL запрос с фильтрацией
$sql = "SELECT o.*, u.full_name as waiter_name 
        FROM orders o
        LEFT JOIN users u ON o.waiter_id = u.id 
        WHERE o.status = 'принят'
        AND DATE(o.created_at) BETWEEN :start_date AND :end_date";

if ($ready_filter !== 'все') {
    $sql .= " AND o.is_ready = " . ($ready_filter === 'готов' ? '1' : '0');
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'start_date' => $start_date,
    'end_date' => $end_date
]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка изменения статуса готовности
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $is_ready = $_POST['is_ready'] ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE orders SET is_ready = ? WHERE id = ?");
    $stmt->execute([$is_ready, $order_id]);
    
    // Сохраняем параметры фильтрации при обновлении
    header('Location: orders.php?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '&ready_filter=' . urlencode($ready_filter));
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы - Повар</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
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
                    <div class="filter-section">
                        <h3>Статус готовности</h3>
                        <select name="ready_filter" class="filter-select">
                            <option value="все" <?php echo $ready_filter === 'все' ? 'selected' : ''; ?>>Все</option>
                            <option value="готов" <?php echo $ready_filter === 'готов' ? 'selected' : ''; ?>>Готовые</option>
                            <option value="не готов" <?php echo $ready_filter === 'не готов' ? 'selected' : ''; ?>>Не готовые</option>
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
                        <h3>Заказ <?php echo htmlspecialchars($order['id']); ?></h3>
                        <p>Официант: <?php echo htmlspecialchars($order['waiter_name']); ?></p>
                        <p>Создан: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></p>
                        <form method="POST" class="ready-form">
                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                            <input type="hidden" name="is_ready" value="<?php echo $order['is_ready'] ? '0' : '1'; ?>">
                            <button type="submit" class="ready-toggle <?php echo $order['is_ready'] ? 'ready' : 'not-ready'; ?>">
                                <?php echo $order['is_ready'] ? 'готов' : 'не готов'; ?>
                            </button>
                        </form>
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