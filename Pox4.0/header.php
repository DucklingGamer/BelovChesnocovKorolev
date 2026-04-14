<?php
// header.php - единый хедер для всех страниц (ФИНАЛЬНАЯ ВЕРСИЯ)
$is_admin = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$is_user = isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
$current_page = basename($_SERVER['PHP_SELF']);



// Получаем данные для таймера (если есть)
$timerData = [];
if (function_exists('getRemainingGameTime')) {
    $timer = getRemainingGameTime();
    $timerData = [
        'game_state' => $timer['type'] ?? 'work',
        'work_time' => $_SESSION['work_time_seconds'] ?? 2700,
        'break_time' => $_SESSION['break_time_seconds'] ?? 300,
        'state_start_time' => ($_SESSION['game_state_start'] ?? time()) * 1000
    ];
}

// Получаем данные уведомления
$notificationData = [];
if (isset($_SESSION['game_state_result']) && $_SESSION['game_state_result']['need_message']) {
    $notificationData = $_SESSION['game_state_result'];
    unset($_SESSION['game_state_result']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? ($is_admin ? '🌸 Кавай Магазин - Админка' : '🌸 Кавай Магазин') ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Black+Ops+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <script src="main.js" defer></script>
</head>
<body data-game-state="<?= $timerData['game_state'] ?? 'work' ?>"
      data-work-time="<?= $timerData['work_time'] ?? 2700 ?>"
      data-break-time="<?= $timerData['break_time'] ?? 300 ?>"
      data-state-start-time="<?= $timerData['state_start_time'] ?? time() * 1000 ?>"
      data-game-notification='<?= json_encode($notificationData) ?>'>
    
    <!-- ВЕРХНЯЯ ПАНЕЛЬ (полная ширина) -->
    <div class="top-bar-full">
        <div class="top-bar-inner">
            <!-- ЛОГОТИП (слева от хедера) -->
            <div class="site-logo-wrapper">
                <a href="<?= $is_admin ? 'index.php' : 'user_index.php' ?>" class="site-logo-link">
                    <div class="site-logo-square">
                        <span class="logo-text">KAWAI</span>
                    </div>
                </a>
            </div>

            <!-- МЕНЮ НАВИГАЦИИ (ХЕДЕР) - растянут -->
            <div class="menu">
                <nav class="menu-nav">
                    <?php if ($is_admin): ?>
                        <a href="index.php" class="<?= $current_page == 'index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Главная</a>
                        <a href="clients.php" class="<?= $current_page == 'clients.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> Клиенты</a>
                        <a href="categories.php" class="<?= $current_page == 'categories.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Категории</a>
                        <a href="products.php" class="<?= $current_page == 'products.php' ? 'active' : '' ?>"><i class="fas fa-box-open"></i> Товары</a>
                        <a href="services.php" class="<?= $current_page == 'services.php' ? 'active' : '' ?>"><i class="fas fa-concierge-bell"></i> Услуги</a>
                        <a href="orders.php" class="<?= $current_page == 'orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Заказы</a>
                        <a href="admin_profile.php" class="<?= $current_page == 'admin_profile.php' ? 'active' : '' ?>"><i class="fas fa-user-cog"></i> Профиль</a>
                        <a href="#" onclick="openColorGame(false); return false;" class="game-btn"><i class="fas fa-palette"></i> Игра</a>
                        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выход</a>
                    <?php else: ?>
                        <a href="user_index.php" class="<?= $current_page == 'user_index.php' ? 'active' : '' ?>"><i class="fas fa-home"></i> Главная</a>
                        <a href="user_categories.php" class="<?= $current_page == 'user_categories.php' ? 'active' : '' ?>"><i class="fas fa-tags"></i> Категории</a>
                        <a href="user_services.php" class="<?= $current_page == 'user_services.php' ? 'active' : '' ?>"><i class="fas fa-concierge-bell"></i> Услуги</a>
                        <a href="user_cart.php" class="<?= $current_page == 'user_cart.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Корзина</a>
                        <a href="user_orders.php" class="<?= $current_page == 'user_orders.php' ? 'active' : '' ?>"><i class="fas fa-shopping-cart"></i> Заказы</a>
                        <a href="user_profile.php" class="<?= $current_page == 'user_profile.php' ? 'active' : '' ?>"><i class="fas fa-user"></i> Профиль</a>
                        <a href="#" onclick="openColorGame(false); return false;" class="game-btn"><i class="fas fa-palette"></i> Игра</a>
                        <a href="user_logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выход</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </div>

    <style>
    /* ===== ФИНАЛЬНЫЕ СТИЛИ ===== */
    
    /* Полная ширина для верхней панели */
    .top-bar-full {
        width: 100%;
        background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
        box-shadow: 0 5px 20px var(--shadow-pink);
        position: relative;
        z-index: 100;
    }
    
    /* Внутренний контейнер панели - центрирование содержимого */
    .top-bar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        max-width: 1400px;
        margin: 0 auto;
        padding: 12px 30px;
        width: 100%;
        box-sizing: border-box;
    }
    
    /* Логотип - фиксированная ширина */
    .site-logo-link {
        text-decoration: none;
        flex-shrink: 0;
    }
    .site-logo-square {
        width: 70px;
        height: 70px;
        background: linear-gradient(145deg, #ff4d6d, #ffafcc);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 20px rgba(255, 77, 109, 0.3);
        transition: all 0.3s ease;
        border: 2px solid white;
    }
    .site-logo-square:hover {
        transform: scale(1.05);
    }
    .logo-text {
        font-family: 'Black Ops One', 'Galiath', 'Arial Black', sans-serif;
font-weight: 400;
font-size: 2.2rem;
color: white !important;
text-shadow: 3px 3px 0 rgba(0, 0, 0, 0.2), 0 0 20px rgba(255, 255, 255, 0.3);
letter-spacing: 4px;
text-transform: uppercase;
-webkit-text-stroke: 1px rgba(0, 0, 0, 0.1);
transition: all 0.3s ease;
line-height: 1;
margin-right: 75px;
    }
    
    /* Меню - растягивается на всю доступную ширину */
    .menu {
        flex: 1;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 60px;
        padding: 5px 15px;
        backdrop-filter: blur(5px);
    }
    .menu-nav {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 5px;
    }
    .menu-nav a {
        padding: 10px 18px;
        font-size: 0.9rem;
        border-radius: 40px;
        background: white;
        color: var(--red-heart);
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        white-space: nowrap;
        font-weight: 600;
        flex: 1;
        justify-content: center;
        text-align: center;
    }
    .menu-nav a i {
        font-size: 0.95rem;
    }
    .menu-nav a:hover {
        transform: translateY(-2px);
        background: var(--pink-light);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .menu-nav a.active {
        background: var(--pink-sakura);
        color: white;
        box-shadow: 0 0 10px rgba(255,255,255,0.5);
    }
    .menu-nav a.game-btn {
        background: linear-gradient(145deg, var(--green-matcha), var(--blue-sky));
        color: white;
    }
    .menu-nav a.logout-btn {
        background: linear-gradient(145deg, #ff4d6d, #c9184a);
        color: white;
    }
    
    .timer-icon {
        font-size: 1.3rem;
        color: var(--purple-magical);
    }
    .timer-content {
        text-align: center;
    }
    .timer-label {
        font-size: 0.65rem;
        color: var(--purple-lavender);
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .timer-value {
        font-size: 1.2rem;
        font-weight: bold;
        color: var(--red-heart);
        font-family: monospace;
        line-height: 1.2;
    }
    
    /* КОНТЕЙНЕР С КОНТЕНТОМ - по центру, фиксированная ширина */
    .container {
        max-width: 1200px !important;
        width: 100% !important;
        margin: 40px auto !important;
        padding: 35px !important;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 30px;
        border: 3px solid var(--pink-neko);
        box-shadow: 0 15px 35px var(--shadow-pink);
        box-sizing: border-box;
    }
    
    /* Уведомления */
    .game-state-notification {
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        border-radius: 20px;
        border: 3px solid var(--pink-sakura);
        z-index: 10002;
        padding: 15px;
        animation: bounceInDown 0.5s ease-out;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .game-state-notification .reminder-content {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .game-state-notification .reminder-icon {
        font-size: 2rem;
    }
    .game-state-notification .reminder-text {
        font-size: 1rem;
        color: var(--purple-magical);
    }
    @keyframes bounceInDown {
        0% { transform: translateX(-50%) translateY(-100px); opacity: 0; }
        60% { transform: translateX(-50%) translateY(10px); opacity: 1; }
        100% { transform: translateX(-50%) translateY(0); }
    }
    @keyframes pulse {
        0%, 100% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.1); opacity: 0.8; }
    }
    
    /* Адаптивность */
    @media (max-width: 1200px) {
        .top-bar-inner {
            padding: 10px 20px;
        }
        .menu-nav a {
            padding: 8px 12px;
            font-size: 0.8rem;
        }
        .container {
            margin: 30px 20px !important;
            max-width: calc(100% - 40px) !important;
        }
    }
    
    @media (max-width: 900px) {
        .top-bar-inner {
            flex-wrap: wrap;
            justify-content: center;
            gap: 12px;
            padding: 12px 15px;
        }
        .menu {
            flex: auto;
            width: 100%;
            order: 3;
        }
        .site-logo-wrapper {
            order: 1;
        }
        .menu-nav {
            justify-content: center;
        }
        .menu-nav a {
            flex: auto;
            padding: 6px 12px;
            font-size: 0.75rem;
        }
        .container {
            margin: 20px 15px !important;
            padding: 20px !important;
        }
    }
    
    @media (max-width: 600px) {
        .site-logo-square {
            width: 55px;
            height: 55px;
        }
        .logo-text {
            font-size: 0.9rem;
        }
        .timer-value {
            font-size: 1rem;
        }
        .timer-icon {
            font-size: 1rem;
        }
    }

    </style>
</body>
</html>