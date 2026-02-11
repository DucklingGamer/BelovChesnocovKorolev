[file name]: login.php
[file content begin]
<?php
require 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $local_pdo->prepare("SELECT id, password_hash FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && verifyPassword($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        
        // Перенаправление на главную или на страницу, с которой пришли
        $redirect = $_GET['redirect'] ?? 'index.php';
        header("Location: $redirect");
        exit;
    } else {
        $error = "🌸 Неверное имя пользователя или пароль!";
    }
}

// Если уже авторизован, перенаправляем на главную
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Вход - Кавай Магазин</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            text-align: center;
        }
        
        .login-title {
            font-family: 'M PLUS Rounded 1c', sans-serif;
            color: var(--red-heart);
            margin-bottom: 30px;
            font-size: 2.5rem;
            text-shadow: 2px 2px 0 var(--shadow-pink);
        }
        
        .login-logo {
            font-size: 4rem;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="login-logo">🌸🍥✨</div>
        <h1 class="login-title">カワイイ ショップ</h1>
        <p class="anime-subtitle" style="margin-bottom: 30px;">АНИМЕ МАГАЗИН ✨ ВХОД НЯ~</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Имя пользователя</label>
                <input type="text" name="username" placeholder="Введите имя пользователя" required autofocus>
            </div>
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> Пароль</label>
                <input type="password" name="password" placeholder="Введите пароль" required>
            </div>
            
            <button type="submit" class="btn" style="width: 100%; padding: 15px; margin-top: 20px;">
                <i class="fas fa-sign-in-alt"></i> Войти
            </button>
        </form>
        
        <div style="margin-top: 30px; padding: 20px; background: var(--pink-light); border-radius: 15px;">
            <p><i class="fas fa-info-circle"></i> Для входа используйте данные из локальной БД</p>
            <p><small>База данных: Bd_belov → таблица users</small></p>
        </div>
    </div>
</body>
</html>
[file content end] 
