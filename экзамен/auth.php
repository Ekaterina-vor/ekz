<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['login']);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === $user['password']) { // В реальном проекте нужно использовать password_hash() и password_verify()
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];

            // Перенаправление в зависимости от роли
            switch ($user['role']) {
                case 'admin':
                    header('Location: admin/dashboard.php');
                    break;
                case 'cook':
                    header('Location: cook/orders.php');
                    break;
                case 'waiter':
                    header('Location: waiter/orders.php');
                    break;
                default:
                    header('Location: login.php?error=invalid_role');
            }
            exit;
        } else {
            header('Location: login.php?error=invalid_credentials');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: login.php?error=database_error');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?> 