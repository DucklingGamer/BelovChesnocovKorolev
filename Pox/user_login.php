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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Вход для покупателей - Кавай Магазин 🍥</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <style>
        .login-container {
            max-width: 450px;
            margin: 50px auto;
            animation: bounce 1s;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-logo {
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 2.5rem;
            color: var(--red-heart);
            margin-bottom: 10px;
            text-shadow: 2px 2px 0 var(--shadow-pink);
        }
        
        .login-subtitle {
            color: var(--purple-magical);
            font-size: 1.2rem;
            letter-spacing: 2px;
        }
        
        .login-form {
            background: white;
            padding: 30px;
            border-radius: 25px;
            border: 3px solid var(--pink-neko);
            box-shadow: 0 15px 35px var(--shadow-pink);
            position: relative;
            overflow: hidden;
        }
        
        .login-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, 
                var(--pink-sakura) 0%, 
                var(--purple-lavender) 25%, 
                var(--blue-sky) 50%,
                var(--green-matcha) 75%,
                var(--pink-sakura) 100%);
        }
        
        .form-icon {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .form-icon i {
            font-size: 3rem;
            color: var(--pink-sakura);
            background: var(--pink-light);
            padding: 15px;
            border-radius: 50%;
            border: 3px solid var(--pink-sakura);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            color: var(--purple-lavender);
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .login-footer a {
            display: inline-block;
            padding: 10px 20px;
            background: var(--pink-light);
            color: var(--red-heart);
            text-decoration: none;
            font-weight: bold;
            border-radius: 30px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .login-footer a:hover {
            background: var(--pink-sakura);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px var(--shadow-pink);
        }
        
        .login-footer a i {
            margin-right: 8px;
        }
        
        .login-footer .admin-link {
            background: var(--purple-lavender);
            color: white;
        }
        
        .login-footer .admin-link:hover {
            background: var(--purple-magical);
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--purple-magical);
            font-weight: 600;
        }
        
        .login-form .form-group label i {
            color: var(--pink-sakura);
            margin-right: 5px;
        }
        
        .login-form .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--pink-light);
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .login-form .form-group input:focus {
            outline: none;
            border-color: var(--pink-sakura);
            box-shadow: 0 0 0 3px var(--shadow-pink);
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-success {
            background: linear-gradient(145deg, var(--pink-sakura), var(--red-heart));
            color: white;
        }
        
        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px var(--shadow-pink);
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background: #ffe6e6;
            border-color: #ff9999;
            color: #cc0000;
        }
        
        .alert i {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="anime-header">
            <div class="anime-logo">🌸 Магазин</div>
            <div class="anime-subtitle">Вход для покупателей</div>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error shake">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="login-form">
            <div class="form-icon">
                <i class="fas fa-user-astronaut"></i>
            </div>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Логин</label>
                    <input type="text" name="username" placeholder="Введите ваш логин" required autofocus>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" name="password" placeholder="Введите ваш пароль" required>
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-sign-in-alt"></i> Войти
                </button>
            </form>
            
            <div class="login-footer">
                <div>
                    <a href="user_register.php">
                        <i class="fas fa-user-plus"></i> Нет аккаунта? Зарегистрироваться
                    </a>
                </div>
                <div>
                    <a href="login.php" class="admin-link">
                        <i class="fas fa-user-shield"></i> Вход для администратора
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Анимация фона -->
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: -1; pointer-events: none;">
            <div style="position: absolute; top: 20%; left: 10%; font-size: 3rem; color: var(--pink-sakura); opacity: 0.1; animation: float 6s ease-in-out infinite;">🌸</div>
            <div style="position: absolute; top: 60%; right: 15%; font-size: 2rem; color: var(--purple-lavender); opacity: 0.1; animation: float 4s ease-in-out infinite reverse;">🍥</div>
            <div style="position: absolute; bottom: 30%; left: 20%; font-size: 4rem; color: var(--blue-sky); opacity: 0.1; animation: rotate 20s linear infinite;">✨</div>
            <div style="position: absolute; top: 40%; left: 80%; font-size: 2.5rem; color: var(--green-matcha); opacity: 0.1; animation: float 5s ease-in-out infinite;">🎀</div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Убираем класс shake при фокусе на инпутах
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.classList.remove('shake');
            });
        });
        
        // Предзагрузка темы
        const savedTheme = localStorage.getItem('kawaii-theme') || 'default';
        if (savedTheme !== 'default') {
            applyTheme(savedTheme);
        }
        
        function applyTheme(theme) {
            const styles = {
                'dark': {
                    '--pink-sakura': '#d45d79',
                    '--pink-light': '#2a1a1f',
                    '--red-heart': '#d45d79',
                    '--purple-magical': '#6d5c8c',
                    '--purple-lavender': '#957dad',
                    '--blue-sky': '#5c8c9c',
                    '--green-matcha': '#7c9c6d',
                    '--shadow-pink': 'rgba(212, 93, 121, 0.2)'
                },
                'pastel': {
                    '--pink-sakura': '#ffb7c5',
                    '--pink-light': '#fff0f5',
                    '--red-heart': '#ffb7c5',
                    '--purple-magical': '#d8bfd8',
                    '--purple-lavender': '#e6e6fa',
                    '--blue-sky': '#b0e0e6',
                    '--green-matcha': '#c1e1c1',
                    '--shadow-pink': 'rgba(255, 183, 197, 0.3)'
                },
                'default': {}
            };
            
            if (styles[theme]) {
                const root = document.documentElement;
                for (const [property, value] of Object.entries(styles[theme])) {
                    root.style.setProperty(property, value);
                }
            }
        }
    });
    </script>
</body>
</html>