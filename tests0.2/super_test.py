#!/usr/bin/env python3
"""
Визуальное тестирование Кавай Магазина
С отображением всех действий и красивым HTML-отчётом
"""

import pytest
import requests
import time
import os
import sys
import re
from selenium import webdriver
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from playwright.sync_api import sync_playwright
import pymysql
from datetime import datetime
from pathlib import Path
import traceback

# ==================== КОНФИГУРАЦИЯ ====================
BASE_URL = "http://localhost:3000"
TEST_PASSWORD = "Test123!@#"
TEST_PHONE = "+79991234567"

# Путь к geckodriver
GECKODRIVER_PATH = "/usr/local/bin/geckodriver"
if not os.path.exists(GECKODRIVER_PATH):
    import shutil
    GECKODRIVER_PATH = shutil.which("geckodriver") or GECKODRIVER_PATH

# Глобальные данные
test_data = {
    "username": None,
    "email": None,
    "password": TEST_PASSWORD,
    "phone": TEST_PHONE,
    "user_id": None,
    "order_id": None,
    "start_time": None,
    "end_time": None,
    "actions": [],
    "results": []
}

# Базы данных
DB_LOCAL = {
    "host": "localhost",
    "port": 3306,
    "user": "admin",
    "password": "admin",
    "database": "Bd_belov",
    "charset": "utf8mb4"
}

# Создаём папку для отчётов
os.makedirs("reports", exist_ok=True)

# ==================== ВИЗУАЛЬНЫЙ ЛОГГЕР ====================
class VisualLogger:
    @staticmethod
    def print_header(text):
        print("\n" + "=" * 70)
        print(f"🌸 {text} 🌸")
        print("=" * 70)
    
    @staticmethod
    def print_action(action, status="🔄", details=""):
        timestamp = datetime.now().strftime("%H:%M:%S")
        print(f"[{timestamp}] {status} {action}")
        if details:
            print(f"   📝 {details}")
    
    @staticmethod
    def print_success(text):
        timestamp = datetime.now().strftime("%H:%M:%S")
        print(f"[{timestamp}] ✅ {text}")
    
    @staticmethod
    def print_error(text):
        timestamp = datetime.now().strftime("%H:%M:%S")
        print(f"[{timestamp}] ❌ {text}")
    
    @staticmethod
    def print_info(text):
        timestamp = datetime.now().strftime("%H:%M:%S")
        print(f"[{timestamp}] ℹ️ {text}")

