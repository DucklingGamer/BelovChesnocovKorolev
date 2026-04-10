<?php
require 'user_config.php';
include 'header.php';

$message = '';
$message_type = '';

// Получаем текущие данные пользователя из локальной БД
$stmt = $local_pdo->prepare("SELECT * FROM site_users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

$email_confirmed = isset($user_data['email_confirmed']) ? $user_data['email_confirmed'] : false;
$phone_confirmed = isset($user_data['phone_confirmed']) ? $user_data['phone_confirmed'] : false;

// Обновление профиля
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        
        $stmt = $local_pdo->prepare("UPDATE site_users SET email = ?, phone = ? WHERE id = ?");
        if ($stmt->execute([$email, $phone, $_SESSION['user_id']])) {
            $_SESSION['email'] = $email;
            $_SESSION['phone'] = $phone;
            $message = 'Профиль обновлен! Подтвердите новые контакты.';
            $message_type = 'success';
            
            // Сбрасываем подтверждение
            $email_confirmed = false;
            $phone_confirmed = false;
        }
    }
    
    // Подтверждение email
    if (isset($_POST['confirm_email'])) {
        $code = rand(100000, 999999);
        $_SESSION['confirm_code'] = $code;
        $_SESSION['confirm_type'] = 'email';
        
        $message = "Код подтверждения отправлен на ваш email: $code";
        $message_type = 'info';
    }
    
    // Подтверждение телефона
    if (isset($_POST['confirm_phone'])) {
        $code = rand(100000, 999999);
        $_SESSION['confirm_code'] = $code;
        $_SESSION['confirm_type'] = 'phone';
        
        $message = "Код подтверждения отправлен на ваш телефон: $code";
        $message_type = 'info';
    }
    
    // Проверка кода
    if (isset($_POST['verify_code'])) {
        if ($_POST['code'] == $_SESSION['confirm_code']) {
            if ($_SESSION['confirm_type'] == 'email') {
                $local_pdo->exec("UPDATE site_users SET email_confirmed = 1 WHERE id = {$_SESSION['user_id']}");
                $email_confirmed = true;
                $message = 'Email подтвержден!';
            } else {
                $local_pdo->exec("UPDATE site_users SET phone_confirmed = 1 WHERE id = {$_SESSION['user_id']}");
                $phone_confirmed = true;
                $message = 'Телефон подтвержден!';
            }
            $message_type = 'success';
            unset($_SESSION['confirm_code']);
        } else {
            $message = 'Неверный код';
            $message_type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Мой профиль</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="theme.js" defer></script>
    <style>
        .verification-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
            margin-left: 10px;
        }
        .verified { background: #48bb78; color: white; }
        .not-verified { background: #ff4d6d; color: white; }
        .confirm-form { margin-top: 10px; padding: 15px; background: var(--pink-light); border-radius: 10px; }
    </style>
</head>
<body>

    
    <div class="container">
        <h1><i class="fas fa-user-circle"></i> Мой профиль</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-id-card"></i> Основная информация
                </div>
                <div class="card-content">
                    <p><strong>Логин:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Email 
                                <span class="verification-status <?= $email_confirmed ? 'verified' : 'not-verified' ?>">
                                    <?= $email_confirmed ? '✓ Подтвержден' : '✗ Не подтвержден' ?>
                                </span>
                            </label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Телефон
                                <span class="verification-status <?= $phone_confirmed ? 'verified' : 'not-verified' ?>">
                                    <?= $phone_confirmed ? '✓ Подтвержден' : '✗ Не подтвержден' ?>
                                </span>
                            </label>
                            <input type="text" name="phone" value="<?= htmlspecialchars($_SESSION['phone'] ?? '') ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-success">
                            <i class="fas fa-save"></i> Сохранить изменения
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-shield-alt"></i> Подтверждение контактов
                </div>
                <div class="card-content">
                    <?php if (!$email_confirmed): ?>
                        <div class="confirm-form">
                            <h4>Подтверждение email</h4>
                            <form method="POST">
                                <button type="submit" name="confirm_email" class="btn btn-edit btn-small">
                                    <i class="fas fa-envelope"></i> Отправить код
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!$phone_confirmed): ?>
                        <div class="confirm-form">
                            <h4>Подтверждение телефона</h4>
                            <form method="POST">
                                <button type="submit" name="confirm_phone" class="btn btn-edit btn-small">
                                    <i class="fas fa-phone"></i> Отправить код
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['confirm_code'])): ?>
                        <div class="confirm-form">
                            <h4>Введите код подтверждения</h4>
                            <form method="POST">
                                <input type="text" name="code" placeholder="6-значный код" required>
                                <button type="submit" name="verify_code" class="btn btn-success btn-small">
                                    Проверить
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include 'game_modal.php'; ?>
</body>
</html>