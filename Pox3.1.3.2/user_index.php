<?php
require 'user_config.php';

// Получаем все доступные товары из БД КАТАЛОГА
$products = $pdo->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    WHERE p.is_available = 1 
    ORDER BY p.product_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Магазин товаров</title>
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
            <!-- ДОБАВЛЕНО: кнопка ручного запуска игры -->
            <a href="#" onclick="openColorGame(false); return false;" style="background: linear-gradient(145deg, var(--green-matcha), var(--blue-sky));">
                <i class="fas fa-palette"></i> Игра-перерыв
            </a>
            <a href="user_logout.php" class="logout-btn">Выход</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="anime-header">
            <div class="anime-logo">🌸 Добро пожаловать, <?= htmlspecialchars($_SESSION['username']) ?>!</div>
        </div>
        
        <h2><i class="fas fa-boxes"></i> Все товары</h2>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Товаров пока нет
            </div>
        <?php else: ?>
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
                        <div class="product-stock <?= $product['stock_quantity'] > 0 ? 'in-stock' : 'out-stock' ?>">
                            <?= $product['stock_quantity'] ?> шт.
                        </div>
                    </div>
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <a href="user_cart.php?add=<?= $product['product_id'] ?>" class="btn btn-success btn-small" style="margin-top: 10px; width: 100%;">
                            <i class="fas fa-cart-plus"></i> В корзину
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Подключаем модальное окно с игрой -->
    <?php include 'game_modal.php'; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Проверка результата состояния из сессии
        <?php if (isset($_SESSION['game_state_result']) && $_SESSION['game_state_result']['need_message']): ?>
            <?php $res = $_SESSION['game_state_result']; ?>
            <?php if ($res['force_open_game']): ?>
                // Принудительное открытие игры
                setTimeout(function() {
                    openColorGame(true); // forced = true, игра будет случайная
                }, 500);
            <?php endif; ?>
            // Показываем уведомление о смене состояния
            showGameStateMessage("<?= addslashes($res['message']) ?>", <?= $res['force_open_game'] ? 'true' : 'false' ?>);
            <?php unset($_SESSION['game_state_result']); ?>
        <?php endif; ?>
    });

    function showGameStateMessage(message, isForced) {
        const notification = document.createElement('div');
        notification.className = 'game-state-notification';
        notification.innerHTML = `
            <div class="reminder-content">
                <div class="reminder-icon">${isForced ? '🎮' : '⏰'}</div>
                <div class="reminder-text">${message}</div>
                <button onclick="this.parentElement.parentElement.remove()" class="btn btn-edit btn-small">OK</button>
            </div>
        `;
        document.body.appendChild(notification);
        setTimeout(() => notification.classList.add('show'), 100);
        setTimeout(() => {
            if (notification.parentNode) notification.remove();
        }, 8000);
    }
    </script>
    
    <style>
    .game-state-notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        max-width: 400px;
        background: white;
        border-radius: 20px;
        border: 3px solid var(--pink-sakura);
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        z-index: 10002;
        transition: transform 0.4s ease-out;
        animation: bounceInDown 0.5s ease-out;
    }
    .game-state-notification.show { transform: translateX(-50%) translateY(0); }
    @keyframes bounceInDown {
        0% { transform: translateX(-50%) translateY(-100px); opacity: 0; }
        60% { transform: translateX(-50%) translateY(10px); opacity: 1; }
        100% { transform: translateX(-50%) translateY(0); }
    }
    </style>
</body>
</html>