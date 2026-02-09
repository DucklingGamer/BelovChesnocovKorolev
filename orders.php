<?php
require 'config.php';

// Получение данных для форм
$clients = $pdo->query("SELECT * FROM clients WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$products = $pdo->query("SELECT * FROM products WHERE is_available = 1 AND stock_quantity > 0 ORDER BY product_name")->fetchAll();
$services = $pdo->query("SELECT * FROM additional_services WHERE is_available = 1 ORDER BY service_name")->fetchAll();

// Создание заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $pdo->beginTransaction();
    
    try {
        // Создание заказа
        $stmt = $pdo->prepare("
            INSERT INTO orders (client_id, shipping_address, total_amount, status, estimated_delivery_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $total_amount = 0;
        
        // Расчет стоимости товаров
        if (isset($_POST['products'])) {
            foreach ($_POST['products'] as $product_id => $item) {
                if ($item['quantity'] > 0) {
                    $product = $pdo->query("SELECT price FROM products WHERE product_id = $product_id")->fetch();
                    $total_amount += $product['price'] * $item['quantity'];
                }
            }
        }
        
        // Расчет стоимости услуг
        if (isset($_POST['services'])) {
            foreach ($_POST['services'] as $service_id => $item) {
                if ($item['quantity'] > 0) {
                    $service = $pdo->query("SELECT price FROM additional_services WHERE service_id = $service_id")->fetch();
                    $total_amount += $service['price'] * $item['quantity'];
                }
            }
        }
        
        $stmt->execute([
            $_POST['client_id'],
            $_POST['shipping_address'],
            $total_amount,
            $_POST['status'],
            $_POST['estimated_delivery_date'] ?: null,
            $_POST['notes']
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Добавление товаров в заказ
        if (isset($_POST['products'])) {
            foreach ($_POST['products'] as $product_id => $item) {
                if ($item['quantity'] > 0) {
                    $product = $pdo->query("SELECT price FROM products WHERE product_id = $product_id")->fetch();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, quantity, unit_price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$order_id, $product_id, $item['quantity'], $product['price']]);
                    
                    // Обновление количества на складе
                    $pdo->exec("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE product_id = $product_id");
                }
            }
        }
        
        // Добавление услуг в заказ
        if (isset($_POST['services'])) {
            foreach ($_POST['services'] as $service_id => $item) {
                if ($item['quantity'] > 0) {
                    $service = $pdo->query("SELECT price FROM additional_services WHERE service_id = $service_id")->fetch();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO order_services (order_id, service_id, quantity, service_price) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$order_id, $service_id, $item['quantity'], $service['price']]);
                }
            }
        }
        
        $pdo->commit();
        header("Location: orders.php?success=1&order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Обновление статуса заказа
if (isset($_GET['update_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$_GET['status'], $_GET['update_status']]);
    header("Location: orders.php?success=2");
    exit;
}

// Получение всех заказов
$orders = $pdo->query("
    SELECT o.*, c.full_name 
    FROM orders o 
    JOIN clients c ON o.client_id = c.client_id 
    ORDER BY o.order_date DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Заказы - Кавай Магазин</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <!-- В index.php в секции head -->
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
            <a href="orders.php" class="active"><i class="fas fa-shopping-cart"></i> Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Управление заказами</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> Ошибка: <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                if ($_GET['success'] == 1) echo "🌸 Заказ #" . ($_GET['order_id'] ?? '') . " успешно создан!";
                elseif ($_GET['success'] == 2) echo "✨ Статус заказа успешно обновлен!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card-grid">
            <div class="card" style="grid-column: span 2;">
                <div class="card-title"><i class="fas fa-plus-circle"></i> Создать новый заказ</div>
                <form method="POST" id="orderForm">
                    <div class="form-group">
                        <label>Клиент</label>
                        <select name="client_id" required id="clientSelect">
                            <option value="">-- Выберите клиента --</option>
                            <?php foreach ($clients as $client): ?>
                            <option value="<?= $client['client_id'] ?>">
                                <?= htmlspecialchars($client['full_name']) ?> (<?= $client['email'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Адрес доставки</label>
                        <textarea name="shipping_address" placeholder="Адрес доставки" required></textarea>
                    </div>
                    
                    <h3><i class="fas fa-box"></i> Товары в заказе</h3>
                    <table>
                        <tr>
                            <th>Товар</th>
                            <th>Цена</th>
                            <th>На складе</th>
                            <th>Количество</th>
                            <th>Сумма</th>
                        </tr>
                        <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_name']) ?></td>
                            <td class="price"><?= number_format($product['price'], 2) ?> руб</td>
                            <td><span class="status status-delivered"><?= $product['stock_quantity'] ?> шт.</span></td>
                            <td>
                                <input type="number" 
                                       name="products[<?= $product['product_id'] ?>][quantity]" 
                                       value="0" 
                                       min="0" 
                                       max="<?= $product['stock_quantity'] ?>"
                                       class="qty-input product-qty"
                                       data-price="<?= $product['price'] ?>"
                                       data-type="product">
                            </td>
                            <td class="product-subtotal" data-id="<?= $product['product_id'] ?>">0 руб</td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <h3><i class="fas fa-concierge-bell"></i> Дополнительные услуги</h3>
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
                            <td class="price"><?= number_format($service['price'], 2) ?> руб</td>
                            <td>
                                <input type="number" 
                                       name="services[<?= $service['service_id'] ?>][quantity]" 
                                       value="0" 
                                       min="0"
                                       class="qty-input service-qty"
                                       data-price="<?= $service['price'] ?>"
                                       data-type="service">
                            </td>
                            <td class="service-subtotal" data-id="<?= $service['service_id'] ?>">0 руб</td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    
                    <div class="form-group">
                        <label>Статус заказа</label>
                        <select name="status">
                            <option value="pending">Ожидание</option>
                            <option value="processing">В обработке</option>
                            <option value="shipped">Отправлен</option>
                            <option value="delivered">Доставлен</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Предполагаемая дата доставки</label>
                        <input type="date" name="estimated_delivery_date">
                    </div>
                    
                    <div class="form-group">
                        <label>Примечания</label>
                        <textarea name="notes" placeholder="Дополнительные заметки..."></textarea>
                    </div>
                    
                    <div style="background: var(--pink-light); padding: 20px; border-radius: 15px; margin: 20px 0;">
                        <h3 style="margin: 0 0 10px 0;">Итого: <span id="totalAmount" class="price">0</span> руб</h3>
                        <input type="hidden" name="total_amount" id="totalAmountInput">
                    </div>
                    
                    <button type="submit" name="create_order" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Создать заказ
                    </button>
                </form>
            </div>
        </div>
        
        <h2><i class="fas fa-list"></i> Список заказов</h2>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Клиент</th>
                <th>Дата</th>
                <th>Сумма</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($orders as $order): ?>
            <tr>
                <td>#<?= $order['order_id'] ?></td>
                <td>
                    <strong><?= htmlspecialchars($order['full_name']) ?></strong>
                    <br><small style="color: var(--purple-lavender);"><?= date('d.m.Y H:i', strtotime($order['order_date'])) ?></small>
                </td>
                <td><?= date('d.m.Y', strtotime($order['order_date'])) ?></td>
                <td class="price"><?= number_format($order['total_amount'], 2) ?> руб</td>
                <td>
                    <span class="status status-<?= $order['status'] ?>">
                        <?php 
                        $status_names = [
                            'pending' => 'Ожидание',
                            'processing' => 'Обработка',
                            'shipped' => 'Отправлен',
                            'delivered' => 'Доставлен',
                            'cancelled' => 'Отменен'
                        ];
                        echo $status_names[$order['status']] ?? $order['status'];
                        ?>
                    </span>
                </td>
                <td>
                    <div class="action-buttons">
                        <select onchange="updateOrderStatus(<?= $order['order_id'] ?>, this.value)" 
                                class="btn btn-edit btn-small" 
                                style="padding: 5px 10px;">
                            <option value="">Изменить статус</option>
                            <option value="pending">Ожидание</option>
                            <option value="processing">Обработка</option>
                            <option value="shipped">Отправлен</option>
                            <option value="delivered">Доставлен</option>
                            <option value="cancelled">Отменить</option>
                        </select>
                        
                        <a href="order_details.php?id=<?= $order['order_id'] ?>" 
                           class="btn btn-edit btn-small">
                            <i class="fas fa-eye"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <script>
    function calculateTotal() {
        let total = 0;
        
        // Сумма товаров
        document.querySelectorAll('.product-qty').forEach(input => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price) || 0;
            const subtotal = qty * price;
            total += subtotal;
            
            // Обновляем отображение подытога
            const subtotalEl = document.querySelector(`.product-subtotal[data-id="${input.name.match(/\[(\d+)\]/)[1]}"]`);
            if (subtotalEl) {
                subtotalEl.textContent = subtotal.toFixed(2) + ' руб';
            }
        });
        
        // Сумма услуг
        document.querySelectorAll('.service-qty').forEach(input => {
            const qty = parseInt(input.value) || 0;
            const price = parseFloat(input.dataset.price) || 0;
            const subtotal = qty * price;
            total += subtotal;
            
            // Обновляем отображение подытога
            const subtotalEl = document.querySelector(`.service-subtotal[data-id="${input.name.match(/\[(\d+)\]/)[1]}"]`);
            if (subtotalEl) {
                subtotalEl.textContent = subtotal.toFixed(2) + ' руб';
            }
        });
        
        // Обновляем общую сумму
        document.getElementById('totalAmount').textContent = total.toFixed(2);
        document.getElementById('totalAmountInput').value = total.toFixed(2);
    }
    
    function updateOrderStatus(orderId, status) {
        if (status && confirm('🌸 Изменить статус заказа #' + orderId + '?')) {
            window.location.href = `orders.php?update_status=${orderId}&status=${status}`;
        }
    }
    
    // Инициализация
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('input', calculateTotal);
    });
    
    calculateTotal();
    
    // Автозаполнение адреса при выборе клиента
    document.getElementById('clientSelect').addEventListener('change', function() {
        const clientId = this.value;
        if (clientId) {
            fetch('api.php?action=get_client_address&id=' + clientId)
                .then(response => response.json())
                .then(data => {
                    if (data.address) {
                        document.querySelector('textarea[name="shipping_address"]').value = data.address;
                    }
                });
        }
    });
    </script>
</body>
</html>