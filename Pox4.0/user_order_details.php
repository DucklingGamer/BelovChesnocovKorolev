<?php
require 'user_config.php';

$order_id = $_GET['id'] ?? 0;

$stmt = $orders_pdo->prepare("SELECT client_id FROM clients WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$client = $stmt->fetch();

if (!$client) {
    header('Location: user_orders.php');
    exit;
}

$order = $orders_pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND client_id = ?");
$order->execute([$order_id, $client['client_id']]);
$order = $order->fetch();

if (!$order) {
    header('Location: user_orders.php');
    exit;
}

$items = $orders_pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);
$items = $items->fetchAll();

$services = $orders_pdo->prepare("SELECT * FROM order_services WHERE order_id = ?");
$services->execute([$order_id]);
$services = $services->fetchAll();

$status_names = [
    'pending' => 'Ожидание',
    'processing' => 'В обработке',
    'shipped' => 'Отправлен',
    'delivered' => 'Доставлен',
    'cancelled' => 'Отменен'
];

$success_message = isset($_GET['success']) && $_GET['success'] == 1 ? 'Заказ успешно оформлен!' : '';

$title = '🌸 Заказ #' . $order_id;
include 'header.php';
?>

<div class="container">
    <div style="margin-bottom: 20px;">
        <a href="user_orders.php" class="btn btn-edit btn-small"><i class="fas fa-arrow-left"></i> Назад к заказам</a>
    </div>
    
    <h1><i class="fas fa-shopping-cart"></i> Детали заказа #<?= $order_id ?></h1>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success"><?= $success_message ?></div>
    <?php endif; ?>

    <div class="card-grid">
        <div class="card">
            <div class="card-title">Информация о заказе</div>
            <div class="card-content">
                <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></p>
                <p><strong>Статус:</strong> <span class="status status-<?= $order['status'] ?>"><?= $status_names[$order['status']] ?></span></p>
                <p><strong>Адрес доставки:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                <?php if ($order['estimated_delivery_date']): ?>
                    <p><strong>Ожидаемая доставка:</strong> <?= date('d.m.Y', strtotime($order['estimated_delivery_date'])) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <h2>Товары в заказе</h2>
    <?php if (empty($items)): ?>
        <div class="alert alert-warning">Товаров нет</div>
    <?php else: ?>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Товар</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr</thead>
            <tbody>
                <?php foreach ($items as $item): ?>
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
    
    <?php if (!empty($services)): ?>
    <h2>Дополнительные услуги</h2>
    <div class="table-responsive">
        <table>
            <thead><tr><th>Услуга</th><th>Цена</th><th>Количество</th><th>Сумма</th></tr</thead>
            <tbody>
                <?php foreach ($services as $service): ?>
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
        <div class="card-title">Примечания</div>
        <div class="card-content"><?= nl2br(htmlspecialchars($order['notes'])) ?></div>
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
table { width: 100%; border-collapse: collapse; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid var(--pink-light); }
th { background: var(--pink-light); }
.price { color: var(--red-heart); font-weight: bold; }
.status-pending { background: rgba(255,183,3,0.2); color: #ffb703; }
.status-processing { background: rgba(66,153,225,0.2); color: #4299e1; }
.status-shipped { background: rgba(114,137,218,0.2); color: #7289da; }
.status-delivered { background: rgba(72,187,120,0.2); color: #48bb78; }
.status-cancelled { background: rgba(255,77,109,0.2); color: #ff4d6d; }
</style>
<?php include 'game_modal.php'; ?>
</body>
</html>