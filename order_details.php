<?php
require 'config.php';

$order_id = $_GET['id'] ?? 0;

// Получение информации о заказе
$order = $pdo->query("
    SELECT o.*, c.* 
    FROM orders o 
    JOIN clients c ON o.client_id = c.client_id 
    WHERE o.order_id = $order_id
")->fetch();

// Получение товаров в заказе
$order_items = $pdo->query("
    SELECT oi.*, p.product_name 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.product_id 
    WHERE oi.order_id = $order_id
")->fetchAll();

// Получение услуг в заказе
$order_services = $pdo->query("
    SELECT os.*, s.service_name 
    FROM order_services os 
    JOIN additional_services s ON os.service_id = s.service_id 
    WHERE os.order_id = $order_id
")->fetchAll();

if (!$order) {
    die("<div class='alert alert-error'>🌸 Заказ не найден!</div>");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Детали заказа #<?= $order_id ?> - Кавай Магазин</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="menu">
        <nav class="menu-nav">
            <a href="index.php"><i class="fas fa-home"></i> Главная</a>
            <a href="clients.php"><i class="fas fa-users"></i> Клиенты</a>
            <a href="categories.php"><i class="fas fa-tags"></i> Категории</a>
            <a href="products.php"><i class="fas fa-box-open"></i> Товары</a>
            <a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-file-invoice"></i> Детали заказа #<?= $order_id ?></h1>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-title"><i class="fas fa-user"></i> Информация о клиенте</div>
                <div class="card-content">
                    <p><strong>Имя:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                    <p><strong>Телефон:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                    <p><strong>Адрес регистрации:</strong> <?= htmlspecialchars($order['address']) ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-truck"></i> Информация о доставке</div>
                <div class="card-content">
                    <p><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    <p><strong>Статус:</strong> 
                        <span class="status status-<?= $order['status'] ?>">
                            <?= $order['status'] ?>
                        </span>
                    </p>
                    <p><strong>Дата заказа:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></p>
                    <?php if ($order['estimated_delivery_date']): ?>
                        <p><strong>Предполагаемая доставка:</strong> <?= date('d.m.Y', strtotime($order['estimated_delivery_date'])) ?></p>
                    <?php endif; ?>
                    <?php if ($order['actual_delivery_date']): ?>
                        <p><strong>Фактическая доставка:</strong> <?= date('d.m.Y', strtotime($order['actual_delivery_date'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <h2><i class="fas fa-box"></i> Товары в заказе</h2>
        <?php if (count($order_items) > 0): ?>
        <table>
            <tr>
                <th>Товар</th>
                <th>Цена за единицу</th>
                <th>Количество</th>
                <th>Сумма</th>
            </tr>
            <?php foreach ($order_items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="price"><?= number_format($item['unit_price'], 2) ?> руб</td>
                <td><?= $item['quantity'] ?> шт.</td>
                <td class="price"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> руб</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> В заказе нет товаров
        </div>
        <?php endif; ?>
        
        <h2><i class="fas fa-concierge-bell"></i> Дополнительные услуги</h2>
        <?php if (count($order_services) > 0): ?>
        <table>
            <tr>
                <th>Услуга</th>
                <th>Цена за единицу</th>
                <th>Количество</th>
                <th>Сумма</th>
            </tr>
            <?php foreach ($order_services as $service): ?>
            <tr>
                <td><?= htmlspecialchars($service['service_name']) ?></td>
                <td class="price"><?= number_format($service['service_price'], 2) ?> руб</td>
                <td><?= $service['quantity'] ?> шт.</td>
                <td class="price"><?= number_format($service['service_price'] * $service['quantity'], 2) ?> руб</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> В заказе нет дополнительных услуг
        </div>
        <?php endif; ?>
        
        <div style="background: var(--pink-light); padding: 25px; border-radius: 20px; margin-top: 30px; text-align: right;">
            <h2 style="margin: 0;">Итоговая сумма: <span class="price"><?= number_format($order['total_amount'], 2) ?> руб</span></h2>
        </div>
        
        <?php if ($order['notes']): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-title"><i class="fas fa-sticky-note"></i> Примечания к заказу</div>
            <div class="card-content">
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="orders.php" class="btn">
                <i class="fas fa-arrow-left"></i> Вернуться к списку заказов
            </a>
            <button onclick="window.print()" class="btn btn-success">
                <i class="fas fa-print"></i> Распечатать
            </button>
        </div>
    </div>
</body>
</html>