// theme.js - Простая рабочая система тем
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем сохраненную тему
    const savedTheme = localStorage.getItem('kawaii-theme');
    const savedMode = localStorage.getItem('kawaii-theme-mode') || 'manual';
    
    if (savedMode === 'auto') {
        const hour = new Date().getHours();
        const autoTheme = (hour >= 20 || hour < 7) ? 'dark' : 'default';
        applyTheme(autoTheme);
        localStorage.setItem('kawaii-theme', autoTheme);
    } else if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        applyTheme('default');
    }
    
    // Создаем переключатель тем
    createThemeSwitcher();
    
    // Запускаем проверку авто-темы каждую минуту
    setInterval(function() {
        const mode = localStorage.getItem('kawaii-theme-mode');
        if (mode === 'auto') {
            const hour = new Date().getHours();
            const currentTheme = localStorage.getItem('kawaii-theme');
            const shouldBeTheme = (hour >= 20 || hour < 7) ? 'dark' : 'default';
            
            if (currentTheme !== shouldBeTheme) {
                applyTheme(shouldBeTheme);
                localStorage.setItem('kawaii-theme', shouldBeTheme);
                updateActiveButton(shouldBeTheme);
            }
        }
    }, 60000);
});

function applyTheme(theme) {
    // Устанавливаем data-атрибут на body
    document.body.setAttribute('data-theme', theme);
    
    // Сохраняем тему
    localStorage.setItem('kawaii-theme', theme);
    
    // Обновляем активную кнопку
    updateActiveButton(theme);
}

function updateActiveButton(theme) {
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.theme === theme) {
            btn.classList.add('active');
        }
    });
    
    // Авто-кнопка
    const autoBtn = document.querySelector('.theme-btn[data-theme="auto"]');
    if (autoBtn) {
        const mode = localStorage.getItem('kawaii-theme-mode');
        if (mode === 'auto') {
            autoBtn.classList.add('active');
        } else {
            autoBtn.classList.remove('active');
        }
    }
}

function createThemeSwitcher() {
    // Удаляем старый если есть
    const old = document.querySelector('.theme-switcher');
    if (old) old.remove();
    
    // Создаем новый
    const switcher = document.createElement('div');
    switcher.className = 'theme-switcher';
    switcher.innerHTML = `
        <div class="theme-btn" data-theme="default" title="Светлая тема">
            <div class="theme-preview default"></div>
            <span>☀️</span>
        </div>
        <div class="theme-btn" data-theme="dark" title="Тёмная тема">
            <div class="theme-preview dark"></div>
            <span>🌙</span>
        </div>
        <div class="theme-btn" data-theme="pastel" title="Пастельная">
            <div class="theme-preview pastel"></div>
            <span>🎀</span>
        </div>
        <div class="theme-btn" data-theme="ocean" title="Океан">
            <div class="theme-preview ocean"></div>
            <span>🌊</span>
        </div>
        <div class="theme-btn" data-theme="forest" title="Лес">
            <div class="theme-preview forest"></div>
            <span>🌿</span>
        </div>
        <div class="theme-divider"></div>
        <div class="theme-btn" data-theme="auto" title="Авто (тёмная ночью)">
            <div class="theme-preview auto"></div>
            <span>🔄</span>
        </div>
    `;
    
    document.body.appendChild(switcher);
    
    // Стили для переключателя (если их нет)
    if (!document.querySelector('#theme-switcher-styles')) {
        const style = document.createElement('style');
        style.id = 'theme-switcher-styles';
        style.textContent = `
            .theme-switcher {
                position: fixed;
                top: 100px;
                right: 20px;
                background: white;
                padding: 12px;
                border-radius: 20px;
                border: 3px solid var(--pink-sakura);
                box-shadow: 0 10px 30px var(--shadow-pink);
                z-index: 1000;
                display: flex;
                flex-direction: column;
                gap: 6px;
            }
            .theme-divider {
                height: 1px;
                background: var(--pink-light);
                margin: 4px 0;
            }
            .theme-btn {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 8px 12px;
                border-radius: 15px;
                cursor: pointer;
                transition: all 0.2s;
                border: 2px solid transparent;
            }
            .theme-btn:hover {
                background: var(--pink-light);
            }
            .theme-btn.active {
                background: var(--pink-light);
                border-color: var(--pink-sakura);
            }
            .theme-preview {
                width: 18px;
                height: 18px;
                border-radius: 50%;
                border: 2px solid var(--pink-sakura);
            }
            .theme-preview.default { background: linear-gradient(135deg, #ffafcc, #cdb4db, #a2d2ff); }
            .theme-preview.dark { background: linear-gradient(135deg, #2a1a1f, #7c5c8c, #5c7ca8); }
            .theme-preview.pastel { background: linear-gradient(135deg, #ffb7c5, #e6e6fa, #b0e0e6); }
            .theme-preview.ocean { background: linear-gradient(135deg, #5dade2, #3498db, #58d68d); }
            .theme-preview.forest { background: linear-gradient(135deg, #73c6b6, #58d68d, #5dade2); }
            .theme-preview.auto { background: linear-gradient(135deg, #ffafcc 0%, #ffafcc 50%, #2a1a1f 50%, #2a1a1f 100%); }
            .theme-btn span { font-size: 1.2rem; min-width: 24px; text-align: center; }
            
            @media (max-width: 768px) {
                .theme-switcher {
                    top: auto;
                    bottom: 20px;
                    right: 20px;
                    flex-direction: row;
                    padding: 8px;
                }
                .theme-btn span { display: none; }
                .theme-divider { width: 1px; height: auto; margin: 0 4px; }
            }
        `;
        document.head.appendChild(style);
    }
    
    // Обработчики
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const theme = this.dataset.theme;
            
            if (theme === 'auto') {
                localStorage.setItem('kawaii-theme-mode', 'auto');
                const hour = new Date().getHours();
                const autoTheme = (hour >= 20 || hour < 7) ? 'dark' : 'default';
                applyTheme(autoTheme);
                localStorage.setItem('kawaii-theme', autoTheme);
            } else {
                localStorage.setItem('kawaii-theme-mode', 'manual');
                applyTheme(theme);
            }
            
            updateActiveButton(theme);
        });
    });
    
    // Начальное состояние
    const currentTheme = localStorage.getItem('kawaii-theme') || 'default';
    const currentMode = localStorage.getItem('kawaii-theme-mode') || 'manual';
    
    if (currentMode === 'auto') {
        updateActiveButton('auto');
    } else {
        updateActiveButton(currentTheme);
    }
}