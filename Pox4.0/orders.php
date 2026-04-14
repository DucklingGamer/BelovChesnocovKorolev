<?php
require 'config.php';
include 'header.php';

// Получение данных для форм
$clients = $orders_pdo->query("SELECT * FROM clients WHERE is_active = 1 ORDER BY full_name")->fetchAll();
$products = $catalog_pdo->query("SELECT * FROM products WHERE is_available = 1 AND stock_quantity > 0 ORDER BY product_name")->fetchAll();
$services = $catalog_pdo->query("SELECT * FROM additional_services WHERE is_available = 1 ORDER BY service_name")->fetchAll();

// Создание заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_order'])) {
    $orders_pdo->beginTransaction();
    try {
        $total_amount = 0;
        $items_data = [];
        
        // Расчет стоимости товаров и сбор данных
        if (isset($_POST['products'])) {
            foreach ($_POST['products'] as $product_id => $item) {
                if ($item['quantity'] > 0) {
                    $product = $catalog_pdo->query("SELECT price, product_name, stock_quantity FROM products WHERE product_id = $product_id")->fetch();
                    if ($product['stock_quantity'] < $item['quantity']) {
                        throw new Exception("Недостаточно товара '{$product['product_name']}' на складе");
                    }
                    $total_amount += $product['price'] * $item['quantity'];
                    $items_data[] = [
                        'product_id' => $product_id,
                        'product_name' => $product['product_name'],
                        'unit_price' => $product['price'],
                        'quantity' => $item['quantity']
                    ];
                }
            }
        }
        
        // Расчет стоимости услуг
        $services_data = [];
        if (isset($_POST['services'])) {
            foreach ($_POST['services'] as $service_id => $item) {
                if ($item['quantity'] > 0) {
                    $service = $catalog_pdo->query("SELECT price, service_name FROM additional_services WHERE service_id = $service_id")->fetch();
                    $total_amount += $service['price'] * $item['quantity'];
                    $services_data[] = [
                        'service_id' => $service_id,
                        'service_name' => $service['service_name'],
                        'service_price' => $service['price'],
                        'quantity' => $item['quantity']
                    ];
                }
            }
        }
        
        // Создание заказа
        $stmt = $orders_pdo->prepare("
            INSERT INTO orders (client_id, shipping_address, total_amount, status, estimated_delivery_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['client_id'],
            $_POST['shipping_address'],
            $total_amount,
            $_POST['status'],
            $_POST['estimated_delivery_date'] ?: null,
            $_POST['notes']
        ]);
        
        $order_id = $orders_pdo->lastInsertId();
        
        // Добавление товаров в order_items
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
            // Уменьшение остатка в catalog_pdo
            $update_stmt = $catalog_pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?");
            $update_stmt->execute([$item['quantity'], $item['product_id']]);
        }
        
        // Добавление услуг в order_services
        if (!empty($services_data)) {
            $stmt_service = $orders_pdo->prepare("
                INSERT INTO order_services (order_id, service_id, service_name, service_price, quantity) 
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($services_data as $service) {
                $stmt_service->execute([
                    $order_id,
                    $service['service_id'],
                    $service['service_name'],
                    $service['service_price'],
                    $service['quantity']
                ]);
            }
        }
        
        $orders_pdo->commit();
        header("Location: orders.php?success=1&order_id=" . $order_id);
        exit;
        
    } catch (Exception $e) {
        $orders_pdo->rollBack();
        $error = $e->getMessage();
    }
}

// Обновление статуса заказа
if (isset($_GET['update_status'])) {
    $stmt = $orders_pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$_GET['status'], $_GET['update_status']]);
    header("Location: orders.php?success=2");
    exit;
}

