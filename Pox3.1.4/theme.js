// theme.js - Глобальные темы для всех страниц (с защитой от дублирования)

// Флаг, чтобы не создавать переключатель несколько раз
let themeInitialized = false;

// Делаем функцию applyTheme глобальной
window.applyTheme = function(theme) {
    // Удаляем предыдущие кастомные стили
    document.querySelectorAll('.theme-style').forEach(style => style.remove());
    
    // Сохраняем выбранную тему
    localStorage.setItem('kawaii-theme', theme);
    
    // Сбрасываем inline-стили переменных
    const root = document.documentElement;
    root.style.removeProperty('--pink-sakura');
    root.style.removeProperty('--pink-neko');
    root.style.removeProperty('--pink-light');
    root.style.removeProperty('--purple-lavender');
    root.style.removeProperty('--purple-magical');
    root.style.removeProperty('--blue-sky');
    root.style.removeProperty('--green-matcha');
    root.style.removeProperty('--yellow-happy');
    root.style.removeProperty('--red-heart');
    root.style.removeProperty('--shadow-pink');
    root.style.removeProperty('--shadow-purple');
    
    if (theme === 'default') {
        resetToDefaultTheme();
    } else {
        const style = document.createElement('style');
        style.className = 'theme-style';
        
        switch(theme) {
            case 'dark':
                style.textContent = `
                    :root {
                        --pink-sakura: #c9708a;
                        --pink-neko: #a8556e;
                        --pink-light: #3d2a30;
                        --purple-lavender: #8b6b9e;
                        --purple-magical: #7a5c8c;
                        --blue-sky: #6b8fae;
                        --green-matcha: #7a9e6b;
                        --yellow-happy: #c9a840;
                        --red-heart: #c9708a;
                        --shadow-pink: rgba(201, 112, 138, 0.15);
                        --shadow-purple: rgba(107, 79, 122, 0.15);
                    }
                    body {
                        background: #1a1a2e;
                        color: #e0d4e8;
                    }
                    .container {
                        background: rgba(30, 30, 50, 0.95);
                        border: 3px solid var(--pink-neko);
                        color: #e0d4e8;
                    }
                    table {
                        background: #252540;
                    }
                    tr:nth-child(even) {
                        background: rgba(201, 112, 138, 0.08);
                    }
                    .card {
                        background: #252540;
                        border: 3px solid #3d2a30;
                        color: #e0d4e8;
                    }
                    .stat-card {
                        background: linear-gradient(145deg, #252540, #2d2d4a);
                        color: #e0d4e8;
                    }
                    input, select, textarea {
                        background: #ffffff !important;
                        color: #1a1a1a !important;
                        border: 2px solid var(--pink-neko) !important;
                    }
                    input::placeholder, textarea::placeholder {
                        color: #888888 !important;
                        opacity: 1 !important;
                    }
                    input:focus, select:focus, textarea:focus {
                        background: #ffffff !important;
                        color: #1a1a1a !important;
                        border-color: var(--pink-sakura) !important;
                        box-shadow: 0 0 10px rgba(201, 112, 138, 0.3) !important;
                    }
                    label {
                        color: #c9b8d9 !important;
                    }
                    .menu {
                        background: linear-gradient(145deg, #c9708a, #8b6b9e);
                    }
                    .theme-switcher, .auto-theme-container {
                        background: #252540;
                        border-color: var(--pink-sakura);
                        color: #e0d4e8;
                    }
                    .theme-btn {
                        background: #3d2a30;
                        color: #e0d4e8;
                    }
                    .theme-btn.active {
                        background: #c9708a;
                        color: white;
                    }
                    .alert {
                        background: #252540;
                    }
                    .alert-success {
                        background: #1a3a2a;
                        border-color: #7a9e6b;
                        color: #b8e0b8;
                    }
                    .alert-error {
                        background: #3a1a1a;
                        border-color: #c9708a;
                        color: #ffb8b8;
                    }
                    .alert-warning {
                        background: #3a3a1a;
                        border-color: #c9a840;
                        color: #ffe0a0;
                    }
                    .btn {
                        color: white;
                    }
                    .btn-edit {
                        background: linear-gradient(145deg, #8b6b9e, #7a5c8c);
                    }
                    .btn-delete {
                        background: linear-gradient(145deg, #c9708a, #a8556e);
                    }
                    .btn-success {
                        background: linear-gradient(145deg, #7a9e6b, #6b8f5a);
                    }
                    .global-timer {
                        background: #252540;
                        border-color: var(--pink-sakura);
                        color: #e0d4e8;
                    }
                    .timer-value {
                        color: #c9708a;
                    }
                `;
                break;
                
            case 'pastel':
                style.textContent = `
                    :root {
                        --pink-sakura: #e8b4b8;
                        --pink-neko: #e8c4c8;
                        --pink-light: #faf0f2;
                        --purple-lavender: #d4c4e0;
                        --purple-magical: #c8b4d8;
                        --blue-sky: #c4dce8;
                        --green-matcha: #d4e8c4;
                        --yellow-happy: #f5e8b4;
                        --red-heart: #e8b4b8;
                        --shadow-pink: rgba(232, 180, 184, 0.15);
                        --shadow-purple: rgba(200, 180, 216, 0.15);
                    }
                    body {
                        background: #f5f0f8;
                        color: #5a4a6a;
                    }
                    input, select, textarea {
                        background: #ffffff !important;
                        color: #333333 !important;
                        border: 2px solid var(--pink-neko) !important;
                    }
                    input::placeholder, textarea::placeholder {
                        color: #999999 !important;
                        opacity: 1 !important;
                    }
                    input:focus, select:focus, textarea:focus {
                        background: #ffffff !important;
                        color: #333333 !important;
                        border-color: var(--pink-sakura) !important;
                    }
                    .menu {
                        background: linear-gradient(145deg, #e8b4b8, #d4c4e0);
                    }
                `;
                break;
                
            case 'ocean':
                style.textContent = `
                    :root {
                        --pink-sakura: #6ba5d4;
                        --pink-neko: #8bb8e0;
                        --pink-light: #e8f0f8;
                        --purple-lavender: #b8a8d4;
                        --purple-magical: #a898c8;
                        --blue-sky: #5a9ec4;
                        --green-matcha: #7ab88a;
                        --yellow-happy: #e8d48a;
                        --red-heart: #d46b6b;
                        --shadow-pink: rgba(107, 165, 212, 0.15);
                        --shadow-purple: rgba(168, 152, 200, 0.15);
                    }
                    body {
                        background: #e0ecf4;
                        color: #3a5a6a;
                    }
                    input, select, textarea {
                        background: #ffffff !important;
                        color: #333333 !important;
                        border: 2px solid var(--pink-neko) !important;
                    }
                    input::placeholder, textarea::placeholder {
                        color: #999999 !important;
                        opacity: 1 !important;
                    }
                    input:focus, select:focus, textarea:focus {
                        background: #ffffff !important;
                        color: #333333 !important;
                        border-color: var(--pink-sakura) !important;
                    }
                    .menu {
                        background: linear-gradient(145deg, #6ba5d4, #8bb8e0);
                    }
                `;
                break;
                
            case 'forest':
                style.textContent = `
                    :root {
                        --pink-sakura: #7ab88a;
                        --pink-neko: #9ad0a8;
                        --pink-light: #e8f4ec;
                        --purple-lavender: #c4b0d4;
                        --purple-magical: #b4a0c8;
                        --blue-sky: #7a9eb8;
                        --green-matcha: #6aa87a;
                        --yellow-happy: #e0d48a;
                        --red-heart: #d47a7a;
                        --shadow-pink: rgba(122, 184, 138, 0.15);
                        --shadow-purple: rgba(180, 160, 200, 0.15);
                    }
                    body {
                        background: #e8f0e4;
                        color: #3a5a4a;
                    }
                    input, select, textarea {
                        background: #ffffff !important;
                        color: #333333 !important;
                        border: 2px solid var(--pink-neko) !important;
                    }
                    input::placeholder, textarea::placeholder {
                        color: #999999 !important;
                        opacity: 1 !important;
                    }
                    input:focus, select:focus, textarea:focus {
                        background: #ffffff !important;
                        color: #333333 !important;
                        border-color: var(--pink-sakura) !important;
                    }
                    .menu {
                        background: linear-gradient(145deg, #7ab88a, #9ad0a8);
                    }
                `;
                break;
        }
        document.head.appendChild(style);
    }
    
    // Обновляем активную кнопку
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.theme === theme) btn.classList.add('active');
    });
};

