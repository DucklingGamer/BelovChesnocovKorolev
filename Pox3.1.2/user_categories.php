<?php
require 'user_config.php';

// Получаем все категории из БД КАТАЛОГА (project_Chesnokov)
$categories = $pdo->query("
    SELECT c.*, COUNT(p.product_id) as products_count
    FROM categories c
    LEFT JOIN products p ON c.category_id = p.category_id AND p.is_available = 1
    GROUP BY c.category_id
    ORDER BY c.category_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Категории товаров</title>
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
        <a href="user_cart.php"><i class="fas fa-shopping-cart"></i> Корзина</a>
        <a href="user_orders.php"><i class="fas fa-shopping-cart"></i> Мои заказы</a>
        <a href="user_profile.php"><i class="fas fa-user"></i> Профиль</a>
        <a href="user_logout.php" class="logout-btn">Выход</a>
    </nav>
</div>
    
    <div class="container">
        <h1><i class="fas fa-tags"></i> Категории товаров</h1>
        
        <?php if (empty($categories)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Категорий нет
            </div>
        <?php else: ?>
        <div class="categories-grid">
            <?php foreach ($categories as $category): ?>
            <a href="user_category_products.php?id=<?= $category['category_id'] ?>" style="text-decoration: none;">
                <div class="category-card">
                    <div class="category-info">
                        <h3><?= htmlspecialchars($category['category_name']) ?></h3>
                        <?php if ($category['description']): ?>
                            <div class="category-description">
                                <?= htmlspecialchars(substr($category['description'], 0, 100)) ?>...
                            </div>
                        <?php endif; ?>
                        <div class="category-meta">
                            <span><i class="fas fa-box"></i> Товаров: <?= $category['products_count'] ?></span>
                        </div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>