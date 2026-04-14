<?php
require 'config.php';

$title = '🌸 Кавай Магазин - Админка';
include 'header.php';
?>

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
    
    
    
    <div class="card-grid">
        <div class="card">
            <div class="card-title"><i class="fas fa-bolt"></i> Быстрый доступ</div>
            <div class="card-content">
                <p><i class="fas fa-user-plus"></i> <a href="clients.php?action=add" style="color: var(--red-heart);">✨ Добавить клиента</a></p>
                <p><i class="fas fa-cart-plus"></i> <a href="orders.php?action=add" style="color: var(--red-heart);">🌸 Создать заказ</a></p>
                <p><i class="fas fa-box"></i> <a href="products.php?action=add" style="color: var(--red-heart);">🍥 Добавить товар</a></p>
                <p><i class="fas fa-tag"></i> <a href="categories.php?action=add" style="color: var(--red-heart);">🎀 Добавить категорию</a></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title"><i class="fas fa-chart-line"></i> Статистика</div>
            <div class="card-content">
                <?php
                $orders_today = $orders_pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()")->fetchColumn();
                $total_revenue = $orders_pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'")->fetchColumn() ?? 0;
                $active_clients = $orders_pdo->query("SELECT COUNT(*) FROM clients WHERE is_active = 1")->fetchColumn();
                $total_products = $catalog_pdo->query("SELECT SUM(stock_quantity) FROM products")->fetchColumn() ?? 0;
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
    <div class="table-responsive">
        <table class="table">
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
                    <td>
                        <span class="status status-<?= $order['status'] ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
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

<?php include 'game_modal.php'; ?>

<style>
/* Стили для админки */
.anime-header {
    text-align: center;
    margin-bottom: 30px;
}
.anime-logo {
    font-family: 'M PLUS Rounded 1c', sans-serif;
    font-size: 2.5rem;
    color: var(--red-heart);
}
.anime-subtitle {
    color: var(--purple-magical);
    font-size: 1rem;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 30px 0;
}
.stat-card {
    background: linear-gradient(145deg, white, var(--pink-light));
    padding: 25px;
    border-radius: 25px;
    text-align: center;
    transition: all 0.4s;
    box-shadow: 0 10px 30px var(--shadow-pink);
}
.stat-card:hover {
    transform: translateY(-8px);
}
.stat-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin: 15px 0;
}
.stat-label {
    color: var(--purple-magical);
    font-weight: 700;
}
.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin: 30px 0;
}
.card {
    background: white;
    padding: 25px;
    border-radius: 25px;
    border: 3px solid var(--pink-light);
    box-shadow: 0 10px 25px var(--shadow-pink);
}
.card-title {
    font-size: 1.3rem;
    font-weight: bold;
    color: var(--red-heart);
    margin-bottom: 15px;
}
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 25px;
    margin: 30px 0;
}
.product-card {
    background: white;
    border-radius: 20px;
    border: 3px solid var(--pink-light);
    overflow: hidden;
    transition: all 0.4s;
    box-shadow: 0 10px 25px var(--shadow-pink);
}
.product-card:hover {
    transform: translateY(-10px);
}
.product-image {
    height: 200px;
    background: linear-gradient(135deg, var(--pink-light), var(--purple-lavender));
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}
.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.no-image {
    text-align: center;
    color: var(--purple-magical);
}
.no-image i {
    font-size: 3rem;
}
.out-of-stock {
    position: absolute;
    top: 10px;
    right: 10px;
    background: var(--red-heart);
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 0.8rem;
}
.product-info {
    padding: 20px;
}
.product-category {
    background: var(--pink-light);
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
    display: inline-block;
    margin-bottom: 10px;
}
.product-price {
    color: var(--red-heart);
    font-weight: 800;
    font-size: 1.4rem;
}
.product-stock {
    padding: 5px 12px;
    border-radius: 15px;
    font-size: 0.85rem;
}
.in-stock {
    background: rgba(72, 187, 120, 0.2);
    color: #48bb78;
}
.out-stock {
    background: rgba(255, 77, 109, 0.2);
    color: #ff4d6d;
}
.table-responsive {
    overflow-x: auto;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table th, .table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid var(--pink-light);
}
.table th {
    background: var(--pink-light);
    color: var(--purple-magical);
}
.status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
}
.status-pending { background: rgba(255,183,3,0.2); color: #ffb703; }
.status-processing { background: rgba(66,153,225,0.2); color: #4299e1; }
.status-shipped { background: rgba(114,137,218,0.2); color: #7289da; }
.status-delivered { background: rgba(72,187,120,0.2); color: #48bb78; }
.status-cancelled { background: rgba(255,77,109,0.2); color: #ff4d6d; }
.authors-footer {
    margin-top: 60px;
    padding: 30px;
    background: linear-gradient(145deg, var(--pink-light), var(--purple-lavender));
    border-radius: 25px;
    text-align: center;
}
.authors-list {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin: 15px 0;
    flex-wrap: wrap;
}
.author {
    background: white;
    padding: 8px 20px;
    border-radius: 30px;
    font-weight: bold;
    color: var(--purple-magical);
}
.price {
    color: var(--red-heart);
    font-weight: bold;
}
.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: 30px;
    text-decoration: none;
    font-weight: bold;
    transition: all 0.3s;
}
.btn-success {
    background: linear-gradient(145deg, var(--green-matcha), #52b788);
    color: white;
}
.btn-edit {
    background: linear-gradient(145deg, var(--purple-lavender), var(--purple-magical));
    color: white;
}
</style>

</body>
</html>