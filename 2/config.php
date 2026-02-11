<?php
session_start();

// Локальная БД для аутентификации
$local_host = 'localhost';
$local_db   = 'Bd_belov';
$local_user = 'admin';
$local_pass = 'admin';

try {
    $local_pdo = new PDO(
        "mysql:host=$local_host;dbname=$local_db;charset=utf8mb4",
        $local_user,
        $local_pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("<div class='alert alert-error'>Ошибка подключения к локальной БД: " . $e->getMessage() . "</div>");
}

// Удаленная БД для основного приложения
$host = '134.90.167.42';
$port = '10306';
$db   = 'project_Chesnokov';
$user = 'Chesnokov';
$pass = 'CAaSRQUQ/5qvp29f';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("<div class='alert alert-error'>Ошибка подключения к основной БД: " . $e->getMessage() . "</div>");
}

// Функция проверки аутентификации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit;
    }
}

// Функция проверки пароля
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
[file content end]