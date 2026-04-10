<?php
require 'user_config.php';
include 'header.php';

// Проверяем, что корзина не пуста
if (empty($_SESSION['cart'])) {
    header('Location: user_cart.php');
    exit;
}

// Получаем товары из корзины с актуальными данными
$ids = implode(',', array_keys($_SESSION['cart']));
$products = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids) AND is_available = 1")->fetchAll();

// Проверяем наличие каждого товара в нужном количестве
$cart_valid = true;
$out_of_stock = [];
foreach ($products as $product) {
    $qty = $_SESSION['cart'][$product['product_id']];
    if ($qty > $product['stock_quantity']) {
        $cart_valid = false;
        $out_of_stock[] = $product['product_name'] . ' (доступно ' . $product['stock_quantity'] . ')';
        // Корректируем количество
        $_SESSION['cart'][$product['product_id']] = $product['stock_quantity'];
    }
}
if (!$cart_valid) {
    $_SESSION['checkout_error'] = 'Некоторые товары отсутствуют в нужном количестве. Количество скорректировано.';
    header('Location: user_cart.php');
    exit;
}

// Получаем или создаём клиента в БД заказов
$stmt = $orders_pdo->prepare("SELECT client_id FROM clients WHERE email = ?");
$stmt->execute([$_SESSION['email']]);
$client = $stmt->fetch();

if (!$client) {
    // Создаём клиента в БД заказов
    $stmt = $orders_pdo->prepare("INSERT INTO clients (full_name, email, phone, address) VALUES (?, ?, ?, ?)");
    $address = ''; // Пользователь введёт адрес при оформлении
    $stmt->execute([$_SESSION['username'], $_SESSION['email'], $_SESSION['phone'], $address]);
    $client_id = $orders_pdo->lastInsertId();
} else {
    $client_id = $client['client_id'];
}

// Обработка отправки формы оформления
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = trim($_POST['shipping_address']);
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($shipping_address)) {
        $error = 'Введите адрес доставки';
    } else {
        $orders_pdo->beginTransaction();
        try {
            // Рассчитываем общую сумму
            $total = 0;
            $items_data = [];
            foreach ($products as $product) {
                $qty = $_SESSION['cart'][$product['product_id']];
                $subtotal = $product['price'] * $qty;
                $total += $subtotal;
                $items_data[] = [
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'unit_price' => $product['price'],
                    'quantity' => $qty
                ];
            }
            
            // Создаём заказ
            $stmt = $orders_pdo->prepare("
                INSERT INTO orders (client_id, shipping_address, total_amount, status, notes)
                VALUES (?, ?, ?, 'pending', ?)
            ");
            $stmt->execute([$client_id, $shipping_address, $total, $notes]);
            $order_id = $orders_pdo->lastInsertId();
            
            // Добавляем товары в order_items
            $stmt_item = $orders_pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, unit_price, quantity)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items_data as $item) {
                $stmt_item->execute([
                    $order_id,
                    $item['product_id'],
                    $item['product_name'],
                    $item['unit_price'],
                    $item['quantity']
                ]);
            }
            
            // Уменьшаем остатки на складе в БД каталога
            $update_stmt = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            foreach ($items_data as $item) {
                $update_stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            $orders_pdo->commit();
            
            // Очищаем корзину
            unset($_SESSION['cart']);
            
            // Перенаправляем на страницу заказа с сообщением
            header("Location: user_order_details.php?id=$order_id&success=1");
            exit;
            
        } catch (Exception $e) {
            $orders_pdo->rollBack();
            $error = 'Ошибка при оформлении заказа: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Оформление заказа</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
</head>
<body>

    
    <div class="container">
        <h1><i class="fas fa-check-circle"></i> Оформление заказа</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
        <div class="card-title">Состав заказа</div>

        <?php 
        $total = 0;
        foreach ($products as $product): 
            $qty = $_SESSION['cart'][$product['product_id']];
            $subtotal = $product['price'] * $qty;
            $total += $subtotal;
        ?>
        <div class="order-item">
            <div class="item-name"><?= htmlspecialchars($product['product_name']) ?></div>
            <div class="item-details">
                <span class="item-price"><?= number_format($product['price'], 2) ?> руб × <?= $qty ?></span>
                <span class="item-subtotal">= <?= number_format($subtotal, 2) ?> руб</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div style="text-align: right; margin-top: 15px; font-size: 1.4rem;">
        <strong>Итого: <span class="price"><?= number_format($total, 2) ?></span> руб</strong>
    </div>
</div>
                   

            <div class="container">
                <div class="card-title">Данные для доставки</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Адрес доставки</label>
                        <textarea name="shipping_address" placeholder="Улица, дом, квартира, город..." required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Примечания к заказу (необязательно)</label>
                        <textarea name="notes" placeholder="Пожелания, комментарии..."></textarea>
                    </div>
                    
                    <div style="display: flex; justify-content: center; gap: 20px; margin-top: 20px; flex-wrap: wrap;">
    <button type="submit" name="place_order" class="btn btn-success" style="min-width: 200px;">
        <i class="fas fa-check-circle"></i> Подтвердить заказ
    </button>
    <a href="user_cart.php" class="btn btn-edit" style="min-width: 200px;">
        <i class="fas fa-arrow-left"></i> Вернуться в корзину
    </a>
</div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'game_modal.php'; ?>
</body>
</html>