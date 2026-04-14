// main.js - универсальные скрипты для всех страниц

document.addEventListener('DOMContentLoaded', function() {
    // ========== ТАЙМЕР ==========
    initTimer();
    
    // ========== УВЕДОМЛЕНИЯ О СМЕНЕ РЕЖИМА ==========
    initGameStateNotifications();
});

function initTimer() {
    const timerLabel = document.getElementById('timerLabel');
    const timerValue = document.getElementById('timerValue');
    
    if (!timerLabel || !timerValue) return;
    
    let currentState = document.body.dataset.gameState || 'work';
    let workTime = parseInt(document.body.dataset.workTime) || 2700;
    let breakTime = parseInt(document.body.dataset.breakTime) || 300;
    let stateStartTime = parseInt(document.body.dataset.stateStartTime) || Date.now();
    
    function updateTimer() {
        const now = Date.now();
        const elapsed = Math.floor((now - stateStartTime) / 1000);
        let remaining, label;
        
        if (currentState === 'work') {
            remaining = workTime - elapsed;
            label = 'До отдыха';
        } else {
            remaining = breakTime - elapsed;
            label = 'До работы';
        }
        
        if (remaining < 0) remaining = 0;
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        
        timerLabel.textContent = label;
        timerValue.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (remaining < 60 && remaining > 0) {
            timerValue.style.color = '#ff4d6d';
            timerValue.style.animation = 'pulse 1s infinite';
        } else {
            timerValue.style.color = 'var(--red-heart)';
            timerValue.style.animation = 'none';
        }
        
        if (remaining <= 0) {
            location.reload();
        }
    }
    
    setInterval(updateTimer, 1000);
    updateTimer();
}

function initGameStateNotifications() {
    const notificationData = document.body.dataset.gameNotification;
    if (!notificationData || notificationData === '[]' || notificationData === '{}') return;
    
    try {
        const data = JSON.parse(notificationData);
        if (data.need_message) {
            if (data.force_open_game) {
                setTimeout(() => {
                    if (typeof openColorGame === 'function') {
                        openColorGame(true);
                    }
                }, 500);
            }
            showGameStateMessage(data.message, data.force_open_game);
        }
    } catch(e) {
        console.log('Notification error:', e);
    }
}

function showGameStateMessage(message, isForced) {
    const notification = document.createElement('div');
    notification.className = 'game-state-notification';
    notification.innerHTML = `
        <div class="reminder-content">
            <div class="reminder-icon">${isForced ? '🎮' : '⏰'}</div>
            <div class="reminder-text">${message}</div>
            <button onclick="this.parentElement.parentElement.remove()" class="btn btn-edit btn-small">OK</button>
        </div>
    `;
    document.body.appendChild(notification);
    setTimeout(() => notification.classList.add('show'), 100);
    setTimeout(() => {
        if (notification.parentNode) notification.remove();
    }, 8000);
}