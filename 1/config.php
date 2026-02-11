<?php
$host = '134.90.167.42';
$port = '10306';
$db   = 'project_Chesnokov';
$user = 'Chesnokov'; // замени на свои данные
$pass = 'CAaSRQUQ/5qvp29f';     // замени на свои данные
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
    die("<div class='alert alert-error'>Ошибка подключения: " . $e->getMessage() . "</div>");
}
?>