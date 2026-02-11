<?php
session_start();

// Определяем корневую папку
define('ROOT_PATH', dirname(__FILE__));

// Проверяем, находимся ли мы на странице авторизации или выхода
$current_page = basename($_SERVER['PHP_SELF']);
$excluded_pages = ['login.php', 'logout.php', 'create_users_table.php', 'check_database.php', 'init_setup.php'];

// Если страница не исключена и пользователь не авторизован, перенаправляем
if (!in_array($current_page, $excluded_pages) && !isset($_SESSION['authenticated'])) {
    header('Location: login.php');
    exit;
}

// Если пользователь авторизован, подключаемся к БД
if (isset($_SESSION['authenticated'])) {
    // Локальная БД для хранения пользователей
    $local_host = 'localhost';
    $local_db   = 'Bd_belov';
    $local_user = 'admin';
    $local_pass = 'admin';
    $local_charset = 'utf8mb4';

    $local_dsn = "mysql:host=$local_host;dbname=$local_db;charset=$local_charset";
    $local_options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $local_pdo = new PDO($local_dsn, $local_user, $local_pass, $local_options);
        
        // Получаем данные текущего пользователя
        $stmt = $local_pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Пользователь не найден, разлогиниваем
            session_destroy();
            header('Location: login.php');
            exit;
        }
        
        // Подключаемся к удаленной БД с данными пользователя
        $host = '134.90.167.42';
        $port = '10306';
        $db   = 'project_Chesnokov';
        $remote_user = $user['remote_db_user'] ?? 'Chesnokov';
        $remote_pass = $user['remote_db_password'] ?? 'CAaSRQUQ/5qvp29f';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, $remote_user, $remote_pass, $options);
        } catch (PDOException $e) {
            die("<div class='alert alert-error'>Ошибка подключения к удаленной БД: " . $e->getMessage() . "</div>");
        }
        
    } catch (PDOException $e) {
        die("<div class='alert alert-error'>Ошибка подключения к локальной БД: " . $e->getMessage() . "</div>");
    }
}
?>