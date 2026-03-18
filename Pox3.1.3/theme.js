// theme.js
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем сохраненную тему
    const savedTheme = localStorage.getItem('kawaii-theme') || 'default';
    applyTheme(savedTheme);
    
    // Создаем переключатель тем
    createThemeSwitcher();
});

function applyTheme(theme) {
    // Удаляем предыдущие темы
    document.querySelectorAll('.theme-style').forEach(style => style.remove());
    
    // ВАЖНОЕ ИСПРАВЛЕНИЕ: Сохраняем ВСЕ темы, включая default
    localStorage.setItem('kawaii-theme', theme);
    
    if (theme === 'default') {
        // Для темы по умолчанию просто удаляем все кастомные стили
        // и возвращаем значения CSS переменных к исходным
        resetToDefaultTheme();
        
        // Обновляем активную кнопку в переключателе
        document.querySelectorAll('.theme-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.theme === theme) {
                btn.classList.add('active');
            }
        });
        return;
    }
    
    const style = document.createElement('style');
    style.className = 'theme-style';
    
    switch(theme) {
        case 'dark':
            style.textContent = `
                :root {
                    --pink-sakura: #d45d79;
                    --pink-neko: #b5435e;
                    --pink-light: #2a1a1f;
                    --purple-lavender: #7c5c8c;
                    --purple-magical: #6d5c8c;
                    --blue-sky: #5c7ca8;
                    --green-matcha: #7ca85c;
                    --yellow-happy: #cca700;
                    --red-heart: #d45d79;
                    --shadow-pink: rgba(212, 93, 121, 0.2);
                    --shadow-purple: rgba(124, 92, 140, 0.2);
                }
                
                body {
                    background: 
                        radial-gradient(circle at 10% 20%, rgba(212, 93, 121, 0.1) 0%, transparent 20%),
                        radial-gradient(circle at 90% 80%, rgba(92, 124, 168, 0.1) 0%, transparent 20%),
                        linear-gradient(135deg, #1a0f12 0%, #15121a 100%);
                    color: #c9b8d9;
                }
                
                .container {
                    background: rgba(30, 25, 35, 0.95);
                    border: 3px solid var(--pink-neko);
                    color: #c9b8d9;
                }
                
                table {
                    background: #201a25;
                }
                
                tr:nth-child(even) {
                    background: rgba(212, 93, 121, 0.05);
                }
                
                .card {
                    background: #201a25;
                    border: 3px solid #2a1a1f;
                }
                
                .stat-card {
                    background: linear-gradient(145deg, #201a25, #2a1a1f);
                }
                
                input, select, textarea {
                    background: rgba(42, 26, 31, 0.7);
                    color: #c9b8d9;
                    border: 2px solid var(--pink-neko);
                }
                
                input:focus, select:focus, textarea:focus {
                    background: #2a1a1f;
                }
                
                .menu {
                    background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
                }
            `;
            break;
            
        case 'pastel':
            style.textContent = `
                :root {
                    --pink-sakura: #ffb7c5;
                    --pink-neko: #ffd1dc;
                    --pink-light: #fff0f5;
                    --purple-lavender: #e6e6fa;
                    --purple-magical: #d8bfd8;
                    --blue-sky: #b0e0e6;
                    --green-matcha: #d1e7dd;
                    --yellow-happy: #fffacd;
                    --red-heart: #ffb7c5;
                    --shadow-pink: rgba(255, 183, 197, 0.3);
                    --shadow-purple: rgba(230, 230, 250, 0.3);
                }
                
                body {
                    background: 
                        radial-gradient(circle at 10% 20%, rgba(255, 183, 197, 0.15) 0%, transparent 20%),
                        radial-gradient(circle at 90% 80%, rgba(176, 224, 230, 0.15) 0%, transparent 20%),
                        linear-gradient(135deg, #fffaf0 0%, #f8f8ff 100%);
                }
                
                .menu {
                    background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
                }
            `;
            break;
            
        case 'ocean':
            style.textContent = `
                :root {
                    --pink-sakura: #5dade2;
                    --pink-neko: #85c1e9;
                    --pink-light: #ebf5fb;
                    --purple-lavender: #a569bd;
                    --purple-magical: #bb8fce;
                    --blue-sky: #3498db;
                    --green-matcha: #58d68d;
                    --yellow-happy: #f7dc6f;
                    --red-heart: #e74c3c;
                    --shadow-pink: rgba(93, 173, 226, 0.3);
                    --shadow-purple: rgba(165, 105, 189, 0.3);
                }
                
                body {
                    background: 
                        radial-gradient(circle at 10% 20%, rgba(93, 173, 226, 0.15) 0%, transparent 20%),
                        radial-gradient(circle at 90% 80%, rgba(52, 152, 219, 0.15) 0%, transparent 20%),
                        linear-gradient(135deg, #eaf2f8 0%, #f4f6f7 100%);
                }
                
                .menu {
                    background: linear-gradient(145deg, var(--pink-sakura), var(--blue-sky));
                }
                
                .container::before {
                    background: linear-gradient(90deg, 
                        var(--pink-sakura) 0%, 
                        var(--blue-sky) 25%, 
                        var(--green-matcha) 50%,
                        var(--purple-lavender) 75%,
                        var(--pink-sakura) 100%);
                }
            `;
            break;
            
        case 'forest':
            style.textContent = `
                :root {
                    --pink-sakura: #73c6b6;
                    --pink-neko: #a2d9ce;
                    --pink-light: #e8f6f3;
                    --purple-lavender: #a569bd;
                    --purple-magical: #bb8fce;
                    --blue-sky: #5dade2;
                    --green-matcha: #58d68d;
                    --yellow-happy: #f7dc6f;
                    --red-heart: #e74c3c;
                    --shadow-pink: rgba(115, 198, 182, 0.3);
                    --shadow-purple: rgba(165, 105, 189, 0.3);
                }
                
                body {
                    background: 
                        radial-gradient(circle at 10% 20%, rgba(115, 198, 182, 0.15) 0%, transparent 20%),
                        radial-gradient(circle at 90% 80%, rgba(88, 214, 141, 0.15) 0%, transparent 20%),
                        linear-gradient(135deg, #e8f6f3 0%, #f4f6f7 100%);
                }
                
                .menu {
                    background: linear-gradient(145deg, var(--pink-sakura), var(--green-matcha));
                }
                
                .container::before {
                    background: linear-gradient(90deg, 
                        var(--pink-sakura) 0%, 
                        var(--green-matcha) 25%, 
                        var(--blue-sky) 50%,
                        var(--purple-lavender) 75%,
                        var(--pink-sakura) 100%);
                }
            `;
            break;
    }
    
    document.head.appendChild(style);
    
    // Обновляем активную кнопку в переключателе
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.theme === theme) {
            btn.classList.add('active');
        }
    });
}