// Получение всех заказов
$orders = $orders_pdo->query("
    SELECT o.*, c.full_name 
    FROM orders o 
    JOIN clients c ON o.client_id = c.client_id 
    ORDER BY o.order_date DESC
")->fetchAll();

// Определение статусов для отображения
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
    <title>🌸 Заказы - Кавай Магазин</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <style>
        /* Двухколоночный макет */
        .orders-layout {
            display: grid;
            grid-template-columns: minmax(380px, 450px) 1fr;
            gap: 25px;
            margin-top: 20px;
            align-items: start;
        }
        
        /* Левая колонка (форма) */
        .order-form-col {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 100px);
            overflow-y: auto;
            padding-right: 5px;
        }
        
        /* Правая колонка (список) */
        .order-list-col {
            min-width: 0; /* предотвращает выпадение */
        }
        
        /* Стили для таблиц внутри формы (чтобы не разрывались) */
        .order-form-col table {
            font-size: 0.9rem;
        }
        
        .order-form-col th,
        .order-form-col td {
            padding: 10px 8px;
        }
        
        .order-form-col .qty-input {
            width: 60px;
        }
        
        /* Уменьшаем отступы в форме для компактности */
        .order-form-col .form-group {
            margin-bottom: 15px;
        }
        
        .order-form-col textarea {
            min-height: 80px;
        }
        
        /* Адаптивность */
        @media (max-width: 900px) {
            .orders-layout {
                grid-template-columns: 1fr;
            }
            .order-form-col {
                position: static;
                max-height: none;
                overflow-y: visible;
            }
        }

        .container {
    max-width: 1600px;
    padding: 40px;
}

.card-grid {
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}

input, select, textarea {
    padding: 18px 25px;
    font-size: 1.1rem;
}

td, th {
    padding: 18px 25px;
}
    </style>
</head>
<body>
    
    
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
        
        <!-- Двухколоночный макет -->
        <div class="orders-layout">
            <!-- Левая колонка: форма создания заказа -->
            <div class="order-form-col">
                <div class="card">
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
                        
                        <h3><i class="fas fa-box"></i> Товары</h3>
                        <div style="overflow-x: auto; max-height: 300px; overflow-y: auto; border: 1px solid var(--pink-light); border-radius: 10px; margin-bottom: 15px;">
                            <table style="margin: 0; border: none;">
                                <tr>
                                    <th>Товар</th>
                                    <th>Цена</th>
                                    <th>В наличии</th>
                                    <th>Кол-во</th>
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
                                               data-type="product"
                                               style="width: 60px;">
                                    </td>
                                    <td class="product-subtotal" data-id="<?= $product['product_id'] ?>">0 руб</td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        
                        <h3><i class="fas fa-concierge-bell"></i> Услуги</h3>
                        <div style="overflow-x: auto; max-height: 200px; overflow-y: auto; border: 1px solid var(--pink-light); border-radius: 10px; margin-bottom: 15px;">
                            <table style="margin: 0; border: none;">
                                <tr>
                                    <th>Услуга</th>
                                    <th>Цена</th>
                                    <th>Кол-во</th>
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
                                               data-type="service"
                                               style="width: 60px;">
                                    </td>
                                    <td class="service-subtotal" data-id="<?= $service['service_id'] ?>">0 руб</td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                        
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
                        
                        <div style="background: var(--pink-light); padding: 15px; border-radius: 15px; margin: 20px 0;">
                            <h4 style="margin: 0;">Итого: <span id="totalAmount" class="price">0</span> руб</h4>
                            <input type="hidden" name="total_amount" id="totalAmountInput">
                        </div>
                        
                        <button type="submit" name="create_order" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-check-circle"></i> Создать заказ
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Правая колонка: список заказов -->
            <div class="order-list-col">
                <div class="card">
                    <div class="card-title"><i class="fas fa-list"></i> Список заказов</div>
                    
                    <?php if (empty($orders)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> Заказов пока нет.
                        </div>
                    <?php else: ?>
                        <div style="overflow-x: auto;">
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
                                            <?= $status_names[$order['status']] ?? $order['status'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <select onchange="updateOrderStatus(<?= $order['order_id'] ?>, this.value)" 
                                                    class="btn btn-edit btn-small" 
                                                    style="padding: 5px 10px;">
                                                <option value="">Изменить статус</option>
                                                <?php foreach ($status_names as $key => $name): ?>
                                                    <option value="<?= $key ?>"><?= $name ?></option>
                                                <?php endforeach; ?>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.qty-input').forEach(input => {
            input.addEventListener('input', calculateTotal);
        });
        calculateTotal();
        
        // Автозаполнение адреса при выборе клиента
        document.getElementById('clientSelect')?.addEventListener('change', function() {
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
    });
    </script>
    <?php include 'game_modal.php'; ?>
</body>
</html>