<?php
// game_modal.php - модальное окно с играми
?>
<div id="colorGameModal" class="game-modal" style="display: none;">
    <div class="game-modal-content">
        <div class="game-modal-header">
            <h2><i class="fas fa-palette"></i> <span id="gameModalTitle">Игра-перерыв</span></h2>
            <span class="game-modal-close" id="gameModalCloseBtn">&times;</span>
        </div>
        <div class="game-modal-body">
            <div id="gameSelection" style="display: none; text-align: center; padding: 20px;">
                <p>Выберите игру:</p>
                <select id="gameSelect" class="btn" style="margin: 10px; padding: 10px;">
                    <option value="color">🎨 Color Method</option>
                    <option value="shape">⬛ Shape Method</option>
                    <option value="type">⌨️ Type Method</option>
                    <option value="bezier">✏️ Bezier Method</option>
                </select>
                <button id="startGameBtn" class="btn btn-success">Начать игру</button>
            </div>
            <iframe id="gameIframe" src="" frameborder="0" allow="fullscreen" style="width: 100%; height: 100%; display: none;"></iframe>
        </div>
        <div class="game-modal-footer">
            <div id="breakTimer" style="display: none; color: var(--red-heart); font-weight: bold;"></div>
            <p><i class="fas fa-info-circle"></i> Подберите точный цвет, форму или кривую. Проверьте свои навыки!</p>
            <button id="closeGameBtn" class="btn btn-edit close-game-btn"><i class="fas fa-times"></i> Закрыть</button>
        </div>
    </div>
</div>

<script>
// Список игр
const games = {
    color: 'https://color.method.ac/',
    shape: 'https://shape.method.ac/',
    type: 'https://type.method.ac/',
    bezier: 'https://bezier.method.ac/'
};

let forcedMode = false;          // принудительный режим (отдых)
let breakEndTime = null;         // время окончания отдыха (timestamp)
let timerInterval = null;

// Функция открытия модального окна
window.openColorGame = function(forced = false, randomGame = null) {
    forcedMode = forced;
    const modal = document.getElementById('colorGameModal');
    const iframe = document.getElementById('gameIframe');
    const gameSelection = document.getElementById('gameSelection');
    const modalTitle = document.getElementById('gameModalTitle');
    const closeBtn = document.getElementById('gameModalCloseBtn');
    const closeFooterBtn = document.getElementById('closeGameBtn');
    const breakTimerDiv = document.getElementById('breakTimer');
    const startGameBtn = document.getElementById('startGameBtn');

    // Сброс
    if (timerInterval) clearInterval(timerInterval);
    iframe.style.display = 'none';
    gameSelection.style.display = 'none';
    breakTimerDiv.style.display = 'none';
    iframe.src = '';

    if (forced) {
        // Принудительный режим: случайная игра, блокировка закрытия, таймер
        modalTitle.innerText = '🎮 Обязательный отдых';
        const gameKeys = Object.keys(games);
        const selectedGame = randomGame || gameKeys[Math.floor(Math.random() * gameKeys.length)];
        iframe.src = games[selectedGame];
        iframe.style.display = 'block';
        gameSelection.style.display = 'none';

        // Устанавливаем время окончания отдыха (5 минут от текущего момента)
        breakEndTime = Date.now() + (<?= $_SESSION['break_time_seconds'] ?? 300 ?> * 1000);
        breakTimerDiv.style.display = 'block';
        updateBreakTimer();

        // Блокируем кнопки закрытия
        closeBtn.style.pointerEvents = 'none';
        closeBtn.style.opacity = '0.5';
        closeFooterBtn.style.pointerEvents = 'none';
        closeFooterBtn.style.opacity = '0.5';
        closeFooterBtn.disabled = true;

        // Запускаем таймер
        timerInterval = setInterval(() => {
            const remaining = breakEndTime - Date.now();
            if (remaining <= 0) {
                clearInterval(timerInterval);
                breakTimerDiv.innerHTML = 'Отдых завершён!';
                // Разблокируем закрытие
                closeBtn.style.pointerEvents = 'auto';
                closeBtn.style.opacity = '1';
                closeFooterBtn.style.pointerEvents = 'auto';
                closeFooterBtn.style.opacity = '1';
                closeFooterBtn.disabled = false;
                // Автоматически закроем через 5 секунд? Не будем, пусть пользователь закроет сам
            } else {
                const minutes = Math.floor(remaining / 60000);
                const seconds = Math.floor((remaining % 60000) / 1000);
                breakTimerDiv.innerHTML = `⏱️ Осталось отдыхать: ${minutes}:${seconds.toString().padStart(2, '0')}`;
            }
        }, 1000);
    } else {
        // Ручной режим: показываем выбор игры
        modalTitle.innerText = '🎮 Игра-перерыв';
        gameSelection.style.display = 'block';
        iframe.style.display = 'none';
        breakTimerDiv.style.display = 'none';

        // Разблокируем закрытие (на случай если было заблокировано)
        closeBtn.style.pointerEvents = 'auto';
        closeBtn.style.opacity = '1';
        closeFooterBtn.style.pointerEvents = 'auto';
        closeFooterBtn.style.opacity = '1';
        closeFooterBtn.disabled = false;

        // Обработчик кнопки "Начать игру"
        startGameBtn.onclick = function() {
            const selected = document.getElementById('gameSelect').value;
            iframe.src = games[selected];
            iframe.style.display = 'block';
            gameSelection.style.display = 'none';
        };
    }

    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
};

