<?php
// Включаем отображение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('ROOT_PATH', dirname(__FILE__));

// Страницы, доступные без авторизации
$public_pages = ['user_login.php', 'user_register.php', 'user_logout.php'];
$current_page = basename($_SERVER['PHP_SELF']);

// Параметры подключения к удаленным БД
$host = '134.90.167.42';
$port = '10306';

// Данные для подключения к разным БД
$belov_user = 'Belov';
$belov_pass = 'B6EQr.7PN]*u8Ffn';

$chesnokov_user = 'Chesnokov';
$chesnokov_pass = 'CAaSRQUQ/5qvp29f';

try {
    // 1. Подключение к локальной БД для пользователей (site_users)
    $local_host = 'localhost';
    $local_db   = 'Bd_belov';
    $local_user = 'admin';
    $local_pass = 'admin';
    
    $local_pdo = new PDO("mysql:host=$local_host;dbname=$local_db;charset=utf8mb4", $local_user, $local_pass);
    $local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем таблицу site_users если её нет
    $local_pdo->exec("
        CREATE TABLE IF NOT EXISTS site_users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            phone VARCHAR(20) NOT NULL,
            email_confirmed BOOLEAN DEFAULT FALSE,
            phone_confirmed BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
} catch (PDOException $e) {
    die("<div style='background: #ff4d6d; color: white; padding: 20px; margin: 20px; border-radius: 10px;'>
        <h3>❌ Ошибка подключения к локальной БД:</h3>
        <p>" . $e->getMessage() . "</p>
    </div>");
}

// Проверка авторизации пользователя
if (!in_array($current_page, $public_pages)) {
    if (!isset($_SESSION['user_authenticated'])) {
        header('Location: user_login.php');
        exit;
    }
    
    try {
        // 2. Подключение к БД каталога (project_Chesnokov)
        $catalog_dsn = "mysql:host=$host;port=$port;dbname=project_Chesnokov;charset=utf8mb4";
        $catalog_pdo = new PDO($catalog_dsn, $chesnokov_user, $chesnokov_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // 3. Подключение к БД заказов (project_Belov)
        $orders_dsn = "mysql:host=$host;port=$port;dbname=project_Belov;charset=utf8mb4";
        $orders_pdo = new PDO($orders_dsn, $belov_user, $belov_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Создаем таблицы в БД заказов если их нет
        createOrderTables($orders_pdo);
        
    } catch (PDOException $e) {
        die("<div style='background: #ff4d6d; color: white; padding: 20px; margin: 20px; border-radius: 10px;'>
            <h3>❌ Ошибка подключения к удаленным БД:</h3>
            <p>" . $e->getMessage() . "</p>
        </div>");
    }
}

// Функция для создания таблиц заказов
function createOrderTables($pdo) {
    // Таблица клиентов (связь с site_users)
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
    
    // Таблица заказов
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
    
    // Таблица товаров в заказе
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
    
    // Таблица услуг в заказе
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

// Глобальные переменные для доступа к БД
$pdo = $catalog_pdo ?? null;      // Для товаров, категорий, услуг (project_Chesnokov)
$orders_pdo = $orders_pdo ?? null; // Для заказов, клиентов (project_Belov)

// ========== ДОБАВЛЕНО ДЛЯ ИГРЫ: управление таймерами ==========
if (isset($_SESSION['user_authenticated']) && !isset($_SESSION['game_state'])) {
    $_SESSION['game_state'] = 'work';          // work / break
    $_SESSION['game_state_start'] = time();    // время начала текущего состояния
    $_SESSION['work_time_seconds'] = 45 * 60;  // 45 минут
    $_SESSION['break_time_seconds'] = 5 * 60;  // 5 минут
    $_SESSION['forced_break'] = false;         // флаг принудительного отдыха
}

// Функция проверки и переключения состояний
function checkGameStates() {
    $state = $_SESSION['game_state'];
    $elapsed = time() - $_SESSION['game_state_start'];
    $needMessage = false;
    $message = '';
    $forceOpenGame = false;
    $isAdmin = (basename($_SERVER['PHP_SELF']) !== 'user_index.php'); // упрощённо

    if ($state == 'work' && $elapsed >= $_SESSION['work_time_seconds']) {
        // Переключаем на отдых
        $_SESSION['game_state'] = 'break';
        $_SESSION['game_state_start'] = time();
        $_SESSION['forced_break'] = true;
        $needMessage = true;
        $forceOpenGame = true;
        $message = $isAdmin ? '🌸 Пора отдохнуть! Сыграйте в игру 🌸' : '🌸 Пора отдохнуть! Сыграйте в игру 🌸';
    } elseif ($state == 'break' && $elapsed >= $_SESSION['break_time_seconds']) {
        // Переключаем на работу
        $_SESSION['game_state'] = 'work';
        $_SESSION['game_state_start'] = time();
        $_SESSION['forced_break'] = false;
        $needMessage = true;
        $forceOpenGame = false;
        $message = $isAdmin ? '💼 Пора работать!' : '🛍️ Пора выбирать товары!';
    }

    return [
        'need_message' => $needMessage,
        'message' => $message,
        'force_open_game' => $forceOpenGame
    ];
}

// Вызываем функцию при каждом запросе авторизованного пользователя
if (isset($_SESSION['user_authenticated'])) {
    $stateResult = checkGameStates();
    $_SESSION['game_state_result'] = $stateResult;
}
?>