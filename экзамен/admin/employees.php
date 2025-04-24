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

// Обработка удаления сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_employee'])) {
    $employee_id = $_POST['employee_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$employee_id]);
        header('Location: employees.php');
        exit;
    } catch (PDOException $e) {
        $error = 'Ошибка при удалении сотрудника';
    }
}

// Обработка редактирования сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_employee'])) {
    $employee_id = $_POST['employee_id'];
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($full_name) || empty($username) || empty($role)) {
        $error = 'Все поля, кроме пароля, должны быть заполнены';
    } else {
        try {
            // Проверяем, не занят ли логин другим пользователем
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $check_stmt->execute([$username, $employee_id]);
            if ($check_stmt->fetch()) {
                $error = 'Пользователь с таким логином уже существует';
            } else {
                // Если пароль не был изменен, не обновляем его
                if (empty($password)) {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, role = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $role, $employee_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, username = ?, password = ?, role = ? WHERE id = ?");
                    $stmt->execute([$full_name, $username, $password, $role, $employee_id]);
                }
                header('Location: employees.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при обновлении данных сотрудника';
        }
    }
}

// Обработка добавления нового сотрудника
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($full_name) || empty($username) || empty($password) || empty($role)) {
        $error = 'Все поля должны быть заполнены';
    } else {
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->fetch()) {
            $error = 'Пользователь с таким логином уже существует';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO users (full_name, username, password, role) VALUES (?, ?, ?, ?)");
                $stmt->execute([$full_name, $username, $password, $role]);
                
                if ($stmt->rowCount() > 0) {
                    header('Location: employees.php');
                    exit;
                } else {
                    $error = 'Не удалось добавить сотрудника';
                }
            } catch (PDOException $e) {
                $error = 'Ошибка при добавлении сотрудника: ' . $e->getMessage();
            }
        }
    }
}

// Получаем список всех сотрудников
$stmt = $pdo->query("SELECT * FROM users WHERE role != 'admin' ORDER BY role, full_name");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получаем данные сотрудника для редактирования
$edit_employee = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$_GET['edit']]);
    $edit_employee = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление сотрудниками</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="header">
        <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
        <div class="nav-links">
            <a href="employees.php" class="nav-link active">Сотрудники</a>
            <a href="shifts.php" class="nav-link">Смены</a>
            <a href="orders.php" class="nav-link">Заказы</a>
        </div>
        <a href="../logout.php" class="logout-btn">выйти</a>
    </div>

    <div class="content">
        <h2 class="page-title">Сотрудники</h2>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php foreach ($employees as $employee): ?>
            <div class="employee-card">
                <div class="employee-info">
                    <h3><?php echo htmlspecialchars($employee['full_name']); ?></h3>
                    <p>Роль: <?php echo htmlspecialchars($employee['role']); ?></p>
                    <p>Логин: <?php echo htmlspecialchars($employee['username']); ?></p>
                </div>
                <div class="employee-actions">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="employee_id" value="<?php echo $employee['id']; ?>">
                        <button type="submit" name="delete_employee" class="action-btn" onclick="return confirm('Вы уверены, что хотите удалить этого сотрудника?')">удаление</button>
                    </form>
                    <a href="?edit=<?php echo $employee['id']; ?>" class="action-btn">редактирование</a>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Форма редактирования/добавления сотрудника -->
        <div class="add-employee-form">
            <h3><?php echo $edit_employee ? 'Редактировать сотрудника' : 'Добавить нового сотрудника'; ?></h3>
            <form method="POST" class="employee-form">
                <?php if ($edit_employee): ?>
                    <input type="hidden" name="employee_id" value="<?php echo $edit_employee['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="full_name">ФИО:</label>
                    <input type="text" id="full_name" name="full_name" required class="form-input" 
                           value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['full_name']) : 
                                      (isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''); ?>">
                </div>
                <div class="form-group">
                    <label for="username">Логин:</label>
                    <input type="text" id="username" name="username" required class="form-input" 
                           value="<?php echo $edit_employee ? htmlspecialchars($edit_employee['username']) : 
                                      (isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Пароль:<?php echo $edit_employee ? ' (оставьте пустым, чтобы не менять)' : ''; ?></label>
                    <input type="password" id="password" name="password" <?php echo $edit_employee ? '' : 'required'; ?> class="form-input">
                </div>
                <div class="form-group">
                    <label for="role">Роль:</label>
                    <select id="role" name="role" required class="form-input">
                        <option value="">Выберите роль</option>
                        <option value="cook" <?php echo ($edit_employee && $edit_employee['role'] === 'cook') || 
                                                      (!$edit_employee && isset($_POST['role']) && $_POST['role'] === 'cook') ? 'selected' : ''; ?>>Повар</option>
                        <option value="waiter" <?php echo ($edit_employee && $edit_employee['role'] === 'waiter') || 
                                                       (!$edit_employee && isset($_POST['role']) && $_POST['role'] === 'waiter') ? 'selected' : ''; ?>>Официант</option>
                    </select>
                </div>
                <button type="submit" name="<?php echo $edit_employee ? 'edit_employee' : 'add_employee'; ?>" class="submit-btn">
                    <?php echo $edit_employee ? 'Сохранить изменения' : 'Добавить сотрудника'; ?>
                </button>
                <?php if ($edit_employee): ?>
                    <a href="employees.php" class="cancel-btn">Отмена</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</body>
</html> 