// НОВАЯ ФУНКЦИЯ: Сброс к теме по умолчанию
function resetToDefaultTheme() {
    // Явно устанавливаем CSS переменные к значениям по умолчанию
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
            --shadow-pink: rgba(255, 175, 204, 0.3);
            --shadow-purple: rgba(205, 180, 219, 0.3);
        }
        
        body {
            background: 
                radial-gradient(circle at 10% 20%, rgba(255, 175, 204, 0.2) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(162, 210, 255, 0.2) 0%, transparent 20%),
                linear-gradient(135deg, #fff0f3 0%, #f8f7ff 100%);
            color: #5a4a6a;
        }
        
        .container {
            background: rgba(255, 255, 255, 0.95);
            border: 3px solid var(--pink-neko);
            color: #5a4a6a;
        }
        
        .menu {
            background: linear-gradient(145deg, var(--pink-sakura), var(--purple-lavender));
        }
    `;
    
    // Создаем стиль для сброса
    const resetStyle = document.createElement('style');
    resetStyle.className = 'theme-reset-style';
    resetStyle.textContent = defaultStyles;
    document.head.appendChild(resetStyle);
}

function createThemeSwitcher() {
    // Создаем контейнер для переключателя
    const switcher = document.createElement('div');
    switcher.className = 'theme-switcher';
    switcher.innerHTML = `
        <div class="theme-btn" data-theme="default" title="По умолчанию">
            <div class="theme-preview default"></div>
            <span>🌸</span>
        </div>
        <div class="theme-btn" data-theme="dark" title="Темная">
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
    `;
    
    // Добавляем стили для переключателя
    const style = document.createElement('style');
    style.textContent = `
        .theme-switcher {
            position: fixed;
            top: 100px;
            right: 20px;
            background: white;
            padding: 15px;
            border-radius: 20px;
            border: 3px solid var(--pink-sakura);
            box-shadow: 0 10px 30px var(--shadow-pink);
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .theme-switcher:hover {
            transform: translateX(-5px);
            box-shadow: 0 15px 40px var(--shadow-pink);
        }
        
        .theme-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 15px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .theme-btn:hover {
            background: var(--pink-light);
            transform: scale(1.05);
        }
        
        .theme-btn.active {
            background: var(--pink-light);
            border-color: var(--pink-sakura);
        }
        
        .theme-preview {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid var(--pink-sakura);
        }
        
        .theme-preview.default {
            background: linear-gradient(135deg, #ffafcc, #cdb4db, #a2d2ff);
        }
        
        .theme-preview.dark {
            background: linear-gradient(135deg, #2a1a1f, #7c5c8c, #5c7ca8);
        }
        
        .theme-preview.pastel {
            background: linear-gradient(135deg, #ffb7c5, #e6e6fa, #b0e0e6);
        }
        
        .theme-preview.ocean {
            background: linear-gradient(135deg, #5dade2, #3498db, #58d68d);
        }
        
        .theme-preview.forest {
            background: linear-gradient(135deg, #73c6b6, #58d68d, #5dade2);
        }
        
        .theme-btn span {
            font-size: 1.2rem;
            min-width: 24px;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .theme-switcher {
                top: auto;
                bottom: 20px;
                right: 20px;
                flex-direction: row;
                padding: 10px;
            }
            
            .theme-btn span {
                display: none;
            }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(switcher);
    
    // Добавляем обработчики событий
    document.querySelectorAll('.theme-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const theme = this.dataset.theme;
            applyTheme(theme);
        });
    });
}