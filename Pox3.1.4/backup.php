<?php
session_start();
if (!isset($_SESSION['authenticated'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

require 'config.php';
include 'header.php';

function getDatabaseDump($pdo, $dbname) {
    $output = "-- Database: $dbname\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($tables as $table) {
        $output .= "-- Table structure for `$table`\n";
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $output .= $create['Create Table'] . ";\n\n";

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

$dump = "";
$dump .= getDatabaseDump($local_pdo, 'Bd_belov');
if (isset($catalog_pdo)) {
    $dump .= "\n\n" . getDatabaseDump($catalog_pdo, 'project_Chesnokov');
}
if (isset($orders_pdo)) {
    $dump .= "\n\n" . getDatabaseDump($orders_pdo, 'project_Belov');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_H-i-s') . '.sql"');
echo $dump;