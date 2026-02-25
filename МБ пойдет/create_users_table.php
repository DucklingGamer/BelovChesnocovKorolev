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
                
                // Очищаем форму
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
    <title>🌸 Регистрация - Кавай Магазин</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .register-logo {
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 2.5rem;
            color: var(--red-heart);
            margin-bottom: 10px;
            text-shadow: 2px 2px 0 var(--shadow-pink);
        }
        
        .register-subtitle {
            color: var(--purple-magical);
            font-size: 1.2rem;
            letter-spacing: 2px;
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
        }
        
        .register-footer a {
            color: var(--red-heart);
            text-decoration: none;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 20px;
            background: var(--pink-light);
            transition: all 0.3s;
            display: inline-block;
            margin-top: 10px;
        }
        
        .register-footer a:hover {
            background: var(--pink-sakura);
            color: white;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">カワイイ ショップ</div>
            <div class="register-subtitle">Регистрация нового пользователя</div>
        </div>
        
        <div class="register-form">
            <div class="form-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            
            <?php if ($message): ?>
                <div class="alert <?= $success ? 'alert-success' : 'alert-error' ?>">
                    <i class="fas <?= $success ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> 
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" id="registerForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Имя пользователя *</label>
                    <input type="text" name="username" 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
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
                
                <div class="form-group" style="background: var(--pink-light); padding: 15px; border-radius: 10px; margin: 20px 0;">
                    <h4 style="margin: 0 0 10px 0; color: var(--purple-magical);">
                        <i class="fas fa-info-circle"></i> Информация:
                    </h4>
                    <p style="margin: 0; font-size: 0.9rem;">
                        По умолчанию будут использоваться стандартные данные для подключения к основной БД.
                        Вы можете изменить их позже в настройках.
                    </p>
                </div>
                
                <button type="submit" name="register" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Зарегистрироваться
                </button>
            </form>
            
            <div class="register-footer">
                <p>Уже есть аккаунт?</p>
                <a href="login.php">
                    <i class="fas fa-sign-in-alt"></i> Войти в систему
                </a>
            </div>
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
                    '--shadow-pink': 'rgba(212, 93, 121, 0.2)'
                },
                'pastel': {
                    '--pink-sakura': '#ffb7c5',
                    '--pink-light': '#fff0f5',
                    '--red-heart': '#ffb7c5',
                    '--purple-magical': '#d8bfd8',
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