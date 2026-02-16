<?php
require 'user_config.php';

$category_id = $_GET['id'] ?? 0;

// Получаем информацию о категории
$category = $pdo->prepare("SELECT * FROM categories WHERE category_id = ?");
$category->execute([$category_id]);
$category = $category->fetch();

if (!$category) {
    header('Location: user_categories.php');
    exit;
}

// Получаем товары категории
$products = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND is_available = 1 
    ORDER BY product_name
");
$products->execute([$category_id]);
$products = $products->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 <?= htmlspecialchars($category['category_name']) ?></title>
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
        <div style="margin-bottom: 20px;">
            <a href="user_categories.php" class="btn btn-edit btn-small">
                <i class="fas fa-arrow-left"></i> Назад к категориям
            </a>
        </div>
        
        <h1><i class="fas fa-tag"></i> <?= htmlspecialchars($category['category_name']) ?></h1>
        
        <?php if ($category['description']): ?>
            <div class="card" style="margin-bottom: 20px;">
                <p><?= nl2br(htmlspecialchars($category['description'])) ?></p>
            </div>
        <?php endif; ?>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> В этой категории пока нет товаров
            </div>
        <?php else: ?>
        <h2>Товары в этой категории (<?= count($products) ?>)</h2>
        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($product['image_url']): ?>
                        <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                    <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3><?= htmlspecialchars($product['product_name']) ?></h3>
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
        <?php endif; ?>
    </div>
</body>
</html>