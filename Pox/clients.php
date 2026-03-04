<?php
require 'config.php';

// Добавление клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $stmt = $pdo->prepare("INSERT INTO clients (full_name, email, phone, address, is_active) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['address'],
        isset($_POST['is_active']) ? 1 : 0
    ]);
    header("Location: clients.php?success=1");
    exit;
}

// Обновление клиента
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $stmt = $pdo->prepare("UPDATE clients SET full_name = ?, email = ?, phone = ?, address = ?, is_active = ? WHERE client_id = ?");
    $stmt->execute([
        $_POST['full_name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['address'],
        isset($_POST['is_active']) ? 1 : 0,
        $_POST['client_id']
    ]);
    header("Location: clients.php?success=2");
    exit;
}

// Удаление клиента
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM clients WHERE client_id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: clients.php?success=3");
    exit;
}

// Получение всех клиентов
$clients = $pdo->query("SELECT * FROM clients ORDER BY client_id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Клиенты - Кавай Магазин</title>
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
        <a href="services.php"><i class="fas fa-concierge-bell"></i> Услуги</a>
        <a href="orders.php"><i class="fas fa-shopping-cart"></i> Заказы</a>
        <a href="logout.php" class="logout-btn" style="background: linear-gradient(145deg, #ff4d6d, #c9184a); color: white; margin-left: auto;">
            <i class="fas fa-sign-out-alt"></i> Выход
        </a>
    </nav>
</div>
    
    <div class="container">
        <h1><i class="fas fa-users"></i> Управление клиентами</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php 
                if ($_GET['success'] == 1) echo "🌸 Клиент успешно добавлен!";
                elseif ($_GET['success'] == 2) echo "✨ Клиент успешно обновлен!";
                elseif ($_GET['success'] == 3) echo "🗑️ Клиент успешно удален!";
                ?>
            </div>
        <?php endif; ?>
        
        <div class="card-grid">
            <div class="card" style="grid-column: span 2;">
                <div class="card-title"><i class="fas fa-user-plus"></i> Добавить нового клиента</div>
                <form method="POST">
                    <div class="form-group">
                        <label>Полное имя</label>
                        <input type="text" name="full_name" placeholder="Иванов Иван Иванович" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" placeholder="client@example.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Телефон</label>
                        <input type="text" name="phone" placeholder="+7 (999) 123-45-67" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Адрес</label>
                        <textarea name="address" placeholder="Полный адрес доставки" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" checked> Активный клиент
                        </label>
                    </div>
                    
                    <button type="submit" name="add" class="btn btn-success">
                        <i class="fas fa-plus-circle"></i> Добавить клиента
                    </button>
                </form>
            </div>
        </div>
        
        <h2><i class="fas fa-list"></i> Список клиентов</h2>
        
        <table>
            <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Email</th>
                <th>Телефон</th>
                <th>Дата регистрации</th>
                <th>Статус</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($clients as $client): ?>
            <tr>
                <td>#<?= $client['client_id'] ?></td>
                <td><strong><?= htmlspecialchars($client['full_name']) ?></strong></td>
                <td><?= htmlspecialchars($client['email']) ?></td>
                <td><?= htmlspecialchars($client['phone']) ?></td>
                <td><?= date('d.m.Y', strtotime($client['registration_date'])) ?></td>
                <td>
                    <?php if ($client['is_active']): ?>
                        <span class="status status-delivered">Активен</span>
                    <?php else: ?>
                        <span class="status status-pending">Неактивен</span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="action-buttons">
                        <button onclick="editClient(<?= $client['client_id'] ?>)" class="btn btn-edit btn-small">
                            <i class="fas fa-edit"></i>
                        </button>
                        <a href="?delete=<?= $client['client_id'] ?>" 
                           class="btn btn-delete btn-small"
                           onclick="return confirm('🌸 Удалить клиента <?= htmlspecialchars($client['full_name']) ?>?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <!-- Модальное окно редактирования -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div class="container" style="max-width: 500px; margin: 20px; animation: bounce 0.5s;">
            <h2><i class="fas fa-edit"></i> Редактировать клиента</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="client_id" id="edit_client_id">
                
                <div class="form-group">
                    <label>Полное имя</label>
                    <input type="text" name="full_name" id="edit_full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" name="phone" id="edit_phone" required>
                </div>
                
                <div class="form-group">
                    <label>Адрес</label>
                    <textarea name="address" id="edit_address" required></textarea>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="is_active" id="edit_is_active"> Активный клиент
                    </label>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" name="update" class="btn btn-success">
                        <i class="fas fa-save"></i> Сохранить
                    </button>
                    <button type="button" onclick="closeModal()" class="btn btn-delete">
                        <i class="fas fa-times"></i> Отмена
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function editClient(id) {
        fetch('api.php?action=get_client&id=' + id)
            .then(response => response.json())
            .then(client => {
                document.getElementById('edit_client_id').value = client.client_id;
                document.getElementById('edit_full_name').value = client.full_name;
                document.getElementById('edit_email').value = client.email;
                document.getElementById('edit_phone').value = client.phone;
                document.getElementById('edit_address').value = client.address;
                document.getElementById('edit_is_active').checked = client.is_active == 1;
                
                document.getElementById('editModal').style.display = 'flex';
            });
    }
    
    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    
    // Закрытие по клику вне окна
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });
    </script>
</body>
</html>