<?php
require 'user_config.php';

$success_message = '';
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $success_message = 'Заказ успешно оформлен!';
}

$order_id = $_GET['id'] ?? 0;

// Получаем клиента из БД ЗАКАЗОВ
$stmt = $orders_pdo->prepare("SELECT client_id FROM clients WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: user_orders.php');
    exit;
}

// Получаем заказ из БД ЗАКАЗОВ
$order = $orders_pdo->prepare("
    SELECT * FROM orders 
    WHERE order_id = ? AND client_id = ?
");
$order->execute([$order_id, $client['client_id']]);
$order = $order->fetch();

if (!$order) {
    header('Location: user_orders.php');
    exit;
}

// Получаем товары в заказе из БД ЗАКАЗОВ
$items = $orders_pdo->prepare("
    SELECT * FROM order_items 
    WHERE order_id = ?
");
$items->execute([$order_id]);
$items = $items->fetchAll();

// Получаем услуги в заказе из БД ЗАКАЗОВ
$services = $orders_pdo->prepare("
    SELECT * FROM order_services 
    WHERE order_id = ?
");
$services->execute([$order_id]);
$services = $services->fetchAll();

$status_names = [
    'pending' => 'Ожидание',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменен'
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Заказ #<?= $order_id ?></title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
</head>
<body>
    <div class="menu">
    <nav class="menu-nav">
        <a href="user_index.php"><i class="fas fa-home"></i> Главная</a>
        <a href="user_categories.php"><i class="fas fa-tags"></i> Категории</a>
        <a href="user_services.php"><i class="fas fa-concierge-bell"></i> Услуги</a>
        <a href="user_cart.php"><i class="fas fa-shopping-cart"></i> Корзина</a>
        <a href="user_orders.php"><i class="fas fa-shopping-cart"></i> Мои заказы</a>
        <a href="user_profile.php"><i class="fas fa-user"></i> Профиль</a>
        <a href="user_logout.php" class="logout-btn">Выход</a>
    </nav>
</div>
    
    <div class="container">
        <div style="margin-bottom: 20px;">
            <a href="user_orders.php" class="btn btn-edit btn-small">
                <i class="fas fa-arrow-left"></i> Назад к заказам
            </a>
        </div>
        
        <h1>Детали заказа #<?= $order_id ?></h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>

        <div class="card-grid">
            <div class="card">
                <div class="card-title">Информация о заказе</div>
                <div class="card-content">
                    <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></p>
                    <p><strong>Статус:</strong> 
                        <span class="status status-<?= $order['status'] ?>">
                            <?= $status_names[$order['status']] ?>
                        </span>
                    </p>
                    <p><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                    <?php if ($order['estimated_delivery_date']): ?>
                        <p><strong>Ожидаемая доставка:</strong> <?= date('d.m.Y', strtotime($order['estimated_delivery_date'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <h2>Товары в заказе</h2>
        <table>
            <tr>
                <th>Товар</th>
                <th>Цена</th>
                <th>Количество</th>
                <th>Сумма</th>
            </tr>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['product_name']) ?></td>
                <td class="price"><?= number_format($item['unit_price'], 2) ?> руб</td>
                <td><?= $item['quantity'] ?></td>
                <td class="price"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> руб</td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (!empty($services)): ?>
        <h2>Дополнительные услуги</h2>
        <table>
            <tr>
                <th>Услуга</th>
                <th>Цена</th>
                <th>Количество</th>
                <th>Сумма</th>
            </tr>
            <?php foreach ($services as $service): ?>
            <tr>
                <td><?= htmlspecialchars($service['service_name']) ?></td>
                <td class="price"><?= number_format($service['service_price'], 2) ?> руб</td>
                <td><?= $service['quantity'] ?></td>
                <td class="price"><?= number_format($service['service_price'] * $service['quantity'], 2) ?> руб</td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <div style="background: var(--pink-light); padding: 20px; border-radius: 15px; margin-top: 20px; text-align: right;">
            <h3 style="margin: 0;">Итого: <span class="price"><?= number_format($order['total_amount'], 2) ?> руб</span></h3>
        </div>
        
        <?php if ($order['notes']): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-title">Примечания</div>
            <div class="card-content">
                <?= nl2br(htmlspecialchars($order['notes'])) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>