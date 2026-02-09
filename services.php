<?php
require 'config.php';

// Добавление услуги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO additional_services (service_name, description, price, is_available) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $_POST['service_name'],
        $_POST['description'],
        $_POST['price'],
        isset($_POST['is_available']) ? 1 : 0
    ]);
    header("Location: services.php?success=1");
    exit;
}

// Удаление услуги
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM additional_services WHERE service_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: services.php?success=2");
    exit;
}

// Получение всех услуг
$services = $pdo->query("SELECT * FROM additional_services ORDER BY service_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Услуги - Кавай Магазин</title>
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
            <a href="services.php" class="active"><i class="fas fa-concierge-bell"></i> Услуги</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-concierge-bell"></i> Управление услугами</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] == 1 ? "🌸 Услуга успешно добавлена!" : "🗑️ Услуга успешно удалена!" ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title"><i class="fas fa-plus-circle"></i> Добавить услугу</div>
            <form method="POST">
                <div class="form-group">
                    <label>Название услуги</label>
                    <input type="text" name="service_name" placeholder="Например: Доставка курьером" required>
                </div>
                
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" placeholder="Подробное описание услуги..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Цена (руб)</label>
                    <input type="number" step="0.01" min="0" name="price" placeholder="299.99" required>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_available" checked> Доступна
                    </label>
                </div>
                
                <button type="submit" name="add" class="btn btn-success">
                    <i class="fas fa-plus"></i> Добавить услугу
                </button>
            </form>
        </div>
        
        <h2><i class="fas fa-list"></i> Список услуг</h2>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Цена</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($services as $service): ?>
            <tr>
                <td>#<?= $service['service_id'] ?></td>
                <td><strong><?= htmlspecialchars($service['service_name']) ?></strong></td>
                <td><?= htmlspecialchars($service['description'] ?? '—') ?></td>
                <td class="price"><?= number_format($service['price'], 2) ?> руб</td>
                <td>
                    <?php if ($service['is_available']): ?>
                        <span class="status status-delivered">Доступна</span>
                    <?php else: ?>
                        <span class="status status-pending">Недоступна</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?delete=<?= $service['service_id'] ?>" 
                           class="btn btn-delete btn-small"
                           onclick="return confirm('🌸 Удалить услугу <?= htmlspecialchars($service['service_name']) ?>?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</body>
</html>