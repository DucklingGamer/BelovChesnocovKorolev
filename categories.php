<?php
require 'config.php';

// Добавление категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO categories (category_name, description, parent_category_id) VALUES (?, ?, ?)");
    $stmt->execute([
        $_POST['category_name'],
        $_POST['description'],
        $_POST['parent_category_id'] ?: null
    ]);
    header("Location: categories.php?success=1");
    exit;
}

// Удаление категории
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: categories.php?success=2");
    exit;
}

// Получение всех категорий
$categories = $pdo->query("
    SELECT c1.*, c2.category_name as parent_name 
    FROM categories c1 
    LEFT JOIN categories c2 ON c1.parent_category_id = c2.category_id 
    ORDER BY c1.category_name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Категории - Кавай Магазин</title>
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
            <a href="categories.php" class="active"><i class="fas fa-tags"></i> Категории</a>
            <a href="products.php"><i class="fas fa-box-open"></i> Товары</a>
            <a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-tags"></i> Управление категориями</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] == 1 ? "🌸 Категория успешно добавлена!" : "🗑️ Категория успешно удалена!" ?>
            </div>
        <?php endif; ?>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-title"><i class="fas fa-plus-circle"></i> Добавить категорию</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Название категории</label>
                        <input type="text" name="category_name" placeholder="Например: Аниме-фигурки" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Описание</label>
                        <textarea name="description" placeholder="Описание категории..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Родительская категория (необязательно)</label>
                        <select name="parent_category_id">
                            <option value="">-- Без родительской категории --</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php if (!$cat['parent_category_id']): ?>
                                    <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" name="add" class="btn btn-success">
                        <i class="fas fa-plus"></i> Добавить категорию
                    </button>
                </form>
            </div>
            
            <div class="card">
                <div class="card-title"><i class="fas fa-info-circle"></i> Информация</div>
                <div class="card-content">
                    <p>🌸 Категории помогают организовать товары</p>
                    <p>✨ Можно создавать вложенные категории</p>
                    <p>🎀 У каждой категории может быть родительская</p>
                    <p>🍥 Удаление категории не удаляет товары</p>
                </div>
            </div>
        </div>
        
        <h2><i class="fas fa-list"></i> Список категорий</h2>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Описание</th>
                <th>Родительская</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($categories as $cat): ?>
            <tr>
                <td>#<?= $cat['category_id'] ?></td>
                <td><strong><?= htmlspecialchars($cat['category_name']) ?></strong></td>
                <td><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                <td>
                    <?php if ($cat['parent_name']): ?>
                        <span class="status status-processing"><?= htmlspecialchars($cat['parent_name']) ?></span>
                    <?php else: ?>
                        <span class="status status-delivered">Основная</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?delete=<?= $cat['category_id'] ?>" 
                           class="btn btn-delete btn-small"
                           onclick="return confirm('🌸 Удалить категорию <?= htmlspecialchars($cat['category_name']) ?>?')">
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