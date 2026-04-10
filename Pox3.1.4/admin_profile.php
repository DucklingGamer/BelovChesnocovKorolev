<?php
require 'config.php';
include 'header.php';

$message = '';
$message_type = '';

// Получаем данные администратора
$stmt = $local_pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

// Функции для ресурсов (те же, что в user_profile.php)
function getServerLoad() {
    $load = sys_getloadavg();
    return [
        '1min' => $load[0],
        '5min' => $load[1],
        '15min' => $load[2]
    ];
}

function getMemoryUsage() {
    $memory = memory_get_usage();
    $peak = memory_get_peak_usage();
    return [
        'current' => $memory,
        'peak' => $peak,
        'current_mb' => round($memory / 1024 / 1024, 2),
        'peak_mb' => round($peak / 1024 / 1024, 2)
    ];
}

function getDiskUsage() {
    $free = disk_free_space('/');
    $total = disk_total_space('/');
    $used = $total - $free;
    return [
        'free' => $free,
        'total' => $total,
        'used' => $used,
        'free_gb' => round($free / 1024 / 1024 / 1024, 2),
        'total_gb' => round($total / 1024 / 1024 / 1024, 2),
        'used_gb' => round($used / 1024 / 1024 / 1024, 2),
        'percent_used' => round(($used / $total) * 100, 2)
    ];
}

$load = getServerLoad();
$memory = getMemoryUsage();
$disk = getDiskUsage();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>🌸 Профиль администратора</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="theme.js" defer></script>
    <style>
        .resource-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .resource-card {
            background: var(--pink-light);
            padding: 15px;
            border-radius: 15px;
            text-align: center;
        }
        .resource-value {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--red-heart);
        }
        .resource-label {
            font-size: 0.9rem;
            color: var(--purple-magical);
        }
        canvas {
            max-width: 100%;
            height: 200px;
        }
    </style>
</head>
<body>
    
    <div class="container">
        <h1><i class="fas fa-user-cog"></i> Профиль администратора</h1>
        
        <div class="card-grid">
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-id-card"></i> Информация об аккаунте
                </div>
                <div class="card-content">
                    <p><strong>Логин:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p><strong>ID:</strong> <?= $_SESSION['user_id'] ?></p>
                    <p><strong>Дата регистрации:</strong> <?= $user_data['created_at'] ?? 'Неизвестно' ?></p>
                </div>
            </div>
            
            <div class="card">
                <div class="card-title">
                    <i class="fas fa-database"></i> Бэкап базы данных
                </div>
                <div class="card-content">
                    <p>Создайте резервную копию всех баз данных:</p>
                    <a href="backup.php" class="btn btn-success" style="margin-top: 10px;">
                        <i class="fas fa-download"></i> Скачать бэкап
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Блок системных ресурсов -->
        <div class="card">
            <div class="card-title">
                <i class="fas fa-chart-line"></i> Системные ресурсы
            </div>
            <div class="card-content">
                <div class="resource-stats">
                    <div class="resource-card">
                        <div class="resource-value"><?= $load['1min'] ?></div>
                        <div class="resource-label">CPU Load (1 мин)</div>
                    </div>
                    <div class="resource-card">
                        <div class="resource-value"><?= $load['5min'] ?></div>
                        <div class="resource-label">CPU Load (5 мин)</div>
                    </div>
                    <div class="resource-card">
                        <div class="resource-value"><?= $memory['current_mb'] ?> MB</div>
                        <div class="resource-label">RAM Usage</div>
                    </div>
                    <div class="resource-card">
                        <div class="resource-value"><?= $disk['percent_used'] ?>%</div>
                        <div class="resource-label">Disk Usage</div>
                    </div>
                </div>
                
                <canvas id="resourceChart" width="400" height="200"></canvas>
                <script>
                const ctx = document.getElementById('resourceChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['1 мин', '5 мин', '15 мин'],
                        datasets: [{
                            label: 'CPU Load',
                            data: [<?= $load['1min'] ?>, <?= $load['5min'] ?>, <?= $load['15min'] ?>],
                            borderColor: 'var(--red-heart)',
                            backgroundColor: 'rgba(255, 77, 109, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
                </script>
            </div>
        </div>
    </div>
    <?php include 'game_modal.php'; ?>
</body>
</html>