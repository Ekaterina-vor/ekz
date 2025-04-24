<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'waiter') {
    header('Location: ../login.php');
    exit;
}

// Получаем имя текущего пользователя
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Обработка создания заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $table_number = trim($_POST['table_number']);
    $items = $_POST['items'];
    $quantities = $_POST['quantities'];
    $prices = $_POST['prices'];

    if (empty($table_number) || empty($items) || !is_array($items)) {
        $error = 'Заполните все поля и добавьте хотя бы одно блюдо';
    } else {
        try {
            $pdo->beginTransaction();

            // Создаем заказ
            $stmt = $pdo->prepare("INSERT INTO orders (table_number, waiter_id) VALUES (?, ?)");
            $stmt->execute([$table_number, $_SESSION['user_id']]);
            $order_id = $pdo->lastInsertId();

            // Добавляем позиции заказа
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, dish_name, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $index => $dish_name) {
                if (!empty($dish_name) && isset($quantities[$index]) && isset($prices[$index])) {
                    $stmt->execute([$order_id, $dish_name, $quantities[$index], $prices[$index]]);
                }
            }

            $pdo->commit();
            header('Location: orders.php');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Ошибка при создании заказа: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Создание заказа</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="nav-links">
            <a href="orders.php" class="nav-link">Заказы</a>
        </div>
        <a href="../logout.php" class="logout-btn">выйти</a>
    </div>

    <div class="content">
        <div class="header-actions">
            <a href="orders.php" class="back-btn">← Назад к заказам</a>
        </div>

        <h2 class="page-title">Создание заказа</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="create-order-form">
            <form method="POST" id="orderForm">
                <div class="form-group">
                    <label for="table_number">Номер стола:</label>
                    <input type="number" id="table_number" name="table_number" required min="1" class="form-input" 
                           value="<?php echo isset($_POST['table_number']) ? htmlspecialchars($_POST['table_number']) : ''; ?>">
                </div>

                <div id="orderItems">
                    <h3>Позиции заказа</h3>
                    <div class="order-item">
                        <div class="form-group">
                            <label>Название блюда:</label>
                            <input type="text" name="items[]" required class="form-input">
                        </div>
                        <div class="form-group">
                            <label>Количество:</label>
                            <input type="number" name="quantities[]" required min="1" class="form-input" value="1">
                        </div>
                        <div class="form-group">
                            <label>Цена:</label>
                            <input type="number" name="prices[]" required min="0" step="0.01" class="form-input">
                        </div>
                    </div>
                </div>

                <button type="button" onclick="addOrderItem()" class="add-item-btn">Добавить блюдо</button>
                <button type="submit" name="create_order" class="submit-btn">Создать заказ</button>
            </form>
        </div>
    </div>

    <script>
    function addOrderItem() {
        const container = document.getElementById('orderItems');
        const newItem = document.createElement('div');
        newItem.className = 'order-item';
        newItem.innerHTML = `
            <div class="form-group">
                <label>Название блюда:</label>
                <input type="text" name="items[]" required class="form-input">
            </div>
            <div class="form-group">
                <label>Количество:</label>
                <input type="number" name="quantities[]" required min="1" class="form-input" value="1">
            </div>
            <div class="form-group">
                <label>Цена:</label>
                <input type="number" name="prices[]" required min="0" step="0.01" class="form-input">
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="remove-item-btn">Удалить</button>
        `;
        container.appendChild(newItem);
    }
    </script>
</body>
</html> 