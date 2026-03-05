<?php
require 'user_config.php';

// Получаем доступные услуги из БД КАТАЛОГА (project_Chesnokov)
$services = $pdo->query("
    SELECT * FROM additional_services 
    WHERE is_available = 1 
    ORDER BY service_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Дополнительные услуги</title>
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
        <h1><i class="fas fa-concierge-bell"></i> Дополнительные услуги</h1>
        
        <?php if (empty($services)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Услуг пока нет
            </div>
        <?php else: ?>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
            <div class="card">
                <div class="card-title"><?= htmlspecialchars($service['service_name']) ?></div>
                <div class="card-content">
                    <?php if ($service['description']): ?>
                        <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>
                    <?php endif; ?>
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span class="price"><?= number_format($service['price'], 2) ?> руб</span>
                        <span class="status status-delivered">Доступно</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>