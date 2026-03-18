<?php
require 'config.php';

$order_id = $_GET['id'] ?? 0;

// Получение информации о заказе
$order = $orders_pdo->prepare("
    SELECT o.*, c.full_name, c.email, c.phone, c.address
    FROM orders o 
    JOIN clients c ON o.client_id = c.client_id 
    WHERE o.order_id = ?
");
$order->execute([$order_id]);
$order = $order->fetch();

if (!$order) {
    die("<div class='alert alert-error'>🌸 Заказ не найден!</div>");
}

// Получение товаров в заказе
$order_items = $orders_pdo->prepare("
    SELECT * FROM order_items 
    WHERE order_id = ?
");
$order_items->execute([$order_id]);
$order_items = $order_items->fetchAll();

// Получение услуг в заказе
$order_services = $orders_pdo->prepare("
    SELECT * FROM order_services 
    WHERE order_id = ?
");
$order_services->execute([$order_id]);
$order_services = $order_services->fetchAll();

// ... остальной HTML без изменений
?>