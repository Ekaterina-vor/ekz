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

// Обработка добавления смены
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_shift'])) {
    $employee_id = $_POST['employee_id'];
    $shift_date = $_POST['shift_date'];
    $shift_type = $_POST['shift_type'];

    try {
        $stmt = $pdo->prepare("INSERT INTO shifts (employee_id, shift_date, shift_type) VALUES (?, ?, ?)");
        $stmt->execute([$employee_id, $shift_date, $shift_type]);
        header('Location: shifts.php');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Код ошибки дубликата
            $error = 'Смена для этого сотрудника на указанную дату уже существует';
        } else {
            $error = 'Ошибка при добавлении смены: ' . $e->getMessage();
        }
    }
}

// Обработка удаления смены
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_shift'])) {
    $shift_id = $_POST['shift_id'];
    
    try {
        // Начинаем транзакцию
        $pdo->beginTransaction();
        
        // Сначала удаляем все заказы, связанные с этой сменой
        $stmt = $pdo->prepare("DELETE FROM orders WHERE shift_id = ?");
        $stmt->execute([$shift_id]);
        
        // Затем удаляем саму смену
        $stmt = $pdo->prepare("DELETE FROM shifts WHERE id = ?");
        $stmt->execute([$shift_id]);
        
        // Завершаем транзакцию
        $pdo->commit();
        
        header('Location: shifts.php');
        exit;
    } catch (PDOException $e) {
        // Если произошла ошибка, откатываем изменения
        $pdo->rollBack();
        $error = 'Ошибка при удалении смены: ' . $e->getMessage();
    }
}

// Получаем список сотрудников для выпадающего списка
$stmt = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('waiter', 'cook')");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем список смен с количеством заказов
$stmt = $pdo->query("
    SELECT s.*, u.full_name, u.role,
           (SELECT COUNT(*) FROM orders o WHERE o.shift_id = s.id) as orders_count
    FROM shifts s 
    JOIN users u ON s.employee_id = u.id 
    ORDER BY s.shift_date DESC, s.shift_type
");
$shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление сменами - Администратор</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="nav-links">
            <a href="employees.php" class="nav-link">Сотрудники</a>
            <a href="shifts.php" class="nav-link active">Смены</a>
            <a href="orders.php" class="nav-link">Заказы</a>
        </div>
        <a href="../logout.php" class="logout-btn">выйти</a>
    </div>

    <div class="content">
        <h2 class="page-title">Управление сменами</h2>

        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="shifts-container">
            <!-- Форма добавления смены -->
            <div class="assign-shift-form">
                <h3>Добавить смену</h3>
                <form method="POST" class="shift-form">
                    <div class="form-group">
                        <label>Сотрудник:</label>
                        <select name="employee_id" class="shift-select" required>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['full_name']) . ' (' . htmlspecialchars($employee['role']) . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Дата:</label>
                        <input type="date" name="shift_date" class="shift-input" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Тип смены:</label>
                        <select name="shift_type" class="shift-select" required>
                            <option value="утро">Утро</option>
                            <option value="вечер">Вечер</option>
                        </select>
                    </div>
                    <button type="submit" name="add_shift" class="shift-submit">Добавить смену</button>
                </form>
            </div>

            <!-- Таблица смен -->
            <div class="shifts-table">
                <table>
                    <thead>
                        <tr>
                            <th>Дата</th>
                            <th>Смена</th>
                            <th>Сотрудник</th>
                            <th>Должность</th>
                            <th>Заказов</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $shift): ?>
                            <tr>
                                <td><?php echo date('d.m.Y', strtotime($shift['shift_date'])); ?></td>
                                <td><?php echo htmlspecialchars($shift['shift_type']); ?></td>
                                <td><?php echo htmlspecialchars($shift['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($shift['role']); ?></td>
                                <td><?php echo $shift['orders_count']; ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="shift_id" value="<?php echo $shift['id']; ?>">
                                        <button type="submit" name="delete_shift" class="action-btn" onclick="return confirm('Вы уверены, что хотите удалить эту смену? Все связанные заказы также будут удалены.')">Удалить</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Устанавливаем минимальную дату для поля выбора даты
        const dateInput = document.querySelector('input[type="date"]');
        dateInput.min = new Date().toISOString().split('T')[0];
    });
    </script>
</body>
</html> 