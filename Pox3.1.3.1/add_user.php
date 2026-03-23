<?php
require 'local_config.php';

// Проверка на добавление пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $remote_db_user = $_POST['remote_db_user'];
    
    // Хешируем пароль
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $local_pdo->prepare("INSERT INTO users (username, password_hash, remote_db_user) VALUES (?, ?, ?)");
        $stmt->execute([$username, $password_hash, $remote_db_user]);
        
        $success = "Пользователь $username успешно добавлен!";
    } catch (Exception $e) {
        $error = "Ошибка добавления пользователя: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Добавление пользователя</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; margin: 50px auto;">
        <h1><i class="fas fa-user-plus"></i> Добавление пользователя</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $success ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $error ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="card" style="padding: 30px;">
            <div class="form-group">
                <label>Имя пользователя (для входа)</label>
                <input type="text" name="username" required>
            </div>
            
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required>
            </div>
            
            <div class="form-group">
                <label>Имя пользователя в удаленной БД</label>
                <input type="text" name="remote_db_user" required 
                       placeholder="Например: Chesnokov">
                <small style="color: var(--purple-lavender);">
                    Это имя пользователя для подключения к удаленной БД
                </small>
            </div>
            
            <button type="submit" name="add_user" class="btn btn-success">
                <i class="fas fa-plus"></i> Добавить пользователя
            </button>
            
            <a href="login.php" class="btn" style="margin-left: 10px;">
                <i class="fas fa-sign-in-alt"></i> К авторизации
            </a>
        </form>
    </div>
</body>
</html>