function resetToDefaultTheme() {
    const defaultStyles = `
        :root {
            --pink-sakura: #ffafcc;
            --pink-neko: #ffc8dd;
            --pink-light: #ffe5ec;
            --purple-lavender: #cdb4db;
            --purple-magical: #b8c0ff;
            --blue-sky: #a2d2ff;
            --green-matcha: #b5e48c;
            --yellow-happy: #ffea00;
            --red-heart: #ff4d6d;
            --shadow-pink: rgba(255, 175, 204, 0.15);
            --shadow-purple: rgba(205, 180, 219, 0.15);
        }
        body {
            background: radial-gradient(circle at 10% 20%, rgba(255,175,204,0.15) 0%, transparent 20%),
                        radial-gradient(circle at 90% 80%, rgba(162,210,255,0.15) 0%, transparent 20%),
                        linear-gradient(135deg, #fff0f3 0%, #f8f7ff 100%);
            color: #5a4a6a;
        }
        .container {
            background: rgba(255, 255, 255, 0.95);
            border: 3px solid var(--pink-neko);
            color: #5a4a6a;
        }
        input, select, textarea {
            background: #ffffff !important;
            color: #333333 !important;
            border: 2px solid var(--pink-neko) !important;
        }
        input::placeholder, textarea::placeholder {
            color: #aaaaaa !important;
            opacity: 1 !important;
        }
        input:focus, select:focus, textarea:focus {
            background: #ffffff !important;
            color: #333333 !important;
            border-color: var(--pink-sakura) !important;
            box-shadow: 0 0 10px rgba(255, 175, 204, 0.3) !important;
        }
        .menu {
            background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
        }
        table {
            background: white;
        }
        .card {
            background: white;
        }
    `;
    document.querySelectorAll('.theme-reset-style').forEach(style => style.remove());
    const resetStyle = document.createElement('style');
    resetStyle.className = 'theme-reset-style';
    resetStyle.textContent = defaultStyles;
    document.head.appendChild(resetStyle);
}

