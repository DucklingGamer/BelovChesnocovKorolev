<?php
require 'config.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Кавай Магазин 🍥</title>
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
        <div class="anime-header">
            <div class="anime-logo">カワイイ ショップ</div>
            <div class="anime-subtitle">АНИМЕ МАГАЗИН ✨ НЯ~</div>
        </div>
        
        <div class="stats-grid">
            <?php
            $tables = [
                'clients' => ['icon' => '👥', 'name' => 'Клиенты', 'color' => '#ff4d6d'],
                'products' => ['icon' => '📦', 'name' => 'Товары', 'color' => '#ffafcc'],
                'categories' => ['icon' => '🏷️', 'name' => 'Категории', 'color' => '#cdb4db'],
                'orders' => ['icon' => '🛒', 'name' => 'Заказы', 'color' => '#a2d2ff'],
                'additional_services' => ['icon' => '🔧', 'name' => 'Услуги', 'color' => '#b5e48c']
            ];
            
            foreach ($tables as $table => $info) {
                try {
                    $result = $pdo->query("SELECT COUNT(*) as count FROM $table");
                    $row = $result->fetch();
                    $count = $row['count'];
                } catch (Exception $e) {
                    $count = 0;
                }
                
                echo '
                <div class="stat-card">
                    <div class="stat-icon">' . $info['icon'] . '</div>
                    <div class="stat-number" style="color: ' . $info['color'] . '">' . $count . '</div>
                    <div class="stat-label">' . $info['name'] . '</div>
                </div>';
            }
            ?>
        </div>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-title"><i class="fas fa-bolt"></i> Быстрый доступ</div>
                <div class="card-content">
                    <p><i class="fas fa-user-plus"></i> <a href="clients.php?action=add" style="color: var(--red-heart); text-decoration: none;">✨ Добавить клиента</a></p>
                    <p><i class="fas fa-cart-plus"></i> <a href="orders.php?action=add" style="color: var(--red-heart); text-decoration: none;">🌸 Создать заказ</a></p>
                    <p><i class="fas fa-box"></i> <a href="products.php?action=add" style="color: var(--red-heart); text-decoration: none;">🍥 Добавить товар</a></p>
                    <p><i class="fas fa-tag"></i> <a href="categories.php?action=add" style="color: var(--red-heart); text-decoration: none;">🎀 Добавить категорию</a></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-chart-line"></i> Статистика</div>
                <div class="card-content">
                    <?php
                    // Общая статистика
                    $orders_today = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()")->fetch()['count'];
                    $total_revenue = $pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'")->fetch()['total'] ?? 0;
                    $active_clients = $pdo->query("SELECT COUNT(*) as count FROM clients WHERE is_active = 1")->fetch()['count'];
                    $total_products = $pdo->query("SELECT SUM(stock_quantity) as total FROM products")->fetch()['total'] ?? 0;
                    ?>
                    <p><i class="fas fa-shopping-cart"></i> Заказов сегодня: <span class="price"><?= $orders_today ?></span></p>
                    <p><i class="fas fa-yen-sign"></i> Общий доход: <span class="price"><?= number_format($total_revenue, 2) ?> руб</span></p>
                    <p><i class="fas fa-user-check"></i> Активных клиентов: <span class="price"><?= $active_clients ?></span></p>
                    <p><i class="fas fa-boxes"></i> Товаров в наличии: <span class="price"><?= $total_products ?></span></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-info-circle"></i> О системе</div>
                <div class="card-content">
                    <p>🎨 Кавай аниме-дизайн</p>
                    <p>⚡ Быстрая работа</p>
                    <p>🔐 Безопасные транзакции</p>
                    <p>📱 Полностью адаптивная</p>
                    <p>✨ Автоматические расчеты</p>
                    <p>🌸 Управление услугами</p>
                </div>
            </div>
        </div>
        
        <h2><i class="fas fa-history"></i> Последние заказы</h2>
        <?php
        $recent_orders = $pdo->query("
            SELECT o.*, c.full_name 
            FROM orders o 
            JOIN clients c ON o.client_id = c.client_id 
            ORDER BY o.order_date DESC 
            LIMIT 5
        ")->fetchAll();
        
        if (count($recent_orders) > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Дата</th>
                <th>Сумма</th>
                <th>Статус</th>
            </tr>
            <?php foreach ($recent_orders as $order): ?>
            <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td><?= htmlspecialchars($order['full_name']) ?></td>
                <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                <td class="price"><?= number_format($order['total_amount'], 2) ?> руб</td>
                <td>
                    <span class="status status-<?= $order['status'] ?>">
                        <?= $order['status'] ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Пока нет заказов. Создайте первый заказ!
        </div>
        <?php endif; ?>
    </div>
</body>
</html>