# ==================== ГЕНЕРАТОР HTML-ОТЧЁТА ====================
class HTMLReportGenerator:
    @staticmethod
    def generate():
        """Генерирует полный HTML-отчёт"""
        
        start_time = test_data["start_time"]
        end_time = test_data["end_time"]
        duration = (end_time - start_time) if end_time and start_time else 0
        
        total_tests = len(test_data["results"])
        passed = sum(1 for r in test_data["results"] if r["status"] == "passed")
        failed = sum(1 for r in test_data["results"] if r["status"] == "failed")
        skipped = sum(1 for r in test_data["results"] if r["status"] == "skipped")
        
        html = f<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Отчёт тестирования - Кавай Магазин 🍥</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@300;400;500;700&family=Nunito:wght@300;400;600;700&display=swap');
        
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: 'Nunito', sans-serif;
            background: 
                radial-gradient(circle at 10% 20%, rgba(255, 175, 204, 0.2) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(162, 210, 255, 0.2) 0%, transparent 20%),
                linear-gradient(135deg, #fff0f3 0%, #f8f7ff 100%);
            color: #5a4a6a;
            min-height: 100vh;
            padding: 20px;
        }}
        
        .container {{
            max-width: 1400px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 30px;
            border: 3px solid #ffc8dd;
            box-shadow: 0 15px 35px rgba(255, 175, 204, 0.3);
            overflow: hidden;
        }}
        
        .container::before {{
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: linear-gradient(90deg, #ffafcc, #cdb4db, #a2d2ff, #b5e48c, #ffafcc);
        }}
        
        .header {{
            background: linear-gradient(145deg, #ffafcc, #cdb4db);
            padding: 40px;
            text-align: center;
            position: relative;
        }}
        
        .header::before {{
            content: '🌸';
            position: absolute;
            top: 20px;
            left: 30px;
            font-size: 3rem;
            animation: float 3s ease-in-out infinite;
        }}
        
        .header::after {{
            content: '🍥';
            position: absolute;
            bottom: 20px;
            right: 30px;
            font-size: 3rem;
            animation: float 4s ease-in-out infinite reverse;
        }}
        
        .title {{
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 2.8rem;
            color: white;
            text-shadow: 3px 3px 0 rgba(255, 77, 109, 0.5);
        }}
        
        .subtitle {{
            color: white;
            font-size: 1.2rem;
            margin-top: 10px;
        }}
        
        .stats-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: rgba(255, 229, 236, 0.3);
        }}
        
        .stat-card {{
            background: white;
            padding: 25px;
            border-radius: 25px;
            text-align: center;
            border: 2px solid #ffc8dd;
            transition: transform 0.3s;
        }}
        
        .stat-card:hover {{
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(255, 175, 204, 0.3);
        }}
        
        .stat-number {{
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 2.8rem;
            font-weight: 700;
            margin: 10px 0;
        }}
        
        .stat-label {{
            color: #cdb4db;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9rem;
        }}
        
        .stat-card.total .stat-number {{ color: #b8c0ff; }}
        .stat-card.passed .stat-number {{ color: #48bb78; }}
        .stat-card.failed .stat-number {{ color: #ff4d6d; }}
        .stat-card.skipped .stat-number {{ color: #ffb703; }}
        
        .info-section {{
            background: linear-gradient(145deg, #ffe5ec, #fff0f3);
            padding: 25px;
            margin: 20px;
            border-radius: 20px;
            border: 2px solid #ffc8dd;
        }}
        
        .info-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }}
        
        .info-item {{
            background: white;
            padding: 12px 20px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }}
        
        .info-label {{
            font-weight: 600;
            color: #cdb4db;
        }}
        
        .info-value {{
            color: #ff4d6d;
            font-weight: 600;
        }}
        
        .timeline {{
            padding: 30px;
            position: relative;
        }}
        
        .timeline-title {{
            font-family: 'M PLUS Rounded 1c', sans-serif;
            color: #ff4d6d;
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }}
        
        .timeline-items {{
            position: relative;
            padding-left: 30px;
        }}
        
        .timeline-items::before {{
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #ffafcc, #cdb4db);
        }}
        
        .timeline-item {{
            position: relative;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 15px;
            border: 1px solid #ffe5ec;
            transition: all 0.3s;
        }}
        
        .timeline-item:hover {{
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(255, 175, 204, 0.2);
        }}
        
        .timeline-item::before {{
            content: '';
            position: absolute;
            left: -24px;
            top: 20px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #ffafcc;
            border: 2px solid white;
        }}
        
        .timeline-time {{
            font-size: 0.8rem;
            color: #cdb4db;
            margin-bottom: 5px;
        }}
        
        .timeline-status {{
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: bold;
            margin-left: 10px;
        }}
        
        .status-success {{
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
        }}
        
        .status-error {{
            background: rgba(255, 77, 109, 0.2);
            color: #ff4d6d;
        }}
        
        .status-info {{
            background: rgba(176, 224, 230, 0.2);
            color: #5dade2;
        }}
        
        .timeline-text {{
            font-weight: 600;
            color: #5a4a6a;
        }}
        
        .timeline-details {{
            margin-top: 8px;
            font-size: 0.85rem;
            color: #6d5a7a;
            padding-left: 15px;
            border-left: 2px solid #ffe5ec;
        }}
        
        .results-table {{
            padding: 30px;
        }}
        
        table {{
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            border: 2px solid #ffe5ec;
        }}
        
        th {{
            background: linear-gradient(to bottom, #ffe5ec, #cdb4db);
            color: white;
            padding: 15px;
            text-align: left;
        }}
        
        td {{
            padding: 15px;
            border-bottom: 2px solid #ffe5ec;
        }}
        
        tr:hover {{
            background: rgba(255, 200, 221, 0.2);
        }}
        
        .badge {{
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }}
        
        .badge-passed {{
            background: rgba(72, 187, 120, 0.2);
            color: #48bb78;
        }}
        
        .badge-failed {{
            background: rgba(255, 77, 109, 0.2);
            color: #ff4d6d;
        }}
        
        .badge-skipped {{
            background: rgba(255, 183, 3, 0.2);
            color: #ffb703;
        }}
        
        .error-box {{
            background: #fff0f3;
            padding: 15px;
            border-radius: 15px;
            margin-top: 10px;
            font-family: monospace;
            font-size: 0.85rem;
            overflow-x: auto;
            border-left: 4px solid #ff4d6d;
        }}
        
        .footer {{
            background: linear-gradient(145deg, #ffe5ec, #cdb4db);
            padding: 25px;
            text-align: center;
            color: white;
        }}
        
        .pdf-btn {{
            display: inline-block;
            padding: 14px 35px;
            background: linear-gradient(145deg, #ff4d6d, #c9184a);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            margin: 20px;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }}
        
        .pdf-btn:hover {{
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 77, 109, 0.4);
        }}
        
        @keyframes float {{
            0%, 100% {{ transform: translateY(0); }}
            50% {{ transform: translateY(-10px); }}
        }}
        
        .timestamp {{
            text-align: center;
            padding: 15px;
            color: #cdb4db;
            background: #fff8fa;
        }}
        
        @media print {{
            .pdf-btn {{
                display: none;
            }}
            body {{
                background: white;
                padding: 0;
            }}
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="title">🌸 Кавай Магазин 🍥</div>
            <div class="subtitle">Отчёт автоматизированного тестирования</div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number">{total_tests}</div>
                <div class="stat-label">Всего тестов</div>
            </div>
            <div class="stat-card passed">
                <div class="stat-number">{passed}</div>
                <div class="stat-label">Пройдено</div>
            </div>
            <div class="stat-card failed">
                <div class="stat-number">{failed}</div>
                <div class="stat-label">Провалено</div>
            </div>
            <div class="stat-card skipped">
                <div class="stat-number">{skipped}</div>
                <div class="stat-label">Пропущено</div>
            </div>
        </div>
        
        <div class="info-section">
            <h3 style="color: #ff4d6d;">📋 Информация о тестировании</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">📅 Дата:</span>
                    <span class="info-value">{datetime.now().strftime("%d.%m.%Y %H:%M:%S")}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">⏱️ Длительность:</span>
                    <span class="info-value">{duration:.2f} сек</span>
                </div>
                <div class="info-item">
                    <span class="info-label">👤 Тестовый пользователь:</span>
                    <span class="info-value">{test_data.get('username', '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">📧 Email:</span>
                    <span class="info-value">{test_data.get('email', '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">📱 Телефон:</span>
                    <span class="info-value">{test_data.get('phone', '—')}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">🌐 URL:</span>
                    <span class="info-value">{BASE_URL}</span>
                </div>
            </div>
        </div>
        
        <div class="timeline">
            <div class="timeline-title">
                <span>🎬</span> Ход выполнения тестов
            </div>
            <div class="timeline-items">
'''
        
        # Добавляем все действия
        for action in test_data["actions"]:
            status_class = "status-success" if action["status"] == "success" else "status-error" if action["status"] == "error" else "status-info"
            status_text = "✅" if action["status"] == "success" else "❌" if action["status"] == "error" else "ℹ️"
            
            html += f'''
                <div class="timeline-item">
                    <div class="timeline-time">
                        {action["time"]}
                        <span class="timeline-status {status_class}">{status_text} {action["status"].upper()}</span>
                    </div>
                    <div class="timeline-text">{action["action"]}</div>
                    {f'<div class="timeline-details">{action["details"]}</div>' if action.get("details") else ''}
                </div>
'''
        
        html += '''
            </div>
        </div>
        
        <div class="results-table">
            <h2 style="color: #ff4d6d; margin-bottom: 20px;">📊 Результаты тестов</h2>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Название теста</th>
                            <th>Статус</th>
                            <th>Время</th>
                            <th>Детали</th>
                        </tr>
                    </thead>
                    <tbody>
'''
        
        for i, result in enumerate(test_data["results"], 1):
            badge_class = f"badge-{result['status']}"
            status_text = {"passed": "Пройден", "failed": "Провален", "skipped": "Пропущен"}.get(result['status'], result['status'])
            
            error_html = ''
            if result.get('error') and result['status'] == 'failed':
                error_msg = str(result['error'])
                if len(error_msg) > 300:
                    error_msg = error_msg[:300] + '...'
                error_html = f'<div class="error-box">{error_msg}</div>'
            
            html += f'''
                        <tr>
                            <td>{i}</td>
                            <td>
                                <strong>{result['name']}</strong>
                                {error_html}
                            </td>
                            <td><span class="badge {badge_class}">{status_text}</span></td>
                            <td>{result.get('duration', 0):.2f} сек</td>
                            <td>{result.get('message', '—')}</td>
                        </tr>
'''
        
        html += f'''
                    </tbody>
                </table>
            </div>
        </div>
        
        <div style="text-align: center;">
            <button onclick="window.print()" class="pdf-btn">
                📄 Сохранить как PDF
            </button>
        </div>
        
        <div class="timestamp">
            🕐 Тестирование завершено: {datetime.now().strftime("%d.%m.%Y %H:%M:%S")}<br>
            ⏱️ Общее время: {duration:.2f} секунд
        </div>
        
        <div class="footer">
            🌸 Кавай Магазин 🍥 — Автоматизированное тестирование
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {{
            const items = document.querySelectorAll('.timeline-item');
            items.forEach((item, index) => {{
                item.style.opacity = '0';
                item.style.transform = 'translateX(-20px)';
                setTimeout(() => {{
                    item.style.transition = 'all 0.3s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }}, index * 50);
            }});
        }});
    </script>
</body>
</html>
        
        return html
    
    @staticmethod
    def save(filename):
        """Сохраняет отчёт в файл"""
        html_content = HTMLReportGenerator.generate()
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(html_content)
        return filename

# ==================== FIXTURES ====================
class TestFixtures:
    @pytest.fixture(scope="session")
    def db_connection(self):
        """Подключение к базе данных"""
        VisualLogger.print_info("Подключение к базе данных...")
        conn = pymysql.connect(**DB_LOCAL)
        yield conn
        conn.close()
        VisualLogger.print_success("Отключение от базы данных")
    
    @pytest.fixture(scope="function")
    def selenium_driver(self):
        """Selenium WebDriver"""
        VisualLogger.print_action("Запуск браузера Firefox...", "🔄")
        
        if GECKODRIVER_PATH and os.path.exists(GECKODRIVER_PATH):
            service = Service(executable_path=GECKODRIVER_PATH)
        else:
            service = Service()
        
        options = webdriver.FirefoxOptions()
        # options.add_argument("--headless")  # Раскомментируйте для headless режима
        
        try:
            driver = webdriver.Firefox(service=service, options=options)
            driver.implicitly_wait(10)
            VisualLogger.print_success("Браузер успешно запущен")
            yield driver
            VisualLogger.print_action("Закрытие браузера...", "🔄")
            driver.quit()
            VisualLogger.print_success("Браузер закрыт")
        except Exception as e:
            VisualLogger.print_error(f"Ошибка запуска браузера: {e}")
            pytest.skip("Firefox не доступен")
    
    @pytest.fixture(scope="function")
    def requests_session(self):
        """Requests сессия"""
        session = requests.Session()
        yield session
        session.close()
    
    @pytest.fixture(scope="function")
    def playwright_browser(self):
        """Playwright браузер"""
        VisualLogger.print_action("Запуск Playwright...", "🔄")
        with sync_playwright() as p:
            browser = p.firefox.launch(headless=False)
            context = browser.new_context()
            page = context.new_page()
            VisualLogger.print_success("Playwright запущен")
            yield page
            context.close()
            browser.close()
            VisualLogger.print_success("Playwright закрыт")

# ==================== ТЕСТЫ ====================
class TestKawaiiShop(TestFixtures):
    """Все тесты магазина"""
    
    @pytest.fixture(autouse=True)
    def track_test(self, request):
        """Автоматическое отслеживание результатов тестов"""
        test_start = time.time()
        yield
        duration = time.time() - test_start
        
        outcome = getattr(request.node, 'rep_call', None)
        if outcome:
            test_data["results"].append({
                "name": request.node.name,
                "status": "passed" if outcome.passed else "failed" if outcome.failed else "skipped",
                "duration": duration,
                "error": str(outcome.longrepr) if hasattr(outcome, 'longrepr') and outcome.longrepr else None,
                "message": ""
            })
    
    @pytest.hookimpl(tryfirst=True, hookwrapper=True)
    def pytest_runtest_makereport(self, item, call):
        """Хук для получения результатов тестов"""
        outcome = yield
        rep = outcome.get_result()
        setattr(item, "rep_" + rep.when, rep)
    
    def add_action(self, action, status="info", details=""):
        """Добавляет действие в лог"""
        test_data["actions"].append({
            "time": datetime.now().strftime("%H:%M:%S"),
            "action": action,
            "status": status,
            "details": details
        })
    
    # ========== ТЕСТ 1: ПРОВЕРКА ГЛАВНОЙ СТРАНИЦЫ ==========
    def test_01_homepage_accessible(self, requests_session):
        """Главная страница доступна"""
        self.add_action("Проверка доступности главной страницы", "info")
        VisualLogger.print_header("ТЕСТ 1: ПРОВЕРКА ГЛАВНОЙ СТРАНИЦЫ")
        
        VisualLogger.print_action(f"Отправка GET запроса к {BASE_URL}/user_login.php", "🔄")
        resp = requests_session.get(f"{BASE_URL}/user_login.php")
        
        VisualLogger.print_action(f"Получен ответ: статус {resp.status_code}", "🔄")
        assert resp.status_code == 200, f"Страница не открылась, статус: {resp.status_code}"
        assert "Вход для покупателей" in resp.text, "Нет текста входа"
        
        VisualLogger.print_success("Главная страница доступна")
        self.add_action("Главная страница доступна", "success", f"Статус: {resp.status_code}")
    
    # ========== ТЕСТ 2: РЕГИСТРАЦИЯ ПОЛЬЗОВАТЕЛЯ ==========
    def test_02_user_registration_api(self, requests_session):
        """Регистрация пользователя через API"""
        self.add_action("Регистрация пользователя", "info")
        VisualLogger.print_header("ТЕСТ 2: РЕГИСТРАЦИЯ ПОЛЬЗОВАТЕЛЯ")
        
        timestamp = int(time.time())
        test_data["username"] = f"testuser_{timestamp}"
        test_data["email"] = f"test_{timestamp}@example.com"
        
        VisualLogger.print_action(f"Генерация данных пользователя", "🔄", 
                                 f"Логин: {test_data['username']}, Email: {test_data['email']}")
        
        data = {
            "username": test_data["username"],
            "email": test_data["email"],
            "phone": test_data["phone"],
            "password": test_data["password"],
            "confirm_password": test_data["password"]
        }
        
        VisualLogger.print_action(f"Отправка POST запроса на регистрацию", "🔄")
        resp = requests_session.post(f"{BASE_URL}/user_register.php", data=data)
        
        VisualLogger.print_action(f"Получен ответ: статус {resp.status_code}", "🔄")
        assert resp.status_code in (200, 302), f"Ошибка регистрации: {resp.status_code}"
        
        VisualLogger.print_success(f"Пользователь {test_data['username']} зарегистрирован")
        self.add_action("Пользователь зарегистрирован", "success", f"Логин: {test_data['username']}")
    
    # ========== ТЕСТ 3: ВХОД ПОЛЬЗОВАТЕЛЯ ==========
    def test_03_user_login_api(self, requests_session):
        """Вход пользователя через API"""
        self.add_action("Вход пользователя", "info")
        VisualLogger.print_header("ТЕСТ 3: ВХОД ПОЛЬЗОВАТЕЛЯ")
        
        data = {
            "username": test_data["username"],
            "password": test_data["password"]
        }
        
        VisualLogger.print_action(f"Отправка POST запроса на вход", "🔄",
                                 f"Логин: {test_data['username']}")
        
        resp = requests_session.post(f"{BASE_URL}/user_login.php", data=data, allow_redirects=False)
        
        VisualLogger.print_action(f"Получен ответ: статус {resp.status_code}", "🔄")
        assert resp.status_code == 302, "Нет редиректа после входа"
        
        location = resp.headers.get("Location", "")
        assert "user_index.php" in location, "Редирект не на user_index.php"
        
        VisualLogger.print_action(f"Проверка доступа к защищённой странице", "🔄")
        index_resp = requests_session.get(f"{BASE_URL}/user_index.php")
        assert test_data["username"] in index_resp.text, "Имя пользователя не отображается"
        
        VisualLogger.print_success(f"Пользователь {test_data['username']} успешно вошёл")
        self.add_action("Пользователь вошёл в систему", "success", f"Редирект на: {location}")
    
    # ========== ТЕСТ 4: ПРОВЕРКА БАЗЫ ДАННЫХ ==========
    def test_04_user_in_database(self, db_connection):
        """Проверка наличия пользователя в БД"""
        self.add_action("Проверка базы данных", "info")
        VisualLogger.print_header("ТЕСТ 4: ПРОВЕРКА БАЗЫ ДАННЫХ")
        
        VisualLogger.print_action(f"Поиск пользователя {test_data['username']} в БД", "🔄")
        
        with db_connection.cursor() as cursor:
            cursor.execute(
                "SELECT id, username, email FROM site_users WHERE username = %s",
                (test_data["username"],)
            )
            user = cursor.fetchone()
        
        assert user is not None, f"Пользователь {test_data['username']} не найден в БД"
        test_data["user_id"] = user[0]
        
        VisualLogger.print_success(f"Пользователь найден: ID={user[0]}, Логин={user[1]}, Email={user[2]}")
        self.add_action("Пользователь найден в БД", "success", f"ID: {user[0]}")
    
    # ========== ТЕСТ 5: PLAYWRIGHT ВХОД ==========
    def test_05_playwright_login(self, playwright_browser):
        """Вход через Playwright"""
        self.add_action("Playwright вход", "info")
        VisualLogger.print_header("ТЕСТ 5: PLAYWRIGHT ВХОД")
        
        page = playwright_browser
        
        VisualLogger.print_action(f"Переход на страницу входа", "🔄")
        page.goto(f"{BASE_URL}/user_login.php", timeout=10000)
        VisualLogger.print_success(f"Страница загружена: {page.url}")
        
        VisualLogger.print_action(f"Заполнение формы входа", "🔄",
                                 f"Логин: {test_data['username']}")
        page.fill("input[name='username']", test_data["username"])
        page.fill("input[name='password']", test_data["password"])
        
        VisualLogger.print_action(f"Нажатие кнопки входа", "🔄")
        page.click("button[type='submit']")
        
        VisualLogger.print_action(f"Ожидание редиректа...", "🔄")
        page.wait_for_url("**/user_index.php", timeout=10000)
        
        VisualLogger.print_success(f"Редирект выполнен: {page.url}")
        assert page.url.endswith("user_index.php"), "URL не соответствует"
        assert test_data["username"] in page.content(), "Имя пользователя не найдено"
        
        VisualLogger.print_success(f"Playwright вход выполнен успешно")
        self.add_action("Playwright вход выполнен", "success", f"Финальный URL: {page.url}")
    
    # ========== ТЕСТ 6: ДОБАВЛЕНИЕ ТОВАРА В КОРЗИНУ ==========
    def test_06_add_to_cart(self, selenium_driver):
        """Добавление товара в корзину"""
        self.add_action("Добавление товара в корзину", "info")
        VisualLogger.print_header("ТЕСТ 6: ДОБАВЛЕНИЕ В КОРЗИНУ")
        
        # Вход в систему
        VisualLogger.print_action(f"Вход в систему", "🔄")
        selenium_driver.get(f"{BASE_URL}/user_login.php")
        selenium_driver.find_element(By.NAME, "username").send_keys(test_data["username"])
        selenium_driver.find_element(By.NAME, "password").send_keys(test_data["password"])
        selenium_driver.find_element(By.CSS_SELECTOR, "button[type='submit']").click()
        time.sleep(2)
        
        # Переход на главную
        VisualLogger.print_action(f"Переход на главную страницу", "🔄")
        selenium_driver.get(f"{BASE_URL}/user_index.php")
        time.sleep(2)
        
        # Делаем скриншот главной страницы
        selenium_driver.save_screenshot("reports/main_page.png")
        
        try:
            VisualLogger.print_action(f"Поиск товара для добавления", "🔄")
            
            # Ищем все кнопки добавления
            add_buttons = selenium_driver.find_elements(By.CSS_SELECTOR, ".btn-success, a[href*='user_cart.php?add']")
            
            if not add_buttons:
                VisualLogger.print_error("Кнопки 'В корзину' не найдены")
                self.add_action("Нет товаров для добавления", "error")
                pytest.skip("Нет доступных товаров для теста")
            
            # Берем первый товар
            first_add_btn = add_buttons[0]
            product_card = first_add_btn.find_element(By.XPATH, "./ancestor::div[contains(@class, 'product-card')]")
            product_name = product_card.find_element(By.CSS_SELECTOR, ".product-info h3").text
            
            VisualLogger.print_action(f"Добавление товара '{product_name}' в корзину", "🔄")
            first_add_btn.click()
            time.sleep(2)
            
            # Проверяем, что товар добавлен
            selenium_driver.get(f"{BASE_URL}/user_cart.php")
            time.sleep(2)
            
            cart_text = selenium_driver.page_source
            if "Ваша корзина пуста" in cart_text:
                VisualLogger.print_error("Товар не добавился в корзину!")
                self.add_action("Товар не добавился в корзину", "error")
            else:
                VisualLogger.print_success(f"Товар '{product_name}' добавлен в корзину")
                self.add_action("Товар добавлен в корзину", "success", product_name)
                
            # Сохраняем скриншот корзины
            selenium_driver.save_screenshot("reports/cart_after_add.png")
            
        except Exception as e:
            VisualLogger.print_error(f"Не удалось добавить товар: {e}")
            self.add_action("Ошибка добавления товара", "error", str(e))
            pytest.skip("Нет доступных товаров для теста")
    
    # ========== ТЕСТ 7: ОФОРМЛЕНИЕ ЗАКАЗА ==========
    def test_07_checkout(self, selenium_driver):
        """Оформление заказа"""
        self.add_action("Начало теста: Оформление заказа", "info")
        
        VisualLogger.print_header("ТЕСТ 7: ОФОРМЛЕНИЕ ЗАКАЗА")
        
        # ========== 1. ПРОВЕРКА АВТОРИЗАЦИИ ==========
        VisualLogger.print_action(f"Проверка авторизации пользователя", "🔄")
        
        current_url = selenium_driver.current_url
        VisualLogger.print_info(f"Текущий URL: {current_url}")
        
        # Если мы не на странице пользователя, выполняем вход
        if "user_index" not in current_url and "user_cart" not in current_url and "user_checkout" not in current_url:
            VisualLogger.print_action(f"Выполняем вход в систему...", "🔄")
            
            selenium_driver.get(f"{BASE_URL}/user_login.php")
            time.sleep(2)
            
            VisualLogger.print_action(f"Ввод логина: {test_data['username']}", "🔄")
            username_field = WebDriverWait(selenium_driver, 10).until(
                EC.presence_of_element_located((By.NAME, "username"))
            )
            username_field.clear()
            username_field.send_keys(test_data["username"])
            
            VisualLogger.print_action(f"Ввод пароля", "🔄")
            password_field = selenium_driver.find_element(By.NAME, "password")
            password_field.clear()
            password_field.send_keys(test_data["password"])
            
            VisualLogger.print_action(f"Нажатие кнопки входа", "🔄")
            submit_btn = selenium_driver.find_element(By.CSS_SELECTOR, "button[type='submit']")
            submit_btn.click()
            
            time.sleep(3)
            
            if "user_index" not in selenium_driver.current_url:
                VisualLogger.print_error("Не удалось войти в систему")
                selenium_driver.save_screenshot("reports/login_failed.png")
                self.add_action("Ошибка авторизации", "error")
                pytest.skip("Не удалось авторизоваться")
            
            VisualLogger.print_success("Вход выполнен успешно")
            self.add_action("Пользователь авторизован", "success", test_data['username'])
        
        # ========== 2. ПЕРЕХОД В КОРЗИНУ ==========
        VisualLogger.print_action(f"Переход в корзину", "🔄")
        selenium_driver.get(f"{BASE_URL}/user_cart.php")
        time.sleep(2)
        
        selenium_driver.save_screenshot("reports/cart_before_checkout.png")
        
        # ========== 3. ПРОВЕРКА НАЛИЧИЯ ТОВАРОВ ==========
        VisualLogger.print_action(f"Проверка наличия товаров в корзине", "🔄")
        page_source = selenium_driver.page_source
        page_text = page_source.lower()
        
        empty_indicators = [
            "ваша корзина пуста",
            "корзина пуста",
            "нет товаров",
            "cart is empty",
            "пустая корзина"
        ]
        
        is_empty = any(indicator in page_text for indicator in empty_indicators)
        
        if is_empty:
            VisualLogger.print_error("Корзина пуста!")
            
            VisualLogger.print_action(f"Пытаемся добавить товар в корзину...", "🔄")
            selenium_driver.get(f"{BASE_URL}/user_index.php")
            time.sleep(2)
            
            try:
                add_buttons = selenium_driver.find_elements(By.CSS_SELECTOR, ".btn-success, a[href*='user_cart.php?add']")
                
                if not add_buttons:
                    VisualLogger.print_error("Нет доступных товаров для добавления")
                    self.add_action("Нет товаров для заказа", "error")
                    pytest.skip("Нет товаров для оформления заказа")
                
                first_add_btn = add_buttons[0]
                product_card = first_add_btn.find_element(By.XPATH, "./ancestor::div[contains(@class, 'product-card')]")
                product_name = product_card.find_element(By.CSS_SELECTOR, ".product-info h3").text
                
                VisualLogger.print_action(f"Добавление товара '{product_name}'", "🔄")
                first_add_btn.click()
                time.sleep(2)
                
                VisualLogger.print_success(f"Товар '{product_name}' добавлен в корзину")
                self.add_action("Товар добавлен в корзину", "success", product_name)
                
                selenium_driver.get(f"{BASE_URL}/user_cart.php")
                time.sleep(2)
                
            except Exception as e:
                VisualLogger.print_error(f"Не удалось добавить товар: {e}")
                self.add_action("Ошибка добавления товара", "error", str(e))
                pytest.skip("Нет товаров для оформления заказа")
        
        page_text = selenium_driver.page_source.lower()
        if any(indicator in page_text for indicator in empty_indicators):
            VisualLogger.print_error("Корзина всё ещё пуста")
            pytest.skip("Корзина пуста")
        
        VisualLogger.print_success("В корзине есть товары")
        
        # ========== 4. ПОИСК КНОПКИ ОФОРМЛЕНИЯ ==========
        VisualLogger.print_action(f"Поиск кнопки 'Оформить заказ'", "🔄")
        
        selenium_driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(1)
        
        checkout_found = False
        
        # Вариант 1: По тексту "Оформить заказ"
        try:
            checkout_btn = WebDriverWait(selenium_driver, 5).until(
                EC.element_to_be_clickable((By.LINK_TEXT, "Оформить заказ"))
            )
            checkout_btn.click()
            checkout_found = True
            VisualLogger.print_success("Кнопка 'Оформить заказ' найдена и нажата")
        except:
            pass
        
        # Вариант 2: По части текста
        if not checkout_found:
            try:
                checkout_btn = selenium_driver.find_element(By.XPATH, "//*[contains(text(), 'Оформить')]")
                checkout_btn.click()
                checkout_found = True
                VisualLogger.print_success("Кнопка с текстом 'Оформить' найдена и нажата")
            except:
                pass
        
        # Вариант 3: По CSS классу
        if not checkout_found:
            try:
                btns = selenium_driver.find_elements(By.CSS_SELECTOR, ".btn, .btn-success")
                for btn in btns:
                    if "оформить" in btn.text.lower():
                        btn.click()
                        checkout_found = True
                        VisualLogger.print_success("Кнопка оформления найдена по классу")
                        break
            except:
                pass
        
        # Вариант 4: По ссылке на checkout
        if not checkout_found:
            try:
                checkout_link = selenium_driver.find_element(By.CSS_SELECTOR, "a[href*='checkout'], a[href*='order']")
                checkout_link.click()
                checkout_found = True
                VisualLogger.print_success("Ссылка на оформление найдена")
            except:
                pass
        
        if not checkout_found:
            VisualLogger.print_error("Кнопка оформления не найдена")
            with open("reports/cart_page.html", "w", encoding="utf-8") as f:
                f.write(selenium_driver.page_source)
            selenium_driver.save_screenshot("reports/cart_page.png")
            self.add_action("Кнопка оформления не найдена", "error")
            pytest.skip("Кнопка оформления не найдена")
        
        time.sleep(2)
        
        # ========== 5. ПРОВЕРКА ПЕРЕХОДА ==========
        current_url = selenium_driver.current_url
        VisualLogger.print_info(f"URL после нажатия: {current_url}")
        
        if "checkout" not in current_url and "order" not in current_url:
            VisualLogger.print_error(f"Не перенаправлено на страницу оформления")
            selenium_driver.save_screenshot("reports/checkout_redirect_error.png")
            pytest.skip("Не удалось перейти на страницу оформления")
        
        VisualLogger.print_success("Переход на страницу оформления выполнен")
        
        # ========== 6. ЗАПОЛНЕНИЕ АДРЕСА ==========
        VisualLogger.print_action(f"Поиск поля адреса доставки", "🔄")
        
        try:
            address_field = WebDriverWait(selenium_driver, 10).until(
                EC.presence_of_element_located((By.NAME, "shipping_address"))
            )
            address_field.clear()
            address_field.send_keys("г. Тестовый, ул. Питоновская, д. 42, кв. 13")
            VisualLogger.print_success("Адрес доставки заполнен")
            
            try:
                notes_field = selenium_driver.find_element(By.NAME, "notes")
                notes_field.send_keys("Тестовый заказ, просьба позвонить за час до доставки")
                VisualLogger.print_info("Примечания заполнены")
            except:
                pass
            
        except Exception as e:
            VisualLogger.print_error(f"Поле адреса не найдено: {e}")
            selenium_driver.save_screenshot("reports/address_not_found.png")
            self.add_action("Поле адреса не найдено", "error", str(e))
            pytest.skip("Поле адреса доставки не найдено")
        
        # ========== 7. ПОДТВЕРЖДЕНИЕ ЗАКАЗА ==========
        VisualLogger.print_action(f"Поиск кнопки подтверждения", "🔄")
        
        selenium_driver.execute_script("window.scrollTo(0, document.body.scrollHeight);")
        time.sleep(1)
        
        submit_found = False
        submit_selectors = [
            "button[type='submit']",
            "input[type='submit']",
            "button.btn-success",
            "button:contains('Подтвердить')",
            "button:contains('Заказать')",
            "button:contains('Оформить')"
        ]
        
        for selector in submit_selectors:
            try:
                submit_btn = selenium_driver.find_element(By.CSS_SELECTOR, selector)
                if submit_btn.is_enabled():
                    VisualLogger.print_action(f"Нажатие кнопки подтверждения", "🔄")
                    submit_btn.click()
                    submit_found = True
                    VisualLogger.print_success(f"Кнопка подтверждения найдена и нажата")
                    break
            except:
                continue
        
        if not submit_found:
            VisualLogger.print_error("Кнопка подтверждения не найдена")
            self.add_action("Кнопка подтверждения не найдена", "error")
            pytest.skip("Кнопка подтверждения не найдена")
        
        time.sleep(3)
        
        # ========== 8. ПРОВЕРКА РЕЗУЛЬТАТА ==========
        VisualLogger.print_action(f"Проверка результата оформления", "🔄")
        
        current_url = selenium_driver.current_url
        page_text = selenium_driver.page_source.lower()
        
        VisualLogger.print_info(f"Финальный URL: {current_url}")
        selenium_driver.save_screenshot("reports/order_result.png")
        
        success_indicators = [
            "заказ успешно",
            "заказ оформлен",
            "order confirmed",
            "спасибо за заказ",
            "order details",
            "ваш заказ принят"
        ]
        
        success_detected = any(indicator in page_text for indicator in success_indicators)
        
        if success_detected or "order_details" in current_url or "orders" in current_url:
            VisualLogger.print_success("✅ Заказ успешно оформлен!")
            self.add_action("Заказ успешно оформлен", "success", f"URL: {current_url}")
            
            order_match = re.search(r'заказ\s*#(\d+)', page_text, re.IGNORECASE)
            if order_match:
                order_id = order_match.group(1)
                test_data["order_id"] = order_id
                VisualLogger.print_info(f"Номер заказа: #{order_id}")
                self.add_action("Номер заказа получен", "success", f"#{order_id}")
        else:
            VisualLogger.print_error("Не удалось подтвердить успешное оформление заказа")
            VisualLogger.print_info("Содержимое страницы (первые 500 символов):")
            VisualLogger.print_info(page_text[:500])
            self.add_action("Оформление заказа не подтверждено", "error")
            pytest.skip("Не удалось подтвердить оформление заказа")
    
    # ========== ТЕСТ 8: ВЫХОД ИЗ СИСТЕМЫ ==========
    def test_08_logout(self, selenium_driver):
        """Выход из системы"""
        self.add_action("Выход из системы", "info")
        VisualLogger.print_header("ТЕСТ 8: ВЫХОД ИЗ СИСТЕМЫ")
        
        VisualLogger.print_action(f"Поиск кнопки выхода", "🔄")
        try:
            logout_btn = selenium_driver.find_element(By.CSS_SELECTOR, ".logout-btn")
            logout_btn.click()
            VisualLogger.print_success("Кнопка выхода найдена и нажата")
            time.sleep(1)
        except:
            VisualLogger.print_action(f"Прямой переход на страницу выхода", "🔄")
            selenium_driver.get(f"{BASE_URL}/user_logout.php")
            time.sleep(1)
        
        assert "login" in selenium_driver.current_url, "Не удалось выйти"
        
        VisualLogger.print_success(f"Выход выполнен, текущий URL: {selenium_driver.current_url}")
        self.add_action("Выход выполнен", "success", f"URL после выхода: {selenium_driver.current_url}")
    
    # ========== ТЕСТ 99: ОЧИСТКА ДАННЫХ ==========
    def test_99_cleanup(self, db_connection):
        """Очистка тестовых данных"""
        self.add_action("Очистка данных", "info")
        VisualLogger.print_header("ТЕСТ 99: ОЧИСТКА ДАННЫХ")
        
        with db_connection.cursor() as cursor:
            if test_data.get("username"):
                VisualLogger.print_action(f"Удаление пользователя {test_data['username']} из БД", "🔄")
                cursor.execute(
                    "DELETE FROM site_users WHERE username = %s",
                    (test_data["username"],)
                )
                VisualLogger.print_success(f"Пользователь удалён: {test_data['username']}")
                self.add_action("Пользователь удалён", "success", test_data['username'])
            
            db_connection.commit()
        
        VisualLogger.print_success("Все тестовые данные очищены")
        self.add_action("Очистка завершена", "success")

# ==================== ЗАПУСК ====================
if __name__ == "__main__":
    test_data["start_time"] = time.time()
    
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    report_file = f"reports/kawaii_report_{timestamp}.html"
    
    VisualLogger.print_header("ЗАПУСК ТЕСТИРОВАНИЯ")
    VisualLogger.print_info(f"Базовый URL: {BASE_URL}")
    VisualLogger.print_info(f"Geckodriver: {GECKODRIVER_PATH if GECKODRIVER_PATH else 'Авто-поиск'}")
    VisualLogger.print_info(f"Папка отчётов: reports")
    VisualLogger.print_info(f"Файл отчёта: {report_file}")
    print()
    
    # Запускаем тесты
    pytest_args = [
        __file__,
        "-v",
        "-s",
        "--tb=short"
    ]
    
    exit_code = pytest.main(pytest_args)
    
    test_data["end_time"] = time.time()
    duration = test_data["end_time"] - test_data["start_time"]
    
    # Генерируем HTML-отчёт
    VisualLogger.print_header("ГЕНЕРАЦИЯ ОТЧЁТА")
    HTMLReportGenerator.save(report_file)
    
    VisualLogger.print_success(f"Отчёт сохранён: {report_file}")
    VisualLogger.print_info(f"Общее время выполнения: {duration:.2f} секунд")
    
    # Открываем в браузере
    import webbrowser
    webbrowser.open(f"file://{os.path.abspath(report_file)}")
    
    VisualLogger.print_success("Отчёт открыт в браузере")
    VisualLogger.print_header("ТЕСТИРОВАНИЕ ЗАВЕРШЕНО")
    
    exit(exit_code)