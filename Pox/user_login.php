<?php
require 'user_config.php';

if (isset($_SESSION['user_authenticated'])) {
    header('Location: user_index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Заполните все поля';
    } else {
        $stmt = $local_pdo->prepare("SELECT * FROM site_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_authenticated'] = true;
            $_SESSION['username'] = $username;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['phone'] = $user['phone'];
            
            header('Location: user_index.php');
            exit;
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Вход для пользователей</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
</head>
<body>
    <div class="login-container" style="max-width: 400px; margin: 50px auto;">
        <div class="anime-header">
            <div class="anime-logo">🌸 Магазин</div>
            <div class="anime-subtitle">Вход для покупателей</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="container">
            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Войти
                </button>
            </form>
            
            <div class="login-footer" style="margin-top: 25px;">
                <div>
                    <a href="user_register.php"><i class="fas fa-user-plus"></i> Нет аккаунта? Зарегистрироваться</a>
                </div>
                <div>
                    <a href="login.php"><i class="fas fa-user-shield"></i> Вход для администратора</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
