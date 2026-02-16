<?php
require 'user_config.php';

// Получаем клиента по email
$stmt = $pdo->prepare("SELECT client_id FROM clients WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$client = $stmt->fetch();

$client_id = $client ? $client['client_id'] : 0;

// Отмена заказа
if (isset($_GET['cancel']) && $client_id) {
    $order_id = $_GET['cancel'];
    
    // Проверяем, что заказ принадлежит пользователю
    $check = $pdo->prepare("SELECT status FROM orders WHERE order_id = ? AND client_id = ?");
    $check->execute([$order_id, $client_id]);
    $order = $check->fetch();
    
    if ($order && in_array($order['status'], ['pending', 'processing'])) {
        $pdo->beginTransaction();
        
        try {
            // Возвращаем товары на склад
            $items = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
            $items->execute([$order_id]);
            
            foreach ($items as $item) {
                $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE product_id = ?")
                    ->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Обновляем статус заказа
            $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = ?")->execute([$order_id]);
            
            $pdo->commit();
            $success = "Заказ #$order_id отменен. Товары возвращены на склад.";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Ошибка при отмене заказа";
        }
    }
}

// Получаем заказы пользователя
if ($client_id) {
    $orders = $pdo->prepare("
        SELECT o.*, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.order_id) as items_count
        FROM orders o 
        WHERE o.client_id = ? 
        ORDER BY o.order_date DESC
    ");
    $orders->execute([$client_id]);
    $orders = $orders->fetchAll();
} else {
    $orders = [];
}

$status_names = [
    'pending' => ['Ожидание', 'status-pending'],
    'processing' => ['В обработке', 'status-processing'],
    'shipped' => ['Отправлен', 'status-processing'],
    'delivered' => ['Доставлен', 'status-delivered'],
    'cancelled' => ['Отменен', 'status-pending']
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Мои заказы</title>
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
            <a href="user_orders.php"><i class="fas fa-shopping-cart"></i> Мои заказы</a>
            <a href="user_profile.php"><i class="fas fa-user"></i> Профиль</a>
            <a href="user_logout.php" class="logout-btn">Выход</a>
        </nav>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Мои заказы</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if (!$client_id): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                Вы еще не делали заказов.
            </div>
        <?php elseif (empty($orders)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> У вас пока нет заказов
            </div>
        <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $order): ?>
            <div class="card" style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                    <div class="card-title" style="margin: 0;">
                        Заказ #<?= $order['order_id'] ?> от <?= date('d.m.Y', strtotime($order['order_date'])) ?>
                    </div>
                    <span class="status <?= $status_names[$order['status']][1] ?>">
                        <?= $status_names[$order['status']][0] ?>
                    </span>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 15px;">
                    <div><strong>Сумма:</strong> <span class="price"><?= number_format($order['total_amount'], 2) ?> руб</span></div>
                    <div><strong>Товаров:</strong> <?= $order['items_count'] ?></div>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <a href="user_order_details.php?id=<?= $order['order_id'] ?>" class="btn btn-edit btn-small">
                        <i class="fas fa-eye"></i> Детали
                    </a>
                    
                    <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                        <a href="?cancel=<?= $order['order_id'] ?>" 
                           class="btn btn-delete btn-small"
                           onclick="return confirm('Отменить заказ #<?= $order['order_id'] ?>? Товары вернутся на склад.')">
                            <i class="fas fa-times"></i> Отменить
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>