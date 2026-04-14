<?php
session_start();

// Если пользователь уже авторизован, перенаправляем на главную
if (isset($_SESSION['authenticated'])) {
    header('Location: index.php');
    exit;
}

// Подключение к локальной БД для проверки пароля
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
    
    // Проверяем существование таблицы users
    $table_exists = false;
    try {
        $result = $local_pdo->query("SELECT 1 FROM users LIMIT 1");
        $table_exists = true;
    } catch (Exception $e) {
        $table_exists = false;
    }
    
} catch (PDOException $e) {
    die("<div class='alert alert-error'>Ошибка подключения к локальной БД: " . $e->getMessage() . "</div>");
}

$error = '';
$success = '';

// Проверяем, был ли выход
if (isset($_GET['logout'])) {
    $success = 'Вы успешно вышли из системы';
}

// Если форма отправлена
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Проверка на пустые поля
    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } elseif (!$table_exists) {
        $error = 'Таблица пользователей не существует. Пожалуйста, сначала создайте таблицу.';
    } else {
        try {
            // Ищем пользователя в локальной БД
            $stmt = $local_pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            // ... после подключения к local_pdo

// Проверяем существование таблицы users
$table_exists = false;
try {
    $result = $local_pdo->query("SELECT 1 FROM users LIMIT 1");
    $table_exists = true;
} catch (Exception $e) {
    $table_exists = false;
}

// ... остальной код
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // Авторизация успешна
                $_SESSION['authenticated'] = true;
                $_SESSION['username'] = $username;
                $_SESSION['user_id'] = $user['id'];
                
                // Перенаправляем на главную
                header('Location: index.php');
                exit;
            } else {
                $error = 'Неверное имя пользователя или пароль';
            }
            
        } catch (Exception $e) {
            $error = 'Ошибка авторизации: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Авторизация - Кавай Магазин 🍥</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <style>
        .login-container {
            max-width: 500px;
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
            gap: 10px;
        }
        
        .login-footer a {
            color: var(--red-heart);
            text-decoration: none;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 20px;
            background: var(--pink-light);
            transition: all 0.3s;
        }
        
        .login-footer a:hover {
            background: var(--pink-sakura);
            color: white;
            transform: translateY(-2px);
        }
        
        .setup-alert {
            background: linear-gradient(145deg, var(--yellow-happy), #ffb703);
            color: #5a4a6a;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            border: 3px solid #ffb703;
            text-align: center;
        }
        
        .setup-alert a {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background: var(--red-heart);
            color: white;
            border-radius: 20px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .setup-alert a:hover {
            background: #c9184a;
            transform: scale(1.05);
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .shake {
            animation: shake 0.5s;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="anime-header">
            <div class="anime-logo">🌸 KAWAI</div>
            <div class="anime-subtitle">Вход для покупателей</div>
        </div>
        
        <?php if (!$table_exists): ?>
            <div class="setup-alert">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Система не настроена</h3>
                <p>Таблица пользователей не существует. Необходимо создать её перед использованием системы.</p>
                <a href="create_users_table.php">
                    <i class="fas fa-database"></i> Настроить базу данных
                </a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error shake">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($table_exists): ?>
        <div class="login-form">
            <div class="form-icon">
                <i class="fas fa-user-lock"></i>
            </div>
            
            <form method="POST" id="loginForm">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Логин администратора</label>
                    <input type="text" name="username" placeholder="Введите ваш логин" required
                           class="<?= $error ? 'shake' : '' ?>" autofocus>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Пароль</label>
                    <input type="password" name="password" placeholder="Введите ваш пароль" required
                           class="<?= $error ? 'shake' : '' ?>">
                </div>
                
                <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 20px;">
                    <i class="fas fa-sign-in-alt"></i> Войти в систему
                </button>
            </form>
            <div class="login-footer">
            <div>
                <p><i class="fas fa-info-circle"></i> Для доступа к системе необходима авторизация</p>
            </div>
            <?php if ($table_exists): ?>
                <div>
                    <a href="create_users_table.php">
                        <i class="fas fa-user-plus: margin-top: 12px; "></i> Зарегистрировать администратора
                    </a>
                </div>
                <?php endif; ?>
                    <div style="text-align: center; margin-top: 12px;">
                        <a href="user_login.php" style="color: var(--red-heart);">
                            <i class="fas fa-sign-in-alt"></i> Вход для пользователя
                        </a>
                    </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Анимация фона -->
        <div style="position: fixed; top: 0; left: 0; right: 0; bottom: 0; z-index: -1; pointer-events: none;">
            <div style="position: absolute; top: 20%; left: 10%; font-size: 3rem; color: var(--pink-sakura); opacity: 0.1; animation: float 6s ease-in-out infinite;">🌸</div>
            <div style="position: absolute; top: 40%; right: 15%; font-size: 2rem; color: var(--purple-lavender); opacity: 0.1; animation: float 4s ease-in-out infinite reverse;">🍥</div>
            <div style="position: absolute; bottom: 30%; left: 20%; font-size: 4rem; color: var(--blue-sky); opacity: 0.1; animation: rotate 20s linear infinite;">✨</div>
        </div>
    </div>
    
    <script>
    // Добавляем анимацию при ошибке
    document.addEventListener('DOMContentLoaded', function() {
        const inputs = document.querySelectorAll('input[name="username"], input[name="password"]');
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
        
        // Функция применения темы (упрощенная версия)
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
