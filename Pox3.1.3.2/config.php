<?php
session_start();

// Включаем отображение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__FILE__));

// Страницы, доступные без авторизации
$public_pages = ['login.php', 'logout.php', 'create_users_table.php', 'check_database.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $public_pages) && !isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit;
}

// Локальная БД для пользователей админки
$local_host = 'localhost';
$local_db   = 'Bd_belov';
$local_user = 'admin';
$local_pass = 'admin';

try {
    $local_pdo = new PDO("mysql:host=$local_host;dbname=$local_db;charset=utf8mb4", $local_user, $local_pass);
    $local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Если пользователь авторизован, подключаемся к удаленным БД
    if (isset($_SESSION['authenticated'])) {
        // Получаем данные пользователя из таблицы users (админка)
        $stmt = $local_pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            session_destroy();
            header('Location: login.php');
            exit;
        }
        
        // Параметры подключения к удаленным БД
        $host = '134.90.167.42';
        $port = '10306';
        
        $belov_user = 'Belov';
        $belov_pass = 'B6EQr.7PN]*u8Ffn';
        
        $chesnokov_user = 'Chesnokov';
        $chesnokov_pass = 'CAaSRQUQ/5qvp29f';
        
        // 1. Подключение к БД каталога (project_Chesnokov)
        $catalog_dsn = "mysql:host=$host;port=$port;dbname=project_Chesnokov;charset=utf8mb4";
        $catalog_pdo = new PDO($catalog_dsn, $chesnokov_user, $chesnokov_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // 2. Подключение к БД заказов (project_Belov)
        $orders_dsn = "mysql:host=$host;port=$port;dbname=project_Belov;charset=utf8mb4";
        $orders_pdo = new PDO($orders_dsn, $belov_user, $belov_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Создаём таблицы в БД заказов, если их нет
        createOrderTables($orders_pdo);
        
        // Для обратной совместимости
        $pdo = $catalog_pdo;
    }
} catch (PDOException $e) {
    die("<div class='alert alert-error'>Ошибка подключения: " . $e->getMessage() . "</div>");
}

// Функция создания таблиц заказов (копия из user_config.php)
function createOrderTables($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS clients (
            client_id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(200) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            phone VARCHAR(20) NOT NULL,
            address TEXT,
            user_id INT,
            registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT true,
            INDEX (email),
            INDEX (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            order_id INT AUTO_INCREMENT PRIMARY KEY,
            client_id INT NOT NULL,
            order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            total_amount DECIMAL(10, 2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT NOT NULL,
            estimated_delivery_date DATE,
            actual_delivery_date DATE,
            notes TEXT,
            FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE,
            INDEX (client_id),
            INDEX (status),
            INDEX (order_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            order_item_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            unit_price DECIMAL(10, 2) NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            INDEX (order_id),
            INDEX (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_services (
            order_service_id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            service_id INT NOT NULL,
            service_name VARCHAR(200) NOT NULL,
            service_price DECIMAL(10, 2) NOT NULL,
            quantity INT NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
            INDEX (order_id),
            INDEX (service_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

// ========== ДОБАВЛЕНО ДЛЯ ИГРЫ: управление таймерами ==========
if (isset($_SESSION['authenticated']) && !isset($_SESSION['game_state'])) {
    $_SESSION['game_state'] = 'work';
    $_SESSION['game_state_start'] = time();
    $_SESSION['work_time_seconds'] = 45 * 60;
    $_SESSION['break_time_seconds'] = 5 * 60;
    $_SESSION['forced_break'] = false;
}

function checkGameStates() {
    $state = $_SESSION['game_state'];
    $elapsed = time() - $_SESSION['game_state_start'];
    $needMessage = false;
    $message = '';
    $forceOpenGame = false;
    $isAdmin = true; // всегда админ в config.php

    if ($state == 'work' && $elapsed >= $_SESSION['work_time_seconds']) {
        $_SESSION['game_state'] = 'break';
        $_SESSION['game_state_start'] = time();
        $_SESSION['forced_break'] = true;
        $needMessage = true;
        $forceOpenGame = true;
        $message = '🌸 Пора отдохнуть! Сыграйте в игру 🌸';
    } elseif ($state == 'break' && $elapsed >= $_SESSION['break_time_seconds']) {
        $_SESSION['game_state'] = 'work';
        $_SESSION['game_state_start'] = time();
        $_SESSION['forced_break'] = false;
        $needMessage = true;
        $forceOpenGame = false;
        $message = '💼 Пора работать!';
    }

    return [
        'need_message' => $needMessage,
        'message' => $message,
        'force_open_game' => $forceOpenGame
    ];
}

if (isset($_SESSION['authenticated'])) {
    $stateResult = checkGameStates();
    $_SESSION['game_state_result'] = $stateResult;
}
?>