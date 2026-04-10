<?php
require 'user_config.php';
$title = '🌸 Категории товаров';
include 'header.php';
?>

<div class="container">
    <h1><i class="fas fa-tags"></i> Категории товаров</h1>
    
    <?php
    $categories = $pdo->query("
        SELECT c.*, COUNT(p.product_id) as products_count
        FROM categories c
        LEFT JOIN products p ON c.category_id = p.category_id AND p.is_available = 1
        GROUP BY c.category_id
        ORDER BY c.category_name
    ")->fetchAll();
    ?>
    
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

<?php include 'game_modal.php'; ?>
</body>
</html>