function updateBreakTimer() {
    // вспомогательная функция, вызывается в интервале
}

window.closeColorGame = function() {
    if (forcedMode && breakEndTime && Date.now() < breakEndTime) {
        alert('Отдых ещё не закончился! Пожалуйста, подождите.');
        return;
    }
    const modal = document.getElementById('colorGameModal');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
    if (timerInterval) clearInterval(timerInterval);
    // Сброс флага принудительного режима, чтобы при следующем открытии не было блокировки
    forcedMode = false;
};

// Закрытие по ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('colorGameModal');
        if (modal.style.display === 'block') {
            if (forcedMode && breakEndTime && Date.now() < breakEndTime) {
                alert('Отдых ещё не закончился! Пожалуйста, подождите.');
                return;
            }
            closeColorGame();
        }
    }
});

// Обработчики закрытия
document.getElementById('gameModalCloseBtn').addEventListener('click', closeColorGame);
document.getElementById('closeGameBtn').addEventListener('click', closeColorGame);

// Закрытие по клику на фон
document.getElementById('colorGameModal').addEventListener('click', function(e) {
    if (e.target === this) closeColorGame();
});
</script>

<style>
/* Стили для модального окна игры */
.game-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(5px);
    animation: fadeIn 0.3s ease-out;
}

.game-modal-content {
    background: white;
    margin: 5% auto;
    width: 90%;
    max-width: 1000px;
    border-radius: 30px;
    border: 4px solid var(--pink-sakura);
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    overflow: hidden;
    animation: slideInDown 0.4s ease-out;
}

.game-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 25px;
    background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
    color: white;
}

.game-modal-header h2 {
    margin: 0;
    color: white;
    font-size: 1.5rem;
}

.game-modal-header h2 i {
    margin-right: 10px;
}

.game-modal-close {
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
    line-height: 1;
}

.game-modal-close:hover {
    transform: scale(1.2);
    color: var(--red-heart);
}

.game-modal-body {
    padding: 0;
    height: 60vh;
    min-height: 500px;
}

.game-modal-body iframe {
    width: 100%;
    height: 100%;
    border: none;
}

.game-modal-footer {
    padding: 15px 25px;
    background: var(--pink-light);
    border-top: 2px solid var(--pink-sakura);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.game-modal-footer p {
    margin: 0;
    color: var(--purple-magical);
    font-size: 0.9rem;
}

.close-game-btn {
    background: var(--purple-lavender);
    color: white;
    padding: 8px 20px;
    font-size: 0.9rem;
}

.close-game-btn:hover {
    background: var(--purple-magical);
}

@keyframes slideInDown {
    from {
        transform: translateY(-100px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@media (max-width: 768px) {
    .game-modal-content {
        margin: 10% auto;
        width: 95%;
    }
    
    .game-modal-body {
        height: 50vh;
        min-height: 400px;
    }
    
    .game-modal-footer {
        flex-direction: column;
        text-align: center;
    }
    
    .game-modal-header h2 {
        font-size: 1.2rem;
    }
}
</style>