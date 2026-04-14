<?php
require 'user_config.php';

$products = $pdo->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.is_available = 1 
    ORDER BY p.product_id DESC
")->fetchAll();

$title = '🌸 Магазин товаров';
include 'header.php';
?>

<div class="container">
    <div class="anime-header">
        <div class="anime-logo">🌸 Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</div>
    </div>
    
    <h2><i class="fas fa-boxes"></i> Все товары</h2>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Товаров пока нет</div>
    <?php else: ?>
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-card">
            <div class="product-image">
                <?php if ($product['image_url']): ?>
                    <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>">
                <?php else: ?>
                    <div class="no-image"><i class="fas fa-image"></i></div>
                <?php endif; ?>
                <?php if ($product['stock_quantity'] == 0): ?>
                    <div class="out-of-stock">Нет в наличии</div>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <h3><?= htmlspecialchars($product['product_name']) ?></h3>
                <div class="product-category"><?= htmlspecialchars($product['category_name'] ?? 'Без категории') ?></div>
                <div class="product-description"><?= htmlspecialchars(substr($product['description'] ?? '', 0, 80)) ?>...</div>
                <div class="product-footer">
                    <div class="product-price"><?= number_format($product['price'], 2) ?> руб</div>
                    <div class="product-stock <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>"><?= $product['stock_quantity'] ?> шт.</div>
                </div>
                <?php if ($product['stock_quantity'] > 0): ?>
                    <a href="user_cart.php?add=<?= $product['product_id'] ?>" class="btn btn-success btn-small" style="margin-top: 10px; width: 100%;"><i class="fas fa-cart-plus"></i> В корзину</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php include 'game_modal.php'; ?>

<style>
.anime-header { text-align: center; margin-bottom: 30px; }
.anime-logo { font-family: 'M PLUS Rounded 1c', sans-serif; font-size: 2rem; color: var(--red-heart); }
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
    box-shadow: 0 20px 40px var(--shadow-pink);
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
.product-info {
    padding: 20px;
}
.product-price {
    color: var(--red-heart);
    font-weight: 800;
    font-size: 1.4rem;
}
.btn-small {
    padding: 8px 20px;
    font-size: 0.85rem;
}
</style>
<?php include 'game_modal.php'; ?>
</body>
</html>