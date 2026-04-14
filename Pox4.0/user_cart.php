<?php
require 'user_config.php';
include 'header.php';

// Инициализация корзины в сессии
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Обработка действий с корзиной
$redirect = false;

// Добавление товара
if (isset($_GET['add'])) {
    $product_id = (int)$_GET['add'];
    if ($product_id > 0) {
        // Проверим, существует ли товар и доступен ли он
        $stmt = $pdo->prepare("SELECT product_id, stock_quantity FROM products WHERE product_id = ? AND is_available = 1");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        if ($product) {
            if (!isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = 1;
            } else {
                // Можно добавить ограничение на максимальное количество (например, не больше наличия)
                if ($_SESSION['cart'][$product_id] < $product['stock_quantity']) {
                    $_SESSION['cart'][$product_id]++;
                }
            }
        }
    }
    $redirect = true;
}

// Удаление товара
if (isset($_GET['remove'])) {
    $product_id = (int)$_GET['remove'];
    unset($_SESSION['cart'][$product_id]);
    $redirect = true;
}

// Очистка корзины
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    $redirect = true;
}

// Обновление количества (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $product_id => $qty) {
        $qty = (int)$qty;
        $product_id = (int)$product_id;
        if ($qty <= 0) {
            unset($_SESSION['cart'][$product_id]);
        } else {
            // Проверим наличие на складе
            $stmt = $pdo->prepare("SELECT stock_quantity FROM products WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch();
            if ($product && $qty <= $product['stock_quantity']) {
                $_SESSION['cart'][$product_id] = $qty;
            } else {
                // Если запросили больше, чем есть, устанавливаем максимально доступное
                $_SESSION['cart'][$product_id] = $product['stock_quantity'];
            }
        }
    }
    $redirect = true;
}

if ($redirect) {
    header('Location: user_cart.php');
    exit;
}

// Получаем полную информацию о товарах в корзине
$cart_items = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $ids = implode(',', array_keys($_SESSION['cart']));
    $products = $pdo->query("SELECT * FROM products WHERE product_id IN ($ids) AND is_available = 1")->fetchAll();
    foreach ($products as $product) {
        $product_id = $product['product_id'];
        $quantity = $_SESSION['cart'][$product_id];
        // Проверим, не превышает ли количество остаток
        if ($quantity > $product['stock_quantity']) {
            $quantity = $product['stock_quantity'];
            $_SESSION['cart'][$product_id] = $quantity;
            if ($quantity == 0) {
                unset($_SESSION['cart'][$product_id]);
                continue;
            }
        }
        $subtotal = $product['price'] * $quantity;
        $total += $subtotal;
        $cart_items[] = [
            'product' => $product,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Корзина - Кавай Магазин</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
</head>
<body>
    
    
    <div class="container">
        <h1><i class="fas fa-shopping-cart"></i> Корзина</h1>
        
        <?php if (empty($cart_items)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Ваша корзина пуста.
                <a href="user_index.php" class="btn btn-success btn-small" style="margin-left: 15px;">🌸 Продолжить покупки</a>
            </div>
        <?php else: ?>
        
        <form method="POST" action="user_cart.php">
            <table>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                    <th>Действие</th>
                </tr>
                <?php foreach ($cart_items as $item): 
                    $product = $item['product'];    
                ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center;">
                            <?php if ($product['image_url']): ?>
                                <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 10px; margin-right: 10px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: var(--pink-light); border-radius: 10px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                    <i class="fas fa-image" style="color: var(--purple-lavender);"></i>
                                </div>
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                        </div>
                    </td>
                    <td class="price"><?= number_format($product['price'], 2) ?> руб</td>
                    <td>
                        <input type="number" name="quantity[<?= $product['product_id'] ?>]" 
                               value="<?= $item['quantity'] ?>" min="0" max="<?= $product['stock_quantity'] ?>" 
                               style="width: 70px; text-align: center;">
                        <span style="font-size: 0.8rem; color: var(--purple-lavender);">(в наличии <?= $product['stock_quantity'] ?>)</span>
                    </td>
                    <td class="price"><?= number_format($item['subtotal'], 2) ?> руб</td>
                    <td>
                        <a href="?remove=<?= $product['product_id'] ?>" class="btn btn-delete btn-small" onclick="return confirm('Убрать товар из корзины?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            
            <div style="display: flex; justify-content: space-between; align-items: center; margin: 20px 0;">
                <div>
                    <button type="submit" name="update" class="btn btn-edit">
                        <i class="fas fa-sync-alt"></i> Пересчитать
                    </button>
                    <a href="?clear=1" class="btn btn-delete" onclick="return confirm('Очистить корзину?')">
                        <i class="fas fa-trash"></i> Очистить
                    </a>
                </div>
                <div style="background: var(--pink-light); padding: 15px 25px; border-radius: 15px;">
                    <strong>Итого: <span class="price" style="font-size: 1.8rem;"><?= number_format($total, 2) ?></span> руб</strong>
                </div>
            </div>
        </form>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="user_index.php" class="btn btn-edit">
                <i class="fas fa-arrow-left"></i> Продолжить покупки
            </a>
            <a href="user_checkout.php" class="btn btn-success">
                <i class="fas fa-check-circle"></i> Оформить заказ
            </a>
        </div>
        
        <?php endif; ?>
    </div>
    <?php include 'game_modal.php'; ?>
</body>
</html>