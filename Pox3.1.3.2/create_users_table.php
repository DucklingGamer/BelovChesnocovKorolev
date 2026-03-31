<?php
session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}

// Подключение к локальной БД
$local_host = 'localhost';
$local_db   = 'Bd_belov';
$local_user = 'admin';
$local_pass = 'admin';
$local_charset = 'utf8mb4';

$local_dsn = "mysql:host=$local_host;dbname=$local_db;charset=$local_charset";
$local_options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $local_pdo = new PDO($local_dsn, $local_user, $local_pass, $local_options);
    
    $message = '';
    $success = false;
    
    // Создаем таблицу users если её нет
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        remote_db_user VARCHAR(50) NOT NULL DEFAULT 'Chesnokov',
        remote_db_password VARCHAR(255) NOT NULL DEFAULT 'CAaSRQUQ/5qvp29f',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $local_pdo->exec($sql);
    
    // Если форма отправлена, добавляем пользователя
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $password_confirm = $_POST['password_confirm'];
        
        if (empty($username) || empty($password)) {
            $message = "❌ Пожалуйста, заполните все обязательные поля";
            $success = false;
        } elseif ($password !== $password_confirm) {
            $message = "❌ Пароли не совпадают";
            $success = false;
        } elseif (strlen($password) < 6) {
            $message = "❌ Пароль должен содержать не менее 6 символов";
            $success = false;
        } else {
            // Хешируем пароль для входа
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            try {
                $stmt = $local_pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$username, $password_hash]);
                
                $message = "✅ Пользователь '$username' успешно зарегистрирован! Теперь вы можете войти в систему.";
                $success = true;
                
                // Очищаем POST данные после успешной регистрации
                $_POST = array();
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $message = "❌ Пользователь с таким именем уже существует";
                } else {
                    $message = "❌ Ошибка регистрации: " . $e->getMessage();
                }
                $success = false;
            }
        }
    }
    
} catch (PDOException $e) {
    $message = "❌ Ошибка подключения к базе данных: " . $e->getMessage();
    $success = false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Регистрация - Кавай Магазин 🍥</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            animation: bounce 1s;
        }
        
        .anime-header {
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }
        
        .anime-logo {
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 3.5rem;
            color: var(--red-heart);
            margin-bottom: 15px;
            text-shadow: 
                3px 3px 0 var(--shadow-pink),
                6px 6px 0 rgba(205, 180, 219, 0.2);
            position: relative;
            display: inline-block;
        }
        
        .anime-logo::before {
            content: '🍥';
            position: absolute;
            left: -50px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            animation: rotate 15s linear infinite;
        }
        
        .anime-logo::after {
            content: '🌸';
            position: absolute;
            right: -50px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            animation: float 4s ease-in-out infinite;
        }
        
        .anime-subtitle {
            color: var(--purple-magical);
            font-size: 1.2rem;
            letter-spacing: 3px;
            font-weight: 600;
        }
        
        .register-form {
            background: white;
            padding: 30px;
            border-radius: 25px;
            border: 3px solid var(--pink-neko);
            box-shadow: 0 15px 35px var(--shadow-pink);
            position: relative;
            overflow: hidden;
        }
        
        .register-form::before {
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
        
        .password-strength {
            margin-top: 5px;
            height: 5px;
            background: #eee;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
        
        .register-footer {
            text-align: center;
            margin-top: 25px;
            color: var(--purple-lavender);
            font-size: 0.9rem;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .register-footer a {
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
        
        .register-footer a:hover {
            background: var(--pink-sakura);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px var(--shadow-pink);
        }
        
        .register-footer a i {
            margin-right: 8px;
        }
        
        .register-footer .login-link {
            background: var(--purple-lavender);
            color: white;
        }
        
        .register-footer .login-link:hover {
            background: var(--purple-magical);
        }
        
        .register-form .form-group {
            margin-bottom: 25px;
        }
        
        .register-form .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--purple-magical);
            font-weight: 600;
        }
        
        .register-form .form-group label i {
            color: var(--pink-sakura);
            margin-right: 5px;
        }
        
        .register-form .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--pink-light);
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .register-form .form-group input:focus {
            outline: none;
            border-color: var(--pink-sakura);
            box-shadow: 0 0 0 3px var(--shadow-pink);
        }
        
        .info-box {
            background: var(--pink-light);
            padding: 20px;
            border-radius: 15px;
            margin: 25px 0;
            border-left: 5px solid var(--purple-lavender);
        }
        
        .info-box h4 {
            margin: 0 0 10px 0;
            color: var(--purple-magical);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-box p {
            margin: 0;
            font-size: 0.95rem;
            color: #5a4a6a;
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
            width: 100%;
            margin-top: 20px;
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
        
        .alert-success {
            background: #e6ffe6;
            border-color: #99ff99;
            color: #006600;
        }
        
        .alert i {
            font-size: 1.2rem;
        }

        /* Стили для анимаций фона */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            pointer-events: none;
        }

        .floating-elements div {
            position: absolute;
            font-size: 3rem;
            opacity: 0.1;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="anime-header">
            <div class="anime-logo">Регистрация</div>
            <div class="anime-subtitle">Регистрация нового пользователя</div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?> shake">
                <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> 
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="register-form">
            <div class="form-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Имя пользователя *</label>
                    <input type="text" name="username" 
                           placeholder="Придумайте логин" required
                           minlength="3" maxlength="50">
                    <small style="color: var(--purple-lavender);">От 3 до 50 символов</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Пароль *</label>
                    <input type="password" name="password" id="password" 
                           placeholder="Придумайте пароль" required
                           minlength="6">
                    <div class="password-strength">
                        <div class="strength-bar" id="strengthBar"></div>
                    </div>
                    <small style="color: var(--purple-lavender);">Не менее 6 символов</small>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Подтверждение пароля *</label>
                    <input type="password" name="password_confirm" 
                           placeholder="Повторите пароль" required>
                </div>
                
                <div class="info-box">
                    <h4><i class="fas fa-info-circle"></i> Информация:</h4>
                    <p>По умолчанию будут использоваться стандартные данные для подключения к основной БД. Вы можете изменить их позже в настройках.</p>
                </div>
                
                <button type="submit" name="register" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Зарегистрироваться
                </button>
            </form>
            
            <div class="register-footer">
                <div>
                    <p style="margin-bottom: 8px;">Уже есть аккаунт?</p>
                    <a href="login.php" class="login-link">
                        <i class="fas fa-sign-in-alt"></i> Войти в систему
                    </a>
                </div>
            </div>
        </div>

        <!-- Анимация фона -->
        <div class="floating-elements">
            <div style="top: 20%; left: 10%; animation: float 6s ease-in-out infinite;">🌸</div>
            <div style="top: 60%; right: 15%; animation: float 4s ease-in-out infinite reverse;">🍥</div>
            <div style="bottom: 30%; left: 20%; font-size: 4rem; animation: rotate 20s linear infinite;">✨</div>
            <div style="top: 40%; left: 80%; animation: float 5s ease-in-out infinite;">🎀</div>
            <div style="top: 70%; left: 5%; animation: float 7s ease-in-out infinite;">⭐</div>
            <div style="top: 30%; right: 5%; animation: rotate 15s linear infinite;">🌀</div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Проверка длины
            if (password.length >= 6) strength += 20;
            if (password.length >= 8) strength += 20;
            
            // Проверка наличия символов разных типов
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;
            
            // Ограничиваем до 100%
            strength = Math.min(strength, 100);
            
            // Устанавливаем ширину и цвет
            strengthBar.style.width = strength + '%';
            
            if (strength < 40) {
                strengthBar.style.background = '#ff4d6d'; // Красный
            } else if (strength < 70) {
                strengthBar.style.background = '#ffb703'; // Желтый
            } else {
                strengthBar.style.background = '#48bb78'; // Зеленый
            }
        });
        
        // Убираем класс shake при фокусе на инпутах
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.classList.remove('shake');
            });
        });
    });
    </script>
</body>
</html>