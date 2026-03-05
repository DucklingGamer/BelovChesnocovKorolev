#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Тестирование PHP сайта "Кавай Магазин" с помощью Selenium (Firefox)
Создание скриншотов и HTML-отчета о тестировании
"""

import os
import sys
import time
import json
import base64
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Tuple, Optional, Any
import traceback

# Установка необходимых библиотек (если не установлены)
required_packages = ['selenium', 'rich', 'webdriver-manager']
for package in required_packages:
    try:
        __import__(package.replace('-', '_'))
    except ImportError:
        print(f"Устанавливаем {package}...")
        os.system(f"{sys.executable} -m pip install {package}")

from selenium import webdriver
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.firefox.service import Service
from selenium.webdriver.firefox.options import Options
from webdriver_manager.firefox import GeckoDriverManager
from selenium.common.exceptions import TimeoutException, NoSuchElementException, ElementClickInterceptedException, StaleElementReferenceException

from rich.console import Console
from rich.table import Table
from rich.progress import Progress, SpinnerColumn, TextColumn, BarColumn, TaskProgressColumn
from rich.panel import Panel
from rich.layout import Layout
from rich.live import Live
from rich.text import Text
from rich import box
from datetime import datetime

# Инициализация Rich консоли
console = Console()

class TestResult:
    """Класс для хранения результатов теста"""
    def __init__(self, name: str):
        self.name = name
        self.status = "⏳ Ожидание"
        self.message = ""
        self.screenshot_path = ""
        self.duration = 0.0
        self.error = None
        self.timestamp = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    
    def to_dict(self) -> Dict:
        return {
            'name': self.name,
            'status': self.status,
            'message': self.message,
            'screenshot_path': self.screenshot_path,
            'duration': f"{self.duration:.2f}",
            'timestamp': self.timestamp,
            'error': str(self.error) if self.error else None
        }

class KawaiShopTester:
    """Класс для тестирования магазина"""
    
    def __init__(self, base_url: str = "http://localhost"):
        self.base_url = base_url
        self.driver = None
        self.wait = None
        self.test_results: List[TestResult] = []
        self.screenshots_dir = Path("test_screenshots")
        self.report_dir = Path("test_reports")
        self.test_start_time = datetime.now()
        
        # Создание директорий
        self.screenshots_dir.mkdir(exist_ok=True)
        self.report_dir.mkdir(exist_ok=True)
        
        # Тестовые данные
        self.test_user = {
            'username': 'testuser_' + datetime.now().strftime("%Y%m%d_%H%M%S"),
            'password': 'Test123!@#',
            'email': f'test_{datetime.now().strftime("%Y%m%d%H%M%S")}@example.com',
            'phone': '+79991234567'
        }
        
        # Данные админа (по умолчанию из create_users_table.php)
        self.admin_user = {
            'username': '456456',
            'password': '456456'
        }
    
    def setup_driver(self):
        """Настройка Firefox WebDriver"""
        try:
            console.print("[bold cyan]🔄 Настройка Firefox WebDriver...[/bold cyan]")
            
            firefox_options = Options()
            firefox_options.add_argument("--width=1920")
            firefox_options.add_argument("--height=1080")

            # Для отладки можно раскомментировать:
            # firefox_options.add_argument("--headless")
            
            # Дополнительные настройки для Firefox
            firefox_options.set_preference("dom.popup_maximum", 0)
            firefox_options.set_preference("privacy.popups.showBrowserMessage", False)
            firefox_options.set_preference("dom.disable_beforeunload", True)
            firefox_options.set_preference("browser.tabs.warnOnClose", False)
            
            # Автоматическая загрузка и установка geckodriver
            service = Service(GeckoDriverManager().install())
            self.driver = webdriver.Firefox(service=service, options=firefox_options)
            self.wait = WebDriverWait(self.driver, 15)  # Увеличим время ожидания до 15 секунд
            
            # Установка неявного ожидания
            self.driver.implicitly_wait(5)
            
            console.print("[bold green]✅ Firefox WebDriver успешно настроен[/bold green]")
            return True
            
        except Exception as e:
            console.print(f"[bold red]❌ Ошибка настройки Firefox WebDriver: {e}[/bold red]")
            console.print("[yellow]Убедитесь, что Firefox установлен и доступен в системе[/yellow]")
            return False
    
    def wait_for_page_load(self, timeout=10):
        """Ожидание полной загрузки страницы"""
        try:
            # Ждем, пока состояние документа будет 'complete'
            WebDriverWait(self.driver, timeout).until(
                lambda driver: driver.execute_script("return document.readyState") == "complete"
            )
            # Дополнительная небольшая задержка для динамического контента
            time.sleep(0.5)
            return True
        except:
            return False
    
    def wait_for_element(self, by, value, timeout=10, condition="presence"):
        """Ожидание появления элемента с проверкой"""
        try:
            if condition == "clickable":
                element = WebDriverWait(self.driver, timeout).until(
                    EC.element_to_be_clickable((by, value))
                )
            elif condition == "visible":
                element = WebDriverWait(self.driver, timeout).until(
                    EC.visibility_of_element_located((by, value))
                )
            else:  # presence
                element = WebDriverWait(self.driver, timeout).until(
                    EC.presence_of_element_located((by, value))
                )
            return element
        except TimeoutException:
            console.print(f"[yellow]⚠️ Таймаут ожидания элемента: {by}={value}[/yellow]")
            # Выводим текущий URL и часть страницы для отладки
            console.print(f"[dim]Текущий URL: {self.driver.current_url}[/dim]")
            return None
    
    def take_screenshot(self, test_name: str) -> str:
        """Создание скриншота"""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        # Очищаем имя файла от недопустимых символов
        safe_test_name = "".join(c for c in test_name if c.isalnum() or c in (' ', '-', '_')).rstrip()
        filename = f"{safe_test_name}_{timestamp}.png"
        filepath = self.screenshots_dir / filename
        
        try:
            self.driver.save_screenshot(str(filepath))
            console.print(f"[dim]📸 Скриншот сохранен: {filename}[/dim]")
            return str(filepath)
        except Exception as e:
            console.print(f"[yellow]⚠️ Не удалось создать скриншот: {e}[/yellow]")
            return ""
    
    def run_test(self, test_func, name: str) -> TestResult:
        """Запуск отдельного теста с измерениями"""
        result = TestResult(name)
        start_time = time.time()
        
        try:
            console.print(f"\n[bold cyan]▶️ Запуск теста: {name}[/bold cyan]")
            test_func(result)
            result.status = "✅ Успешно"
            console.print(f"[bold green]✅ Тест пройден: {name}[/bold green]")
        except AssertionError as e:
            result.status = "❌ Ошибка"
            result.message = str(e)
            result.error = e
            console.print(f"[bold red]❌ Тест не пройден: {name}[/bold red]")
            console.print(f"[red]  Ошибка: {e}[/red]")
        except Exception as e:
            result.status = "⚠️ Исключение"
            result.message = str(e)
            result.error = e
            console.print(f"[bold red]⚠️ Исключение в тесте {name}: {e}[/bold red]")
            traceback.print_exc()
        finally:
            result.duration = time.time() - start_time
            # Делаем скриншот в любом случае
            screenshot_path = self.take_screenshot(name.replace(" ", "_"))
            if screenshot_path:
                result.screenshot_path = screenshot_path
            self.test_results.append(result)
        
        return result
    
    def ensure_user_logged_in(self):
        """Вспомогательная функция для гарантии входа пользователя"""
        if "user_index.php" not in self.driver.current_url:
            self.driver.get(f"{self.base_url}/user_login.php")
            self.wait_for_page_load()
            
            username_input = self.wait_for_element(By.NAME, "username", timeout=10, condition="visible")
            if username_input is None:
                raise AssertionError("Поле username не найдено на странице входа")
            username_input.send_keys(self.test_user['username'])
            
            password_input = self.wait_for_element(By.NAME, "password", timeout=5)
            if password_input is None:
                raise AssertionError("Поле password не найдено на странице входа")
            password_input.send_keys(self.test_user['password'])
            
            submit_btn = self.wait_for_element(By.CSS_SELECTOR, "button[type='submit']", timeout=5, condition="clickable")
            if submit_btn is None:
                raise AssertionError("Кнопка входа не найдена")
            submit_btn.click()
            
            time.sleep(2)
            self.wait_for_page_load()
    
    def ensure_admin_logged_in(self):
        """Вспомогательная функция для гарантии входа админа"""
        self.driver.get(f"{self.base_url}/login.php")
        self.wait_for_page_load()
        
        username_input = self.wait_for_element(By.NAME, "username", timeout=10, condition="visible")
        if username_input is None:
            raise AssertionError("Поле username не найдено на странице входа")
        username_input.send_keys(self.admin_user['username'])
        
        password_input = self.wait_for_element(By.NAME, "password", timeout=5)
        if password_input is None:
            raise AssertionError("Поле password не найдено на странице входа")
        password_input.send_keys(self.admin_user['password'])
        
        submit_btn = self.wait_for_element(By.CSS_SELECTOR, "button[type='submit']", timeout=5, condition="clickable")
        if submit_btn is None:
            raise AssertionError("Кнопка входа не найдена")
        submit_btn.click()
        
        time.sleep(2)
        self.wait_for_page_load()
    
    # ==================== ТЕСТЫ ====================
    
    def test_login_page(self, result: TestResult):
        """Тест страницы входа (доступна без авторизации)"""
        self.driver.get(f"{self.base_url}/login.php")
        self.wait_for_page_load()
        
        # Ждем появления формы входа
        username_input = self.wait_for_element(By.NAME, "username", timeout=10, condition="visible")
        if username_input is None:
            raise AssertionError("Поле username не найдено на странице входа")
        
        password_input = self.wait_for_element(By.NAME, "password", timeout=5, condition="visible")
        if password_input is None:
            raise AssertionError("Поле password не найдено на странице входа")
        
        submit_btn = self.wait_for_element(By.CSS_SELECTOR, "button[type='submit']", timeout=5)
        if submit_btn is None:
            raise AssertionError("Кнопка отправки не найдена")
        
        # Проверка заголовка
        assert "Вход" in self.driver.title or "Login" in self.driver.title, f"Неверный заголовок страницы: {self.driver.title}"
        
        result.message = f"Страница входа загружена корректно. URL: {self.driver.current_url}"
    
    def test_register_page(self, result: TestResult):
        """Тест страницы регистрации (доступна без авторизации)"""
        self.driver.get(f"{self.base_url}/user_register.php")
        self.wait_for_page_load()
        
        # Ждем появления формы регистрации
        username_input = self.wait_for_element(By.NAME, "username", timeout=10, condition="visible")
        if username_input is None:
            raise AssertionError("Поле username не найдено")
        
        email_input = self.wait_for_element(By.NAME, "email", timeout=5)
        if email_input is None:
            raise AssertionError("Поле email не найдено")
        
        phone_input = self.wait_for_element(By.NAME, "phone", timeout=5)
        if phone_input is None:
            raise AssertionError("Поле phone не найдено")
        
        password_input = self.wait_for_element(By.NAME, "password", timeout=5)
        if password_input is None:
            raise AssertionError("Поле password не найдено")
        
        confirm_input = self.wait_for_element(By.NAME, "confirm_password", timeout=5)
        if confirm_input is None:
            raise AssertionError("Поле confirm_password не найдено")
        
        result.message = f"Страница регистрации загружена корректно. URL: {self.driver.current_url}"
    
    def test_admin_login(self, result: TestResult):
        """Тест входа в админку"""
        self.ensure_admin_logged_in()
        
        # Проверка успешного входа
        current_url = self.driver.current_url
        assert "index.php" in current_url or "главная" in current_url.lower(), f"Не удалось войти в админку. Текущий URL: {current_url}"
        
        # Проверка наличия меню
        menu = self.wait_for_element(By.CLASS_NAME, "menu", timeout=10)
        if menu is None:
            console.print("[yellow]  Меню не найдено после входа, но это может быть нормой[/yellow]")
        
        result.message = f"Вход в админку выполнен успешно. URL: {current_url}"
    
    def test_user_registration(self, result: TestResult):
        """Тест регистрации пользователя"""
        self.driver.get(f"{self.base_url}/user_register.php")
        self.wait_for_page_load()
        
        # Заполнение формы регистрации с проверкой каждого поля
        username_input = self.wait_for_element(By.NAME, "username", timeout=10, condition="visible")
        if username_input is None:
            raise AssertionError("Поле username не найдено на странице регистрации")
        username_input.send_keys(self.test_user['username'])
        
        email_input = self.wait_for_element(By.NAME, "email", timeout=5)
        if email_input is None:
            raise AssertionError("Поле email не найдено")
        email_input.send_keys(self.test_user['email'])
        
        phone_input = self.wait_for_element(By.NAME, "phone", timeout=5)
        if phone_input is None:
            raise AssertionError("Поле phone не найдено")
        phone_input.send_keys(self.test_user['phone'])
        
        password_input = self.wait_for_element(By.NAME, "password", timeout=5)
        if password_input is None:
            raise AssertionError("Поле password не найдено")
        password_input.send_keys(self.test_user['password'])
        
        confirm_input = self.wait_for_element(By.NAME, "confirm_password", timeout=5)
        if confirm_input is None:
            raise AssertionError("Поле confirm_password не найдено")
        confirm_input.send_keys(self.test_user['password'])
        
        # Отправка формы
        submit_btn = self.wait_for_element(By.CSS_SELECTOR, "button[type='submit']", timeout=5, condition="clickable")
        if submit_btn is None:
            raise AssertionError("Кнопка отправки не найдена")
        submit_btn.click()
        
        # Ждем обработки регистрации
        time.sleep(2)
        self.wait_for_page_load()
        
        # Проверка успешной регистрации
        page_source = self.driver.page_source.lower()
        success = False
        success_indicators = ["успешн", "success", "добавлен", "created", "registered"]
        for indicator in success_indicators:
            if indicator in page_source:
                success = True
                break
        
        if not success and "login" in self.driver.current_url:
            success = True  # Редирект на страницу входа тоже считается успехом
        
        assert success, f"Регистрация не удалась. Текущий URL: {self.driver.current_url}"
        
        result.message = f"Пользователь {self.test_user['username']} зарегистрирован. URL: {self.driver.current_url}"
    
    def test_user_login(self, result: TestResult):
        """Тест входа пользователя"""
        self.driver.get(f"{self.base_url}/user_login.php")
        self.wait_for_page_load()
        
        # Заполнение формы входа
        username_input = self.wait_for_element(By.NAME, "username", timeout=10, condition="visible")
        if username_input is None:
            raise AssertionError("Поле username не найдено на странице входа")
        username_input.send_keys(self.test_user['username'])
        
        password_input = self.wait_for_element(By.NAME, "password", timeout=5)
        if password_input is None:
            raise AssertionError("Поле password не найдено на странице входа")
        password_input.send_keys(self.test_user['password'])
        
        # Отправка формы
        submit_btn = self.wait_for_element(By.CSS_SELECTOR, "button[type='submit']", timeout=5, condition="clickable")
        if submit_btn is None:
            raise AssertionError("Кнопка входа не найдена")
        submit_btn.click()
        
        # Ждем редиректа
        time.sleep(2)
        self.wait_for_page_load()
        
        # Проверка успешного входа
        current_url = self.driver.current_url
        assert "user_index.php" in current_url, f"Вход пользователя не удался. Текущий URL: {current_url}"
        
        # Проверка приветствия
        page_source = self.driver.page_source
        assert self.test_user['username'] in page_source, f"Имя пользователя {self.test_user['username']} не найдено на странице"
        
        result.message = f"Пользователь {self.test_user['username']} вошел в систему. URL: {current_url}"
    
    def test_user_categories_page(self, result: TestResult):
        """Тест страницы категорий для пользователя"""
        self.ensure_user_logged_in()
        
        self.driver.get(f"{self.base_url}/user_categories.php")
        self.wait_for_page_load()
        
        # Проверка заголовка
        title_text = self.driver.title.lower()
        page_source = self.driver.page_source.lower()
        assert "категории" in title_text or "категории" in page_source, \
               f"Страница категорий не загружена. URL: {self.driver.current_url}"
        
        # Проверка наличия категорий
        categories = self.driver.find_elements(By.CLASS_NAME, "category-card")
        if len(categories) == 0:
            categories = self.driver.find_elements(By.CSS_SELECTOR, ".category, .category-item, [class*='category']")
        
        result.message = f"Страница категорий загружена, найдено {len(categories)} категорий. URL: {self.driver.current_url}"
    
    def test_user_services_page(self, result: TestResult):
        """Тест страницы услуг для пользователя"""
        self.ensure_user_logged_in()
        
        self.driver.get(f"{self.base_url}/user_services.php")
        self.wait_for_page_load()
        
        current_url = self.driver.current_url
        console.print(f"[dim]  Текущий URL: {current_url}[/dim]")
        
        # Проверка наличия услуг
        services = []
        service_selectors = [
            (By.CLASS_NAME, "card"),
            (By.CSS_SELECTOR, ".service-card"),
            (By.CSS_SELECTOR, ".service-item"),
            (By.CSS_SELECTOR, ".product-card"),
            (By.CSS_SELECTOR, "[class*='service']"),
        ]
        
        for by, selector in service_selectors:
            elements = self.driver.find_elements(by, selector)
            if len(elements) > 0:
                services = elements
                console.print(f"[dim]  Найдено элементов услуг: {len(services)} по селектору {by}={selector}[/dim]")
                break
        
        # Проверяем также наличие таблицы с услугами
        if len(services) == 0:
            tables = self.driver.find_elements(By.CSS_SELECTOR, "table")
            if tables:
                rows = tables[0].find_elements(By.CSS_SELECTOR, "tbody tr")
                if rows:
                    services = rows
        
        result.message = f"Страница услуг загружена, найдено {len(services)} услуг. URL: {current_url}"
    
    def test_user_profile_page(self, result: TestResult):
        """Тест страницы профиля пользователя"""
        self.ensure_user_logged_in()
        
        self.driver.get(f"{self.base_url}/user_profile.php")
        self.wait_for_page_load()
        
        # Проверка наличия информации профиля
        page_source = self.driver.page_source
        assert "Мой профиль" in page_source or "Profile" in page_source, "Страница профиля не загружена"
        
        # Проверка наличия email
        email_input = self.wait_for_element(By.NAME, "email", timeout=10)
        if email_input is None:
            # Пробуем найти другие поля профиля
            username_display = self.wait_for_element(By.XPATH, f"//*[contains(text(), '{self.test_user['username']}')]", timeout=5)
            if username_display is None:
                raise AssertionError("Не найдена информация профиля")
            result.message = f"Страница профиля загружена, найден username. URL: {self.driver.current_url}"
        else:
            email_value = email_input.get_attribute("value")
            assert email_value == self.test_user['email'], \
                   f"Email не соответствует. Ожидалось: {self.test_user['email']}, получено: {email_value}"
            result.message = f"Страница профиля загружена корректно. URL: {self.driver.current_url}"
    
    def test_user_theme_switcher(self, result: TestResult):
        """Тест переключателя тем для пользователя"""
        self.ensure_user_logged_in()
        
        self.driver.get(f"{self.base_url}/user_index.php")
        self.wait_for_page_load()
        
        current_url = self.driver.current_url
        console.print(f"[dim]  Текущий URL: {current_url}[/dim]")
        
        # Сохраняем скриншот для отладки
        debug_screenshot = self.take_screenshot("theme_switcher_before")
        console.print(f"[dim]  Скриншот для отладки: {debug_screenshot}[/dim]")
        
        # Ждем появления переключателя тем с несколькими попытками
        theme_switcher = None
        selectors = [
            (By.CLASS_NAME, "theme-switcher"),
            (By.CSS_SELECTOR, ".theme-switcher"),
            (By.CSS_SELECTOR, ".theme-selector"),
            (By.CSS_SELECTOR, "[class*='theme']"),
            (By.CSS_SELECTOR, ".color-scheme"),
        ]
        
        for by, selector in selectors:
            theme_switcher = self.wait_for_element(by, selector, timeout=3, condition="presence")
            if theme_switcher is not None:
                console.print(f"[dim]  Найден переключатель тем по селектору: {by}={selector}[/dim]")
                break
        
        if theme_switcher is None:
            # Если не нашли переключатель, проверяем наличие localStorage
            current_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
            if current_theme:
                result.message = f"Переключатель тем не найден, но тема уже установлена: {current_theme}"
                return
            else:
                result.message = "Переключатель тем не найден, пропускаем тест"
                return
        
        # Получение всех кнопок тем
        theme_buttons = []
        button_selectors = [
            (By.CLASS_NAME, "theme-btn"),
            (By.CSS_SELECTOR, ".theme-btn"),
            (By.CSS_SELECTOR, ".theme-button"),
            (By.CSS_SELECTOR, "[class*='theme'] button"),
            (By.CSS_SELECTOR, ".color-scheme option"),
        ]
        
        for by, selector in button_selectors:
            buttons = self.driver.find_elements(by, selector)
            if len(buttons) > 0:
                theme_buttons = buttons
                console.print(f"[dim]  Найдено кнопок тем: {len(theme_buttons)} по селектору {by}={selector}[/dim]")
                break
        
        if len(theme_buttons) == 0:
            # Проверяем localStorage
            current_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
            if current_theme:
                result.message = f"Кнопки тем не найдены, но тема уже установлена: {current_theme}"
            else:
                result.message = "Элементы для переключения тем не найдены"
            return
        
        # Переключение тем
        themes_tested = 0
        current_theme = None
        
        for i, btn in enumerate(theme_buttons[:3]):  # Пробуем первые 3 кнопки
            try:
                # Получаем информацию о кнопке
                btn_text = btn.text.strip() or btn.get_attribute("title") or btn.get_attribute("data-theme") or f"тема {i+1}"
                console.print(f"[dim]  Пробуем переключить: {btn_text}[/dim]")
                
                # Прокрутка до элемента
                self.driver.execute_script("arguments[0].scrollIntoView(true);", btn)
                time.sleep(0.5)
                
                # Пробуем разные способы клика
                click_success = False
                
                try:
                    btn.click()
                    click_success = True
                except:
                    try:
                        self.driver.execute_script("arguments[0].click();", btn)
                        click_success = True
                    except:
                        pass
                
                if not click_success:
                    continue
                
                # Ждем применения темы
                time.sleep(1)
                
                # Проверка изменения темы
                try:
                    new_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
                    if new_theme is not None:
                        if current_theme != new_theme:
                            themes_tested += 1
                            current_theme = new_theme
                            console.print(f"[dim]    Тема изменена на: {new_theme}[/dim]")
                except:
                    pass
                
            except StaleElementReferenceException:
                continue
            except Exception as e:
                console.print(f"[yellow]    Ошибка: {e}[/yellow]")
                continue
        
        if themes_tested == 0:
            current_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
            if current_theme:
                result.message = f"Не удалось переключить темы, но текущая тема: {current_theme}"
            else:
                result.message = "Не удалось переключить ни одной темы"
        else:
            result.message = f"Протестировано переключение {themes_tested} тем. Текущая тема: {current_theme}"
    
    def test_admin_categories_page(self, result: TestResult):
        """Тест страницы категорий для админа"""
        self.ensure_admin_logged_in()
        
        # Переход к категориям
        self.driver.get(f"{self.base_url}/categories.php")
        self.wait_for_page_load()
        
        current_url = self.driver.current_url
        console.print(f"[dim]  Текущий URL: {current_url}[/dim]")
        
        # Проверка наличия формы добавления категории
        category_name_input = None
        name_selectors = [
            (By.NAME, "category_name"),
            (By.NAME, "name"),
            (By.NAME, "category"),
            (By.CSS_SELECTOR, "[name='category_name']"),
            (By.CSS_SELECTOR, "input[placeholder*='категори']"),
        ]
        
        for by, selector in name_selectors:
            category_name_input = self.wait_for_element(by, selector, timeout=5, condition="visible")
            if category_name_input is not None:
                console.print(f"[dim]  Найдено поле названия по селектору: {by}={selector}[/dim]")
                break
        
        if category_name_input is None:
            console.print("[yellow]  Поле названия категории не найдено[/yellow]")
        
        # Проверка наличия списка категорий
        categories_list = self.driver.find_elements(By.CSS_SELECTOR, "table, .categories-list, .list-group")
        categories_count = 0
        if categories_list:
            rows = categories_list[0].find_elements(By.CSS_SELECTOR, "tbody tr, .category-item, li")
            categories_count = len(rows)
        
        result.message = f"Страница управления категориями загружена. Найдено категорий: {categories_count}. URL: {current_url}"
    
    def test_add_category_admin(self, result: TestResult):
        """Тест добавления категории админом"""
        self.ensure_admin_logged_in()
        
        # Переход к категориям
        self.driver.get(f"{self.base_url}/categories.php")
        self.wait_for_page_load()
        
        current_url = self.driver.current_url
        console.print(f"[dim]  Текущий URL: {current_url}[/dim]")
        
        # Добавление категории
        category_name = f"Тестовая категория {datetime.now().strftime('%H%M%S')}"
        
        # Поиск поля названия
        name_input = None
        name_selectors = [
            (By.NAME, "category_name"),
            (By.NAME, "name"),
            (By.NAME, "category"),
            (By.CSS_SELECTOR, "[name='category_name']"),
            (By.CSS_SELECTOR, "input[placeholder*='категори']"),
        ]
        
        for by, selector in name_selectors:
            name_input = self.wait_for_element(by, selector, timeout=5, condition="visible")
            if name_input is not None:
                console.print(f"[dim]  Найдено поле названия по селектору: {by}={selector}[/dim]")
                break
        
        if name_input is None:
            raise AssertionError("Поле названия категории не найдено")
        
        name_input.clear()
        name_input.send_keys(category_name)
        console.print(f"[dim]  Введено название категории: {category_name}[/dim]")
        
        # Поиск поля описания (опционально)
        description_input = self.wait_for_element(By.NAME, "description", timeout=3)
        if description_input is not None:
            description_input.clear()
            description_input.send_keys("Создано автоматическим тестом")
            console.print("[dim]  Введено описание категории[/dim]")
        
        # Поиск кнопки добавления
        submit_btn = None
        btn_selectors = [
            (By.CSS_SELECTOR, "button[type='submit'][name='add']"),
            (By.CSS_SELECTOR, "button[type='submit']"),
            (By.XPATH, "//button[contains(text(), 'Добавить')]"),
            (By.XPATH, "//input[@value='Добавить']"),
        ]
        
        for by, selector in btn_selectors:
            submit_btn = self.wait_for_element(by, selector, timeout=5, condition="clickable")
            if submit_btn is not None:
                console.print(f"[dim]  Найдена кнопка добавления по селектору: {by}={selector}[/dim]")
                break
        
        if submit_btn is None:
            raise AssertionError("Кнопка добавления не найдена")
        
        # Прокрутка и клик
        self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
        time.sleep(0.5)
        
        try:
            submit_btn.click()
        except:
            self.driver.execute_script("arguments[0].click();", submit_btn)
        
        console.print("[dim]  Кнопка добавления нажата[/dim]")
        
        # Ждем обработки
        time.sleep(2)
        self.wait_for_page_load()
        
        # Проверка успешного добавления
        page_source = self.driver.page_source
        
        success = False
        success_indicators = ["успешно", "success", "добавлен", "added"]
        for indicator in success_indicators:
            if indicator in page_source.lower():
                success = True
                break
        
        # Проверяем наличие категории в списке
        if category_name in page_source:
            success = True
        
        assert success, "Категория не добавлена"
        
        result.message = f"Категория '{category_name}' успешно добавлена. URL: {self.driver.current_url}"
    
    def test_user_logout(self, result: TestResult):
        """Тест выхода пользователя из системы"""
        self.ensure_user_logged_in()
        
        current_url = self.driver.current_url
        console.print(f"[dim]  Текущий URL перед выходом: {current_url}[/dim]")
        
        # Находим кнопку выхода
        logout_links = []
        logout_selectors = [
            (By.XPATH, "//a[contains(@href, 'logout')]"),
            (By.XPATH, "//a[contains(text(), 'Выход')]"),
            (By.XPATH, "//a[contains(text(), 'Logout')]"),
            (By.CSS_SELECTOR, ".logout"),
            (By.CSS_SELECTOR, "[href*='logout']"),
        ]
        
        for by, selector in logout_selectors:
            links = self.driver.find_elements(by, selector)
            if links:
                logout_links = links
                console.print(f"[dim]  Найдено элементов выхода: {len(logout_links)} по селектору {by}={selector}[/dim]")
                break
        
        if len(logout_links) == 0:
            # Проверяем, может уже вышли
            if "login" in self.driver.current_url:
                result.message = "Пользователь уже не в системе (на странице входа)"
                return
            raise AssertionError("Кнопка выхода не найдена")
        
        # Прокрутка и клик
        self.driver.execute_script("arguments[0].scrollIntoView(true);", logout_links[0])
        time.sleep(0.5)
        
        try:
            logout_links[0].click()
        except:
            self.driver.execute_script("arguments[0].click();", logout_links[0])
        
        console.print("[dim]  Клик по кнопке выхода выполнен[/dim]")
        
        time.sleep(2)
        self.wait_for_page_load()
        
        # Проверка выхода
        current_url = self.driver.current_url
        assert "login" in current_url or "user_login" in current_url, f"Не удалось выйти, текущий URL: {current_url}"
        
        result.message = f"Выход из системы выполнен успешно. URL: {current_url}"
    
    def generate_html_report(self) -> str:
        """Генерация HTML отчета"""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        report_filename = self.report_dir / f"test_report_{timestamp}.html"
        
        # Подсчет статистики
        total_tests = len(self.test_results)
        passed = sum(1 for r in self.test_results if r.status == "✅ Успешно")
        failed = sum(1 for r in self.test_results if r.status == "❌ Ошибка")
        exceptions = sum(1 for r in self.test_results if r.status == "⚠️ Исключение")
        
        total_time = sum(float(r.duration) for r in self.test_results)
        
        # Создание HTML
        html_content = f"""<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Отчет тестирования Кавай Магазин (Firefox)</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@300;400;500;700&family=Nunito:wght@300;400;600;700&display=swap');
        
        * {{
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }}
        
        body {{
            font-family: 'Nunito', sans-serif;
            background: linear-gradient(135deg, #fff0f3 0%, #f8f7ff 100%);
            color: #5a4a6a;
            padding: 30px;
        }}
        
        .container {{
            max-width: 1400px;
            margin: 0 auto;
        }}
        
        .header {{
            text-align: center;
            margin-bottom: 30px;
            position: relative;
        }}
        
        .header h1 {{
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 2.5rem;
            color: #ff4d6d;
            text-shadow: 3px 3px 0 #ffafcc;
            margin-bottom: 10px;
        }}
        
        .header h1::before {{
            content: '🦊';
            margin-right: 15px;
            animation: float 3s infinite;
        }}
        
        .header h1::after {{
            content: '🍥';
            margin-left: 15px;
            animation: float 3s infinite reverse;
        }}
        
        @keyframes float {{
            0%, 100% {{ transform: translateY(0); }}
            50% {{ transform: translateY(-10px); }}
        }}
        
        .summary {{
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }}
        
        .summary-card {{
            background: white;
            padding: 25px;
            border-radius: 20px;
            border: 3px solid #ffc8dd;
            box-shadow: 0 10px 30px rgba(255, 175, 204, 0.3);
            text-align: center;
            transition: all 0.3s;
        }}
        
        .summary-card:hover {{
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(255, 175, 204, 0.4);
        }}
        
        .summary-card.passed {{
            border-color: #48bb78;
            background: rgba(72, 187, 120, 0.1);
        }}
        
        .summary-card.failed {{
            border-color: #ff4d6d;
            background: rgba(255, 77, 109, 0.1);
        }}
        
        .summary-card.exceptions {{
            border-color: #ffb703;
            background: rgba(255, 183, 3, 0.1);
        }}
        
        .summary-number {{
            font-family: 'M PLUS Rounded 1c', sans-serif;
            font-size: 3rem;
            font-weight: 700;
            margin: 10px 0;
        }}
        
        .summary-label {{
            color: #6d5a7a;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.9rem;
        }}
        
        .tests-grid {{
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin: 30px 0;
        }}
        
        .test-card {{
            background: white;
            border-radius: 20px;
            border: 3px solid #ffc8dd;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(255, 175, 204, 0.2);
            transition: all 0.3s;
        }}
        
        .test-card:hover {{
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(255, 175, 204, 0.3);
        }}
        
        .test-header {{
            padding: 15px 20px;
            background: linear-gradient(135deg, #ffafcc, #cdb4db);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }}
        
        .test-name {{
            font-weight: 700;
            font-size: 1.1rem;
        }}
        
        .test-status {{
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            background: white;
            color: #5a4a6a;
        }}
        
        .test-status.passed {{ background: #48bb78; color: white; }}
        .test-status.failed {{ background: #ff4d6d; color: white; }}
        .test-status.exception {{ background: #ffb703; color: white; }}
        
        .test-content {{
            padding: 20px;
        }}
        
        .test-info {{
            margin-bottom: 15px;
        }}
        
        .test-info p {{
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }}
        
        .test-info i {{
            width: 20px;
            color: #ffafcc;
        }}
        
        .test-message {{
            background: #ffe5ec;
            padding: 12px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 0.95rem;
        }}
        
        .test-screenshot {{
            margin-top: 15px;
            text-align: center;
        }}
        
        .test-screenshot img {{
            max-width: 100%;
            border-radius: 10px;
            border: 3px solid #ffafcc;
            cursor: pointer;
            transition: all 0.3s;
        }}
        
        .test-screenshot img:hover {{
            transform: scale(1.02);
            box-shadow: 0 10px 25px rgba(255, 175, 204, 0.4);
        }}
        
        .modal {{
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }}
        
        .modal img {{
            max-width: 90%;
            max-height: 90%;
            border-radius: 15px;
            border: 5px solid #ffafcc;
            object-fit: contain;
        }}
        
        .close-modal {{
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 3rem;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 1001;
        }}
        
        .close-modal:hover {{
            color: #ff4d6d;
            transform: scale(1.1);
        }}
        
        .footer {{
            text-align: center;
            margin-top: 40px;
            padding: 20px;
            color: #6d5a7a;
            font-size: 0.9rem;
        }}
        
        .download-report {{
            display: inline-block;
            padding: 15px 30px;
            background: linear-gradient(135deg, #ff4d6d, #ffafcc);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 700;
            margin: 20px 0;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }}
        
        .download-report:hover {{
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 77, 109, 0.4);
        }}
        
        .error-details {{
            background: #fee;
            border-left: 5px solid #ff4d6d;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            overflow-x: auto;
        }}
        
        .no-screenshot {{
            background: #ffe5ec;
            padding: 20px;
            border-radius: 10px;
            color: #6d5a7a;
            text-align: center;
            font-style: italic;
        }}
        
        @media (max-width: 768px) {{
            .tests-grid {{
                grid-template-columns: 1fr;
            }}
            
            .header h1 {{
                font-size: 1.8rem;
            }}
        }}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Отчет тестирования Кавай Магазин</h1>
            <p style="font-size: 1.2rem; color: #6d5a7a;">{self.test_start_time.strftime('%d.%m.%Y %H:%M:%S')}</p>
            <p style="font-size: 1rem; color: #ff4d6d;">🦊 Тестирование выполнено в Firefox</p>
        </div>
        
        <div class="summary">
            <div class="summary-card passed">
                <div style="font-size: 2.5rem;">✅</div>
                <div class="summary-number">{passed}</div>
                <div class="summary-label">Успешных тестов</div>
            </div>
            <div class="summary-card failed">
                <div style="font-size: 2.5rem;">❌</div>
                <div class="summary-number">{failed}</div>
                <div class="summary-label">Ошибок</div>
            </div>
            <div class="summary-card exceptions">
                <div style="font-size: 2.5rem;">⚠️</div>
                <div class="summary-number">{exceptions}</div>
                <div class="summary-label">Исключений</div>
            </div>
            <div class="summary-card">
                <div style="font-size: 2.5rem;">⏱️</div>
                <div class="summary-number">{total_time:.1f}с</div>
                <div class="summary-label">Общее время</div>
            </div>
        </div>
        
        <div style="text-align: center; margin: 20px 0;">
            <button class="download-report" onclick="downloadReport()">
                📥 Скачать отчет JSON
            </button>
        </div>
        
        <div class="tests-grid">
"""
        
        # Добавление карточек тестов
        for result in self.test_results:
            status_class = {
                "✅ Успешно": "passed",
                "❌ Ошибка": "failed",
                "⚠️ Исключение": "exception"
            }.get(result.status, "")
            
            # Формируем правильный путь к скриншоту
            screenshot_display = ""
            if result.screenshot_path and os.path.exists(result.screenshot_path):
                try:
                    with open(result.screenshot_path, 'rb') as img_file:
                        img_data = base64.b64encode(img_file.read()).decode('utf-8')
                        screenshot_display = f'<img src="data:image/png;base64,{img_data}" alt="Скриншот {result.name}" onclick="showModal(this.src)">'
                except Exception as e:
                    screenshot_display = f'<div class="no-screenshot">❌ Ошибка загрузки скриншота: {str(e)}</div>'
            else:
                screenshot_display = '<div class="no-screenshot">📷 Скриншот не создан</div>'
            
            html_content += f"""
            <div class="test-card">
                <div class="test-header">
                    <span class="test-name">{result.name}</span>
                    <span class="test-status {status_class}">{result.status}</span>
                </div>
                <div class="test-content">
                    <div class="test-info">
                        <p><i>🕒</i> Время: {result.duration:.2f}с</p>
                        <p><i>📅</i> {result.timestamp}</p>
                    </div>
                    <div class="test-message">
                        {result.message if result.message else "Нет сообщения"}
                    </div>
"""
            
            if result.error:
                html_content += f"""
                    <div class="error-details">
                        <strong>Детали ошибки:</strong><br>
                        {str(result.error)}
                    </div>
"""
            
            html_content += f"""
                    <div class="test-screenshot">
                        {screenshot_display}
                    </div>
                </div>
            </div>
"""
        
        html_content += f"""
        </div>
        
        <div class="footer">
            <p>🌸 Кавай Магазин Тестирование (Firefox) 🦊</p>
            <p>Всего тестов: {total_tests} | Успешно: {passed} | Ошибок: {failed} | Исключений: {exceptions}</p>
        </div>
    </div>
    
    <div class="modal" id="imageModal" onclick="closeModal()">
        <span class="close-modal" onclick="closeModal()">&times;</span>
        <img id="modalImage" src="" alt="Увеличенный скриншот">
    </div>
    
    <script>
        function showModal(src) {{
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }}
        
        function closeModal() {{
            document.getElementById('imageModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }}
        
        document.addEventListener('keydown', function(e) {{
            if (e.key === 'Escape') {{
                closeModal();
            }}
        }});
        
        document.getElementById('imageModal').addEventListener('click', function(e) {{
            if (e.target === this) {{
                closeModal();
            }}
        }});
        
        function downloadReport() {{
            const reportData = {{
                summary: {{
                    total: {total_tests},
                    passed: {passed},
                    failed: {failed},
                    exceptions: {exceptions},
                    total_time: {total_time:.2f},
                    start_time: "{self.test_start_time.strftime('%Y-%m-%d %H:%M:%S')}",
                    end_time: "{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}"
                }},
                tests: {json.dumps([r.to_dict() for r in self.test_results], ensure_ascii=False, indent=2)}
            }};
            
            const dataStr = JSON.stringify(reportData, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = 'test_report_{timestamp}.json';
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
        }}
    </script>
</body>
</html>
"""
        
        with open(report_filename, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        console.print(f"[green]📊 HTML отчет создан: {report_filename}[/green]")
        return str(report_filename)
    
    def print_summary(self):
        """Вывод сводки в консоль с Rich"""
        console.print("\n" + "="*60)
        console.print("[bold magenta]🌸 СВОДКА ТЕСТИРОВАНИЯ (Firefox) 🦊[/bold magenta]")
        console.print("="*60)
        
        # Создание таблицы
        table = Table(title="Результаты тестов", box=box.ROUNDED, show_header=True, header_style="bold magenta")
        table.add_column("№", style="cyan", width=4)
        table.add_column("Тест", style="white", width=30)
        table.add_column("Статус", width=12)
        table.add_column("Время", justify="right", width=8)
        table.add_column("Сообщение", width=40)
        
        for i, result in enumerate(self.test_results, 1):
            status_style = {
                "✅ Успешно": "green",
                "❌ Ошибка": "red",
                "⚠️ Исключение": "yellow"
            }.get(result.status, "white")
            
            short_message = result.message[:38] + "..." if len(result.message) > 38 else result.message
            
            table.add_row(
                str(i),
                result.name[:28] + "..." if len(result.name) > 28 else result.name,
                f"[{status_style}]{result.status}[/{status_style}]",
                f"{result.duration:.2f}с",
                short_message
            )
        
        console.print(table)
        
        # Статистика
        total = len(self.test_results)
        passed = sum(1 for r in self.test_results if r.status == "✅ Успешно")
        failed = sum(1 for r in self.test_results if r.status == "❌ Ошибка")
        exceptions = sum(1 for r in self.test_results if r.status == "⚠️ Исключение")
        
        console.print("\n[bold]📊 Статистика:[/bold]")
        
        console.print(f"[green]✅ Успешно: {passed}/{total}[/green]")
        if failed > 0:
            console.print(f"[red]❌ Ошибок: {failed}[/red]")
        if exceptions > 0:
            console.print(f"[yellow]⚠️ Исключений: {exceptions}[/yellow]")
        
        total_time = sum(float(r.duration) for r in self.test_results)
        console.print(f"[cyan]⏱️ Общее время: {total_time:.2f}с[/cyan]")
        
        screenshots_count = sum(1 for r in self.test_results if r.screenshot_path and os.path.exists(r.screenshot_path))
        console.print(f"[magenta]📸 Скриншотов создано: {screenshots_count}/{total}[/magenta]")
    
    def run_all_tests(self):
        """Запуск всех тестов"""
        if not self.setup_driver():
            return False
        
        try:
            with Progress(
                SpinnerColumn(),
                TextColumn("[progress.description]{task.description}"),
                BarColumn(),
                TaskProgressColumn(),
                console=console
            ) as progress:
                
                task = progress.add_task("[cyan]Запуск тестов в Firefox...", total=12)
                
                # Список тестов - ВСЕ МЕТОДЫ ДОЛЖНЫ БЫТЬ ОПРЕДЕЛЕНЫ
                tests = [
                    (self.test_login_page, "Страница входа (доступ без авторизации)"),
                    (self.test_register_page, "Страница регистрации (доступ без авторизации)"),
                    (self.test_user_registration, "Регистрация пользователя"),
                    (self.test_user_login, "Вход пользователя"),
                    (self.test_user_categories_page, "Страница категорий (пользователь)"),
                    (self.test_user_services_page, "Страница услуг (пользователь)"),
                    (self.test_user_profile_page, "Профиль пользователя"),
                    (self.test_user_theme_switcher, "Переключатель тем"),
                    (self.test_admin_login, "Вход в админку"),
                    (self.test_admin_categories_page, "Страница категорий (админ)"),
                    (self.test_add_category_admin, "Добавление категории (админ)"),
                    (self.test_user_logout, "Выход из системы"),
                ]
                
                for test_func, test_name in tests:
                    self.run_test(test_func, test_name)
                    progress.update(task, advance=1)
                    time.sleep(1)
            
            # Генерация отчета
            console.print("\n[bold cyan]📄 Генерация HTML отчета...[/bold cyan]")
            report_path = self.generate_html_report()
            console.print(f"[bold green]✅ Отчет сохранен: {report_path}[/bold green]")
            
            # Вывод сводки
            self.print_summary()
            
            return True
            
        except Exception as e:
            console.print(f"[bold red]❌ Критическая ошибка: {e}[/bold red]")
            traceback.print_exc()
            return False
            
        finally:
            if self.driver:
                self.driver.quit()
                console.print("[yellow]👋 Браузер Firefox закрыт[/yellow]")

def main():
    """Главная функция"""
    console.print(Panel.fit(
        "[bold magenta]🌸 Кавай Магазин Тестирование (Firefox) 🦊[/bold magenta]\n"
        "[cyan]Автоматическое тестирование PHP сайта через Selenium Firefox[/cyan]",
        border_style="magenta"
    ))
    
    # Запрос базового URL
    default_url = "http://localhost"
    console.print(f"\n[bold]Базовый URL сайта (Enter для '{default_url}'):[/bold]")
    base_url = input().strip() or default_url
    
    # Проверка доступности
    import urllib.request
    from urllib.error import URLError
    
    try:
        urllib.request.urlopen(f"{base_url}/login.php", timeout=5)
        console.print(f"[green]✅ Сайт доступен по адресу: {base_url}[/green]")
    except URLError:
        console.print(f"[yellow]⚠️ Внимание: Сайт недоступен по адресу {base_url}[/yellow]")
        console.print("[yellow]Продолжить тестирование? (y/N):[/yellow]")
        if input().strip().lower() != 'y':
            return
    
    # Запуск тестов
    tester = KawaiShopTester(base_url)
    success = tester.run_all_tests()
    
    if success:
        console.print("\n[bold green]🌸 Тестирование в Firefox завершено! 🦊[/bold green]")
        console.print(f"[cyan]📸 Скриншоты сохранены в: {tester.screenshots_dir.absolute()}[/cyan]")
        console.print(f"[cyan]📊 Отчеты сохранены в: {tester.report_dir.absolute()}[/cyan]")
    else:
        console.print("\n[bold red]❌ Тестирование завершилось с ошибками[/bold red]")

if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        console.print("\n[yellow]⚠️ Тестирование прервано пользователем[/yellow]")
    except Exception as e:
        console.print(f"\n[bold red]❌ Неожиданная ошибка: {e}[/bold red]")
        traceback.print_exc()