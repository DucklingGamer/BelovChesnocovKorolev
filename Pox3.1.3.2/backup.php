<?php
// backup.php - создание дампа баз данных
require 'config.php'; // или user_config.php? Но для админа используем config.php
// Для админа проверяем авторизацию
session_start();
if (!isset($_SESSION['authenticated'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

// Функция для получения дампа одной БД
function getDatabaseDump($pdo, $dbname) {
    $output = "-- Database: $dbname\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    // Получаем список таблиц
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $output .= "-- Table structure for `$table`\n";
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";

        // Данные
        $rows = $pdo->query("SELECT * FROM `$table`");
        if ($rows->rowCount() > 0) {
            $output .= "-- Dumping data for `$table`\n";
            $values = [];
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $escaped = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    return $pdo->quote($val);
                }, $row);
                $values[] = "(" . implode(', ', $escaped) . ")";
            }
            $output .= "INSERT INTO `$table` VALUES \n" . implode(",\n", $values) . ";\n\n";
        }
    }
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $output;
}

// Получаем дампы всех БД
$dump = "";
// Локальная БД (Bd_belov) - для админа используется local_pdo, но в config.php он есть
global $local_pdo;
$dump .= getDatabaseDump($local_pdo, 'Bd_belov');

// БД каталога (project_Chesnokov)
global $catalog_pdo;
if (isset($catalog_pdo)) {
    $dump .= "\n\n" . getDatabaseDump($catalog_pdo, 'project_Chesnokov');
}

// БД заказов (project_Belov)
global $orders_pdo;
if (isset($orders_pdo)) {
    $dump .= "\n\n" . getDatabaseDump($orders_pdo, 'project_Belov');
}

// Отправляем файл
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');
echo $dump;