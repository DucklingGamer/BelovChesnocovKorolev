<?php
require 'config.php';
include 'header.php';

$order_id = $_GET['id'] ?? 0;

$order = $orders_pdo->prepare("
    SELECT o.*, c.full_name, c.email, c.phone, c.address
    FROM orders o 
    JOIN clients c ON o.client_id = c.client_id 
    WHERE o.order_id = ?
");
$order->execute([$order_id]);
$order = $order->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$order_items = $orders_pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$order_items->execute([$order_id]);
$order_items = $order_items->fetchAll();

$order_services = $orders_pdo->prepare("SELECT * FROM order_services WHERE order_id = ?");
$order_services->execute([$order_id]);
$order_services = $order_services->fetchAll();

$status_names = [
    'pending' => 'Ожидание',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменен'
];

$title = '🌸 Детали заказа #' . $order_id;
include 'header.php';
?>

<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="orders.php" class="btn btn-edit btn-small"><i class="fas fa-arrow-left"></i> Назад к заказам</a>
    </div>
    
    <h1><i class="fas fa-shopping-cart"></i> Детали заказа #<?= $order_id ?></h1>

    <div class="card-grid">
        <div class="card">
            <div class="card-title"><i class="fas fa-user"></i> Информация о клиенте</div>
            <div class="card-content">
                <p><strong>Клиент:</strong> <?= htmlspecialchars($order['full_name']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                <p><strong>Адрес:</strong> <?= htmlspecialchars($order['address'] ?: 'Не указан') ?></p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-title"><i class="fas fa-info-circle"></i> Информация о заказе</div>
            <div class="card-content">
                <p><strong>Дата заказа:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></p>
                <p><strong>Статус:</strong> <span class="status status-<?= $order['status'] ?>"><?= $status_names[$order['status']] ?></span></p>
                <p><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                <?php if ($order['estimated_delivery_date']): ?>
                    <p><strong>Ожидаемая доставка:</strong> <?= date('d.m.Y', strtotime($order['estimated_delivery_date'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <h2><i class="fas fa-box"></i> Товары в заказе</h2>
    <?php if (empty($order_items)): ?>
        <div class="alert alert-warning">Товаров в заказе нет</div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Товар</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr</thead>
            <tbody>
                <?php foreach ($order_items as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                    <td class="price"><?= number_format($item['unit_price'], 2) ?> руб</td>
                    <td><?= $item['quantity'] ?></td>
                    <td class="price"><?= number_format($item['unit_price'] * $item['quantity'], 2) ?> руб</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($order_services)): ?>
    <h2><i class="fas fa-concierge-bell"></i> Дополнительные услуги</h2>
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Услуга</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr</thead>
            <tbody>
                <?php foreach ($order_services as $service): ?>
                <tr>
                    <td><?= htmlspecialchars($service['service_name']) ?></td>
                    <td class="price"><?= number_format($service['service_price'], 2) ?> руб</td>
                    <td><?= $service['quantity'] ?></td>
                    <td class="price"><?= number_format($service['service_price'] * $service['quantity'], 2) ?> руб</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <div class="order-total">
        <h3>Итого: <span class="price"><?= number_format($order['total_amount'], 2) ?> руб</span></h3>
    </div>
    
    <?php if ($order['notes']): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-title"><i class="fas fa-comment"></i> Примечания к заказу</div>
        <div class="card-content"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
    </div>
    <?php endif; ?>
    
    <?php if (in_array($order['status'], ['pending', 'processing', 'shipped'])): ?>
    <div class="card" style="margin-top: 20px;">
        <div class="card-title"><i class="fas fa-truck"></i> Изменить статус</div>
        <div class="card-content">
            <form method="POST" action="orders.php">
                <input type="hidden" name="update_status" value="<?= $order_id ?>">
                <select name="status" class="btn btn-edit">
                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Ожидание</option>
                    <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                    <option value="shipped" <?= $order['status'] == 'shipped' ? 'selected' : '' ?>>Отправлен</option>
                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>Доставлен</option>
                    <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                </select>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Обновить статус</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'game_modal.php'; ?>

<style>
.order-total {
    background: var(--pink-light);
    padding: 20px;
    border-radius: 15px;
    margin-top: 20px;
    text-align: right;
}
.table-responsive { overflow-x: auto; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--pink-light); }
.table th { background: var(--pink-light); color: var(--purple-magical); }
.price { color: var(--red-heart); font-weight: bold; }
.status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: bold;
}
.status-pending { background: rgba(255,183,3,0.2); color: #ffb703; }
.status-processing { background: rgba(66,153,225,0.2); color: #4299e1; }
.status-shipped { background: rgba(114,137,218,0.2); color: #7289da; }
.status-delivered { background: rgba(72,187,120,0.2); color: #48bb78; }
.status-cancelled { background: rgba(255,77,109,0.2); color: #ff4d6d; }
</style>
<?php include 'game_modal.php'; ?>
</body>
</html>