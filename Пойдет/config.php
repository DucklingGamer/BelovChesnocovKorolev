<?php
session_start();

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
    
    // Если пользователь авторизован, подключаемся к удаленной БД
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
        
        // Подключение к удаленной БД (project_Chesnokov)
        $host = '134.90.167.42';
        $port = '10306';
        $db   = 'project_Chesnokov';
        $remote_user = $user['remote_db_user'] ?? 'Chesnokov';
        $remote_pass = $user['remote_db_password'] ?? 'CAaSRQUQ/5qvp29f';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $remote_user, $remote_pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
} catch (PDOException $e) {
    die("<div class='alert alert-error'>Ошибка подключения: " . $e->getMessage() . "</div>");
}
?>