window.getTimeBasedTheme = function() {
    const hour = new Date().getHours();
    return (hour >= 22 || hour < 6) ? 'dark' : 'default';
};

// Инициализация при загрузке страницы (только один раз)
document.addEventListener('DOMContentLoaded', function() {
    if (themeInitialized) return;
    themeInitialized = true;
    
    const savedTheme = localStorage.getItem('kawaii-theme');
    const autoThemeEnabled = localStorage.getItem('auto-theme-enabled') !== 'false';
    
    let initialTheme;
    if (autoThemeEnabled) {
        initialTheme = getTimeBasedTheme();
    } else {
        initialTheme = savedTheme || 'default';
    }
    window.applyTheme(initialTheme);
    
    createThemeSwitcher();
});

function createThemeSwitcher() {
    // Удаляем старый переключатель, если есть
    const existingSwitcher = document.querySelector('.theme-switcher');
    if (existingSwitcher) existingSwitcher.remove();
    
    const switcher = document.createElement('div');
    switcher.className = 'theme-switcher';
    switcher.innerHTML = `
        <div class="theme-buttons">
            <div class="theme-btn" data-theme="default" title="По умолчанию">
                <div class="theme-preview default"></div>
                <span>🌸</span>
                <span class="theme-name">Default</span>
            </div>
            <div class="theme-btn" data-theme="dark" title="Темная">
                <div class="theme-preview dark"></div>
                <span>🌙</span>
                <span class="theme-name">Dark</span>
            </div>
            <div class="theme-btn" data-theme="pastel" title="Пастельная">
                <div class="theme-preview pastel"></div>
                <span>🎀</span>
                <span class="theme-name">Pastel</span>
            </div>
            <div class="theme-btn" data-theme="ocean" title="Океан">
                <div class="theme-preview ocean"></div>
                <span>🌊</span>
                <span class="theme-name">Ocean</span>
            </div>
            <div class="theme-btn" data-theme="forest" title="Лес">
                <div class="theme-preview forest"></div>
                <span>🌿</span>
                <span class="theme-name">Forest</span>
            </div>
        </div>
        <div class="auto-theme-container">
            <label class="auto-theme-label">
                <input type="checkbox" id="autoThemeCheckboxGlobal" ${localStorage.getItem('auto-theme-enabled') !== 'false' ? 'checked' : ''}>
                <span>🌙 Авто-тема (ночью темная)</span>
            </label>
        </div>
    `;
    
    const style = document.createElement('style');
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
            gap: 8px;
            min-width: 130px;
        }
        .theme-buttons {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .theme-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 6px 12px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .theme-btn:hover {
            background: var(--pink-light);
            transform: scale(1.02);
        }
        .theme-btn.active {
            background: var(--pink-light);
            border-color: var(--pink-sakura);
        }
        .theme-preview {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 2px solid var(--pink-sakura);
        }
        .theme-preview.default { background: linear-gradient(135deg, #ffafcc, #cdb4db, #a2d2ff); }
        .theme-preview.dark { background: linear-gradient(135deg, #2a1a1f, #7c5c8c, #5c7ca8); }
        .theme-preview.pastel { background: linear-gradient(135deg, #ffb7c5, #e6e6fa, #b0e0e6); }
        .theme-preview.ocean { background: linear-gradient(135deg, #5dade2, #3498db, #58d68d); }
        .theme-preview.forest { background: linear-gradient(135deg, #73c6b6, #58d68d, #5dade2); }
        .theme-name {
            font-size: 0.8rem;
            color: var(--purple-magical);
        }
        .auto-theme-container {
            border-top: 1px solid var(--pink-light);
            padding-top: 8px;
            margin-top: 4px;
        }
        .auto-theme-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 0.8rem;
            padding: 4px 0;
        }
        @media (max-width: 768px) {
            .theme-switcher {
                top: auto;
                bottom: 20px;
                right: 20px;
                flex-direction: row;
                flex-wrap: wrap;
                max-width: 300px;
                min-width: auto;
            }
            .theme-buttons {
                flex-direction: row;
                flex-wrap: wrap;
            }
            .theme-name {
                display: none;
            }
            .auto-theme-container {
                border-top: none;
                border-left: 1px solid var(--pink-light);
                padding-top: 0;
                padding-left: 8px;
                margin-top: 0;
            }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(switcher);
    
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const theme = this.dataset.theme;
            const autoCheckbox = document.getElementById('autoThemeCheckboxGlobal');
            if (autoCheckbox) autoCheckbox.checked = false;
            localStorage.setItem('auto-theme-enabled', 'false');
            window.applyTheme(theme);
        });
    });
    
    const autoCheckbox = document.getElementById('autoThemeCheckboxGlobal');
    if (autoCheckbox) {
        autoCheckbox.addEventListener('change', function(e) {
            const enabled = e.target.checked;
            localStorage.setItem('auto-theme-enabled', enabled);
            if (enabled) {
                window.applyTheme(window.getTimeBasedTheme());
            } else {
                const savedTheme = localStorage.getItem('kawaii-theme') || 'default';
                window.applyTheme(savedTheme);
            }
        });
    }
}