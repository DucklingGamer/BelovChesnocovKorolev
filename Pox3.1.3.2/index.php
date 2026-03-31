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
    <script src="theme.js" defer></script>
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
            <!-- ДОБАВЛЕНО: кнопка ручного запуска игры -->
            <a href="#" onclick="openColorGame(false); return false;" style="background: linear-gradient(145deg, var(--green-matcha), var(--blue-sky));">
                <i class="fas fa-palette"></i> Игра-перерыв
            </a>
            <a href="logout.php" class="logout-btn" style="background: linear-gradient(145deg, #ff4d6d, #c9184a); color: white; margin-left: auto;">
                <i class="fas fa-sign-out-alt"></i> Выход
            </a>
        </nav>
    </div>
    
    <div class="container">
        <div class="anime-header">
            <div class="anime-logo">カワイイ ショップ</div>
            <div class="anime-subtitle">Кавайный магазин</div>
        </div>
        
        <div style="text-align: center; margin: 20px 0; padding: 15px; background: var(--pink-light); border-radius: 15px;">
            <p style="margin: 0; color: var(--purple-magical);">
                <i class="fas fa-user"></i> Вы вошли как: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Гость') ?></strong>
            </p>
        </div>
        
        <div class="stats-grid">
            <?php
            $tables = [
                'clients' => ['icon' => '👥', 'name' => 'Клиенты', 'color' => '#ff4d6d', 'pdo' => $orders_pdo],
                'products' => ['icon' => '📦', 'name' => 'Товары', 'color' => '#ffafcc', 'pdo' => $catalog_pdo],
                'categories' => ['icon' => '🏷️', 'name' => 'Категории', 'color' => '#cdb4db', 'pdo' => $catalog_pdo],
                'orders' => ['icon' => '🛒', 'name' => 'Заказы', 'color' => '#a2d2ff', 'pdo' => $orders_pdo],
                'additional_services' => ['icon' => '🔧', 'name' => 'Услуги', 'color' => '#b5e48c', 'pdo' => $catalog_pdo]
            ];

            foreach ($tables as $table => $info) {
                try {
                    $result = $info['pdo']->query("SELECT COUNT(*) as count FROM $table");
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
                    $orders_today = $orders_pdo->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()")->fetch()['count'];
                    $total_revenue = $orders_pdo->query("SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'")->fetch()['total'] ?? 0;
                    $active_clients = $orders_pdo->query("SELECT COUNT(*) as count FROM clients WHERE is_active = 1")->fetch()['count'];
                    $total_products = $catalog_pdo->query("SELECT SUM(stock_quantity) as total FROM products")->fetch()['total'] ?? 0;
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
        
        <h2><i class="fas fa-boxes"></i> Наши товары</h2>
        <?php
        $products = $catalog_pdo->query("
            SELECT p.*, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.is_available = 1 
            ORDER BY p.product_id DESC 
            LIMIT 12
        ")->fetchAll();
        
        if (count($products) > 0): ?>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($product['image_url']): ?>
                        <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                            <span>Нет фото</span>
                        </div>
                    <?php endif; ?>
                    <?php if ($product['stock_quantity'] == 0): ?>
                        <div class="out-of-stock">Нет в наличии</div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                    <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></div>
                    <div class="product-description"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 60)) ?>...</div>
                    <div class="product-footer">
                        <div class="product-price"><?= number_format($product['price'], 2) ?> руб</div>
                        <div class="product-stock <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                            <?= $product['stock_quantity'] ?> шт.
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Пока нет товаров. Добавьте первый товар!
        </div>
        <?php endif; ?>
        
        <h2><i class="fas fa-history"></i> Последние заказы</h2>
        <?php
        $recent_orders = $orders_pdo->query("
            SELECT o.*, c.full_name 
            FROM orders o 
            JOIN clients c ON o.client_id = c.client_id 
            ORDER BY o.order_date DESC 
            LIMIT 5
        ")->fetchAll();
        
        if (count($recent_orders) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Клиент</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($recent_orders as $order): ?>
                 <tr>
                    <td>#<?= $order['order_id'] ?></td>
                    <td><?= htmlspecialchars($order['full_name']) ?></td>
                    <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                    <td class="price"><?= number_format($order['total_amount'], 2) ?> руб</td>
                    <td><span class="status status-<?= $order['status'] ?>"><?= $order['status'] ?></span></td>
                 </tr>
            <?php endforeach; ?>
            </tbody>
         </table>
        <?php else: ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> Пока нет заказов. Создайте первый заказ!
        </div>
        <?php endif; ?>
        
        <footer class="authors-footer">
            <div class="footer-content">
                <div class="footer-title">Разработано:</div>
                <div class="authors-list">
                    <span class="author">Королев</span>
                    <span class="separator">•</span>
                    <span class="author">Белов</span>
                    <span class="separator">•</span>
                    <span class="author">Чесноков</span>
                </div>
                <div class="footer-subtitle">🌸 Кавай Магазин 🍥 © 2024</div>
            </div>
        </footer>
    </div>
    
    <!-- Подключаем модальное окно с игрой -->
    <?php include 'game_modal.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['game_state_result']) && $_SESSION['game_state_result']['need_message']): ?>
            <?php $res = $_SESSION['game_state_result']; ?>
            <?php if ($res['force_open_game']): ?>
                setTimeout(function() {
                    openColorGame(true);
                }, 500);
            <?php endif; ?>
            showGameStateMessage("<?= addslashes($res['message']) ?>", <?= $res['force_open_game'] ? 'true' : 'false' ?>);
            <?php unset($_SESSION['game_state_result']); ?>
        <?php endif; ?>
    });

    function showGameStateMessage(message, isForced) {
        const notification = document.createElement('div');
        notification.className = 'game-state-notification';
        notification.innerHTML = `
            <div class="reminder-content">
                <div class="reminder-icon">${isForced ? '🎮' : '⏰'}</div>
                <div class="reminder-text">${message}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="btn btn-edit btn-small">OK</button>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            if (notification.parentNode) notification.remove();
        }, 8000);
    }
    </script>
    
    <style>
    .game-state-notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        max-width: 400px;
        background: white;
        border-radius: 20px;
        border: 3px solid var(--pink-sakura);
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        z-index: 10002;
        transition: transform 0.4s ease-out;
        animation: bounceInDown 0.5s ease-out;
    }
    .game-state-notification.show { transform: translateX(-50%) translateY(0); }
    @keyframes bounceInDown {
        0% { transform: translateX(-50%) translateY(-100px); opacity: 0; }
        60% { transform: translateX(-50%) translateY(10px); opacity: 1; }
        100% { transform: translateX(-50%) translateY(0); }
    }
    </style>
</body>
</html>