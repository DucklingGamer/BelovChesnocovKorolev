<?php
require 'config.php';

// Получение категорий для селекта
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

// Проверяем наличие категорий
if (empty($categories)) {
    echo "<div class='alert alert-warning' style='margin: 20px;'>
        <i class='fas fa-exclamation-triangle'></i>
        <strong>Внимание:</strong> В базе данных нет категорий!<br>
        Сначала <a href='categories.php' style='color: var(--red-heart); font-weight: bold;'>добавьте категории</a>
    </div>";
}

// Добавление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $image_url = null;
    
    // Обработка загрузки изображения
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_extension = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid() . '_' . time() . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;
        
        // Разрешенные типы файлов
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array(strtolower($file_extension), $allowed_types)) {
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $file_path)) {
                $image_url = $file_path;
            }
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (product_name, description, price, stock_quantity, category_id, is_available, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['product_name'],
        $_POST['description'],
        $_POST['price'],
        $_POST['stock_quantity'],
        $_POST['category_id'],
        isset($_POST['is_available']) ? 1 : 0,
        $image_url
    ]);
    header("Location: products.php?success=1");
    exit;
}

// Удаление товара
if (isset($_GET['delete'])) {
    // Удаляем изображение товара при удалении
    $product = $pdo->query("SELECT image_url FROM products WHERE product_id = {$_GET['delete']}")->fetch();
    if ($product && $product['image_url'] && file_exists($product['image_url'])) {
        unlink($product['image_url']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: products.php?success=2");
    exit;
}

// Получение всех товаров с информацией о категории
$products = $pdo->query("
    SELECT p.*, c.category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.category_id 
    ORDER BY p.product_id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Товары - Кавай Магазин</title>
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
            <a href="products.php" class="active"><i class="fas fa-box-open"></i> Товары</a>
            <a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a>
            <a href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
        </nav>
    </div>
    
    <div class="container">
        <h1><i class="fas fa-box-open"></i> Управление товарами</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?= $_GET['success'] == 1 ? "🌸 Товар успешно добавлен!" : "🗑️ Товар успешно удален!" ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-title"><i class="fas fa-plus-circle"></i> Добавить новый товар</div>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Название товара</label>
                    <input type="text" name="product_name" placeholder="Например: Аниме-фигурка Наруто" required>
                </div>
                
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" placeholder="Подробное описание товара..."></textarea>
                </div>
                
                <div class="form-group">
                    <label>Цена (руб)</label>
                    <input type="number" step="0.01" min="0" name="price" placeholder="999.99" required>
                </div>
                
                <div class="form-group">
                    <label>Количество на складе</label>
                    <input type="number" min="0" name="stock_quantity" value="0" required>
                </div>
                
                <div class="form-group">
                    <label>Изображение товара</label>
                    <input type="file" name="product_image" accept="image/*">
                    <small style="color: var(--purple-lavender);">Рекомендуемый размер: 400x400px</small>
                </div>
                
                <div class="form-group">
                    <label>Категория</label>
                    <select name="category_id" required>
                        <option value="">-- Выберите категорию --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['category_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_available" checked> Доступен для заказа
                    </label>
                </div>
                
                <button type="submit" name="add" class="btn btn-success">
                    <i class="fas fa-plus"></i> Добавить товар
                </button>
            </form>
        </div>
        
        <h2><i class="fas fa-list"></i> Список товаров</h2>
        
        <?php if (empty($products)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Пока нет товаров. Добавьте первый товар!
            </div>
        <?php else: ?>
        <table>
            <tr>
                <th>Изображение</th>
                <th>Название</th>
                <th>Категория</th>
                <th>Цена</th>
                <th>На складе</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($products as $product): ?>
            <tr>
                <td>
                    <?php if ($product['image_url']): ?>
                        <img src="<?= $product['image_url'] ?>" alt="<?= htmlspecialchars($product['product_name']) ?>" 
                             style="width: 60px; height: 60px; object-fit: cover; border-radius: 10px; border: 2px solid var(--pink-sakura);">
                    <?php else: ?>
                        <div style="width: 60px; height: 60px; background: var(--pink-light); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-image" style="color: var(--purple-lavender); font-size: 1.5rem;"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td>
                    <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                    <?php if ($product['description']): ?>
                        <br><small style="color: var(--purple-lavender);"><?= substr(htmlspecialchars($product['description']), 0, 50) ?>...</small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($product['category_name']): ?>
                        <span class="status status-processing"><?= htmlspecialchars($product['category_name']) ?></span>
                    <?php else: ?>
                        <span class="status status-pending">Без категории</span>
                    <?php endif; ?>
                </td>
                <td class="price"><?= number_format($product['price'], 2) ?> руб</td>
                <td>
                    <span class="<?= $product['stock_quantity'] > 0 ? 'status status-delivered' : 'status status-pending' ?>">
                        <?= $product['stock_quantity'] ?> шт.
                    </span>
                </td>
                <td>
                    <?php if ($product['is_available']): ?>
                        <span class="status status-delivered">Доступен</span>
                    <?php else: ?>
                        <span class="status status-pending">Недоступен</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <a href="?delete=<?= $product['product_id'] ?>" 
                           class="btn btn-delete btn-small"
                           onclick="return confirm('🌸 Удалить товар <?= htmlspecialchars($product['product_name']) ?>?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
    
    <script>
    // Предпросмотр изображения перед загрузкой
    document.querySelector('input[name="product_image"]')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.createElement('img');
                preview.src = e.target.result;
                preview.style.maxWidth = '200px';
                preview.style.marginTop = '10px';
                preview.style.borderRadius = '10px';
                preview.style.border = '3px solid var(--pink-sakura)';
                
                const existingPreview = e.target.parentNode.querySelector('.image-preview');
                if (existingPreview) {
                    existingPreview.remove();
                }
                
                preview.className = 'image-preview';
                e.target.parentNode.appendChild(preview);
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html>