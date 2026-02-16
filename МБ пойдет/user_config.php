<?php
// Включаем отображение всех ошибок
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

define('ROOT_PATH', dirname(__FILE__));

// Страницы, доступные без авторизации
$public_pages = ['user_login.php', 'user_register.php', 'user_logout.php', 'debug.php'];
$current_page = basename($_SERVER['PHP_SELF']);

// Подключение к локальной БД
$local_host = 'localhost';
$local_db   = 'Bd_belov';
$local_user = 'admin';
$local_pass = 'admin';

try {
    $local_pdo = new PDO("mysql:host=$local_host;dbname=$local_db;charset=utf8mb4", $local_user, $local_pass);
    $local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем таблицу site_users если её нет (добавим проверку)
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
        )
    ");
    
} catch (PDOException $e) {
    die("<div style='background: #ff4d6d; color: white; padding: 20px; margin: 20px; border-radius: 10px;'>
        <h3>❌ Ошибка подключения к локальной БД:</h3>
        <p>" . $e->getMessage() . "</p>
        <p>Проверьте:</p>
        <ul>
            <li>Хост: $local_host</li>
            <li>База данных: $local_db</li>
            <li>Пользователь: $local_user</li>
            <li>Пароль: $local_pass</li>
        </ul>
    </div>");
}

// Проверка авторизации пользователя
if (!in_array($current_page, $public_pages)) {
    if (!isset($_SESSION['user_authenticated'])) {
        header('Location: user_login.php');
        exit;
    }
    
    // Если пользователь авторизован, подключаемся к удаленной БД
    try {
        $host = '134.90.167.42';
        $port = '10306';
        $db   = 'project_Belov';
        $remote_user = 'Belov';
        $remote_pass = 'B6EQr.7PN]*u8Ffn';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $remote_user, $remote_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("<div style='background: #ff4d6d; color: white; padding: 20px; margin: 20px; border-radius: 10px;'>
            <h3>❌ Ошибка подключения к удаленной БД:</h3>
            <p>" . $e->getMessage() . "</p>
        </div>");
    }
}
?>