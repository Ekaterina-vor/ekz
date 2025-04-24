-- Удаляем существующие таблицы
DROP TABLE IF EXISTS shifts;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;

-- Создаем таблицу users
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    login VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cook', 'waiter') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем администратора по умолчанию
INSERT INTO users (full_name, login, password, role) VALUES 
('Администратор', 'admin', 'admin', 'admin');

-- Добавляем столбцы login и password, если их нет
ALTER TABLE users
ADD COLUMN IF NOT EXISTS login VARCHAR(50) UNIQUE,
ADD COLUMN IF NOT EXISTS password VARCHAR(255);

-- Обновляем администратора
UPDATE users 
SET login = 'admin', password = 'admin' 
WHERE role = 'admin';

-- Создаем таблицу orders если её нет
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shift_id INT NOT NULL,
    waiter_id INT NOT NULL,
    status ENUM('принят', 'готовится', 'готов', 'оплачен', 'закрыт') NOT NULL DEFAULT 'принят',
    is_ready TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (waiter_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаем таблицу order_items если её нет
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    dish_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создаем таблицу shifts если её нет
CREATE TABLE IF NOT EXISTS shifts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    shift_date DATE NOT NULL,
    shift_type ENUM('утро', 'вечер') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_shift (employee_id, shift_date, shift_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Добавляем тестовые заказы
INSERT INTO orders (id, shift_id, waiter_id, status, is_ready, created_at) VALUES
(3, 1, 3, 'принят', 0, '2025-04-01 09:59:23'),
(4, 1, 3, 'оплачен', 1, '2025-04-24 09:59:23'),
(5, 2, 3, 'принят', 0, '2025-04-24 09:59:23'),
(6, 3, 3, 'закрыт', 1, '2025-03-25 09:59:23'),
(7, 4, 3, 'оплачен', 1, '2025-04-24 09:59:23');

-- Добавляем позиции в заказы
INSERT INTO order_items (order_id, dish_name, quantity, price) VALUES
-- Заказ id=3 (принят)
(3, 'Борщ', 2, 250.00),
(3, 'Котлета по-киевски', 1, 350.00),
(3, 'Компот', 2, 80.00),

-- Заказ id=4 (оплачен)
(4, 'Цезарь с курицей', 1, 420.00),
(4, 'Стейк рибай', 2, 1200.00),
(4, 'Картофельное пюре', 2, 150.00),

-- Заказ id=5 (принят)
(5, 'Грибной суп', 1, 280.00),
(5, 'Паста карбонара', 1, 450.00),
(5, 'Тирамису', 1, 320.00),

-- Заказ id=6 (закрыт)
(6, 'Греческий салат', 1, 350.00),
(6, 'Пицца Маргарита', 1, 500.00),
(6, 'Чай', 2, 100.00),

-- Заказ id=7 (оплачен)
(7, 'Суп лапша', 1, 200.00),
(7, 'Бефстроганов', 1, 580.00),
(7, 'Морс', 2, 120.00);