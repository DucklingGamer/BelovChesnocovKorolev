<?php
require 'user_config.php';

if (isset($_SESSION['user_authenticated'])) {
    header('Location: user_index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    if (empty($username) || empty($password) || empty($email) || empty($phone)) {
        $error = 'Заполните все поля';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        // Проверка уникальности
        $stmt = $local_pdo->prepare("SELECT id FROM site_users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $local_pdo->prepare("INSERT INTO site_users (username, password_hash, email, phone) VALUES (?, ?, ?, ?)");
            
            if ($stmt->execute([$username, $hash, $email, $phone])) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
            } else {
                $error = 'Ошибка регистрации';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Регистрация</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
</head>
<body>
    <div class="login-container" style="max-width: 500px, margin: 50px auto;">
        <div class="anime-header">
            <div class="anime-logo">🌸 Регистрация</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <div style="text-align: center; margin-top: 20px;">
                <a href="user_login.php" class="btn">Войти</a>
            </div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <div class="container">
            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="text" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+7 (999) 123-45-67" required>
                </div>
                
                <div class="form-group">
                    <label>Пароль (мин. 6 символов)</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Подтверждение пароля</label>
                    <input type="password" name="confirm_password" required>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Зарегистрироваться
                </button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="user_login.php">Уже есть аккаунт? Войти</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
