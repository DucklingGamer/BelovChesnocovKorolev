<?php
// header.php - единый хедер для всех страниц
$is_admin = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
$is_user = isset($_SESSION['user_authenticated']) && $_SESSION['user_authenticated'] === true;
$current_page = basename($_SERVER['PHP_SELF']);

// Получаем данные для таймера
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <script src="main.js" defer></script>
</head>
<body data-game-state="<?= $timerData['game_state'] ?? 'work' ?>"
      data-work-time="<?= $timerData['work_time'] ?? 2700 ?>"
      data-break-time="<?= $timerData['break_time'] ?? 300 ?>"
      data-state-start-time="<?= $timerData['state_start_time'] ?? time() * 1000 ?>"
      data-game-notification='<?= json_encode($notificationData) ?>'>
    
    <!-- ВЕРХНЯЯ ПАНЕЛЬ -->
    <div class="top-bar-full">
        <div class="top-bar-inner">
            <!-- ЛОГОТИП -->
            <div class="site-logo-wrapper">
                <a href="<?= $is_admin ? 'index.php' : 'user_index.php' ?>" class="site-logo-link">
                    <div class="site-logo-square">
                        <span class="logo-text">SOSAL?</span>
                    </div>
                </a>
            </div>

            <!-- МЕНЮ (ХЕДЕР) -->
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

            <!-- ТАЙМЕР -->
            <div class="global-timer" id="globalTimer">
                <div class="timer-icon">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="timer-content">
                    <div class="timer-label" id="timerLabel">До отдыха</div>
                    <div class="timer-value" id="timerValue">00:00</div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .top-bar-full {
        width: 100%;
        background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
        box-shadow: 0 5px 20px var(--shadow-pink);
        position: relative;
        z-index: 100;
    }
    .top-bar-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 25px;
        max-width: 1600px;
        margin: 0 auto;
        padding: 12px 40px;
        width: 100%;
        box-sizing: border-box;
    }
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
        font-family: 'M PLUS Rounded 1c', sans-serif;
        font-weight: 800;
        font-size: 1rem;
        color: white;
        text-shadow: 1px 1px 0 rgba(0, 0, 0, 0.2);
        letter-spacing: 0.5px;
    }
    .menu {
        flex: 1;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 50px;
        padding: 6px 20px;
        backdrop-filter: blur(5px);
    }
    .menu-nav {
        display: flex;
        justify-content: space-evenly;
        flex-wrap: wrap;
        gap: 5px;
    }
    .menu-nav a {
        padding: 8px 18px;
        font-size: 0.9rem;
        border-radius: 40px;
        background: white;
        color: var(--red-heart);
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
        font-weight: 600;
    }
    .menu-nav a:hover {
        transform: translateY(-2px);
        background: var(--pink-light);
    }
    .menu-nav a.active {
        background: var(--pink-sakura);
        color: white;
    }
    .menu-nav a.game-btn {
        background: linear-gradient(145deg, var(--green-matcha), var(--blue-sky));
        color: white;
    }
    .menu-nav a.logout-btn {
        background: linear-gradient(145deg, #ff4d6d, #c9184a);
        color: white;
    }
    .global-timer {
        flex-shrink: 0;
        background: white;
        border-radius: 50px;
        border: 2px solid var(--pink-sakura);
        padding: 6px 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .timer-icon {
        font-size: 1.2rem;
        color: var(--purple-magical);
    }
    .timer-label {
        font-size: 0.6rem;
        color: var(--purple-lavender);
        text-transform: uppercase;
    }
    .timer-value {
        font-size: 1.1rem;
        font-weight: bold;
        color: var(--red-heart);
        font-family: monospace;
    }
    .container {
        max-width: 1300px !important;
        width: 100% !important;
        margin: 40px auto !important;
        padding: 35px !important;
        background: rgba(255, 255, 255, 0.95);
        border-radius: 30px;
        border: 3px solid var(--pink-neko);
        box-sizing: border-box;
    }
    @media (max-width: 1100px) {
        .top-bar-inner { padding: 10px 25px; }
        .menu-nav a { padding: 6px 14px; font-size: 0.8rem; }
        .container { margin: 30px 25px !important; max-width: calc(100% - 50px) !important; }
    }
    @media (max-width: 900px) {
        .top-bar-inner { flex-wrap: wrap; justify-content: center; gap: 12px; padding: 12px 20px; }
        .menu { flex: auto; width: 100%; order: 3; }
        .global-timer { order: 2; }
        .site-logo-wrapper { order: 1; }
        .menu-nav { justify-content: center; }
    }
    @media (max-width: 600px) {
        .site-logo-square { width: 55px; height: 55px; }
        .logo-text { font-size: 0.85rem; }
        .global-timer { padding: 4px 12px; }
        .timer-value { font-size: 0.9rem; }
        .container { margin: 20px 15px !important; padding: 20px !important; }
    }
    </style>
</body>
</html>