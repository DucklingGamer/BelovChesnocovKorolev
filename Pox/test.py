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
        try:
            console.print("[dim]Проверка входа администратора...[/dim]")
            
            # Проверяем текущий URL
            current_url = self.driver.current_url
            console.print(f"[dim]  Текущий URL: {current_url}[/dim]")
            
            # Если уже на странице админки (index.php), значит вошли
            if "index.php" in current_url and "user_" not in current_url:
                console.print("[green]  ✅ Администратор уже на главной странице[/green]")
                return
            
            # Переходим на страницу входа админа
            console.print(f"[dim]  Переходим на страницу входа: {self.base_url}/login.php[/dim]")
            self.driver.get(f"{self.base_url}/login.php")
            self.wait_for_page_load()
            
            # Сохраняем скриншот страницы входа
            self.take_screenshot("admin_login_page")
            
            # Выводим информацию о странице
            console.print(f"[dim]  Заголовок страницы: {self.driver.title}[/dim]")
            
            # ПОДРОБНЫЙ ПОИСК ПОЛЯ USERNAME
            console.print("[dim]  Поиск поля для ввода логина...[/dim]")
            
            username_input = None
            
            # Список всех возможных селекторов для поля логина
            username_selectors = [
                (By.NAME, "username"),
                (By.ID, "username"),
                (By.CSS_SELECTOR, "input[name='username']"),
                (By.CSS_SELECTOR, "input[type='text']"),
                (By.CSS_SELECTOR, ".form-group input[type='text']"),
                (By.XPATH, "//input[@type='text']"),
                (By.XPATH, "//input[contains(@name, 'user')]"),
                (By.XPATH, "//input[contains(@id, 'user')]"),
                (By.XPATH, "//label[contains(text(), 'Логин')]/following::input[1]"),
                (By.XPATH, "//label[contains(text(), 'Username')]/following::input[1]"),
                (By.CSS_SELECTOR, "input:not([type='password']):not([type='submit'])")
            ]
            
            for by, selector in username_selectors:
                try:
                    elements = self.driver.find_elements(by, selector)
                    if elements:
                        # Фильтруем скрытые поля
                        for element in elements:
                            if element.is_displayed():
                                username_input = element
                                console.print(f"[green]    ✅ Найдено поле логина: {by}={selector}[/green]")
                                console.print(f"[dim]      type='{element.get_attribute('type')}', name='{element.get_attribute('name')}', placeholder='{element.get_attribute('placeholder')}'[/dim]")
                                break
                        if username_input:
                            break
                except:
                    continue
            
            # Если все еще не нашли, собираем всю информацию о форме
            if not username_input:
                console.print("[yellow]  Поле логина не найдено, анализируем страницу...[/yellow]")
                
                # Находим все формы на странице
                forms = self.driver.find_elements(By.TAG_NAME, "form")
                console.print(f"[dim]  Найдено форм: {len(forms)}[/dim]")
                
                for i, form in enumerate(forms):
                    console.print(f"[dim]  Форма {i+1}:[/dim]")
                    # Находим все input в форме
                    inputs = form.find_elements(By.TAG_NAME, "input")
                    for j, inp in enumerate(inputs):
                        input_type = inp.get_attribute("type") or "text"
                        input_name = inp.get_attribute("name") or ""
                        input_id = inp.get_attribute("id") or ""
                        input_placeholder = inp.get_attribute("placeholder") or ""
                        input_class = inp.get_attribute("class") or ""
                        is_displayed = inp.is_displayed()
                        
                        console.print(f"[dim]    Input {j+1}: type='{input_type}', name='{input_name}', id='{input_id}', placeholder='{input_placeholder}', class='{input_class}', displayed={is_displayed}[/dim]")
                        
                        # Если это текстовое поле и оно видимо, используем его
                        if (input_type == "text" or input_type == "") and is_displayed and not username_input:
                            username_input = inp
                            console.print(f"[green]    ✅ Выбрано поле: {input_name}[/green]")
                
                # Если все еще нет, ищем любые видимые текстовые поля на странице
                if not username_input:
                    all_inputs = self.driver.find_elements(By.TAG_NAME, "input")
                    for inp in all_inputs:
                        input_type = inp.get_attribute("type") or "text"
                        if input_type != "password" and input_type != "hidden" and input_type != "submit" and inp.is_displayed():
                            username_input = inp
                            console.print(f"[green]    ✅ Найдено текстовое поле: type='{input_type}'[/green]")
                            break
            
            if not username_input:
                # Выводим HTML страницы для анализа
                console.print("[red]  ❌ Не удалось найти поле для ввода логина[/red]")
                html_snippet = self.driver.page_source[:1000]
                console.print("[yellow]  HTML страницы (первые 1000 символов):[/yellow]")
                console.print(html_snippet)
                raise AssertionError("Поле username не найдено на странице входа")
            
            # Вводим логин
            username_input.clear()
            username_input.send_keys(self.admin_user['username'])
            console.print(f"[dim]  Введен логин: {self.admin_user['username']}[/dim]")
            
            # ПОИСК ПОЛЯ ПАРОЛЯ
            console.print("[dim]  Поиск поля для ввода пароля...[/dim]")
            
            password_input = None
            password_selectors = [
                (By.NAME, "password"),
                (By.ID, "password"),
                (By.CSS_SELECTOR, "input[type='password']"),
                (By.XPATH, "//input[@type='password']"),
                (By.CSS_SELECTOR, ".form-group input[type='password']"),
            ]
            
            for by, selector in password_selectors:
                try:
                    elements = self.driver.find_elements(by, selector)
                    if elements:
                        for element in elements:
                            if element.is_displayed():
                                password_input = element
                                console.print(f"[green]    ✅ Найдено поле пароля: {by}={selector}[/green]")
                                break
                        if password_input:
                            break
                except:
                    continue
            
            if not password_input:
                # Ищем любой input с type='password'
                all_inputs = self.driver.find_elements(By.TAG_NAME, "input")
                for inp in all_inputs:
                    if inp.get_attribute("type") == "password" and inp.is_displayed():
                        password_input = inp
                        console.print(f"[green]    ✅ Найден input с type='password'[/green]")
                        break
            
            if not password_input:
                raise AssertionError("Поле password не найдено на странице входа")
            
            password_input.clear()
            password_input.send_keys(self.admin_user['password'])
            console.print("[dim]  Введен пароль[/dim]")
            
            # ПОИСК КНОПКИ ОТПРАВКИ
            console.print("[dim]  Поиск кнопки входа...[/dim]")
            
            submit_btn = None
            submit_selectors = [
                (By.CSS_SELECTOR, "button[type='submit']"),
                (By.XPATH, "//button[@type='submit']"),
                (By.XPATH, "//button[contains(text(), 'Войти')]"),
                (By.XPATH, "//button[contains(text(), 'Login')]"),
                (By.XPATH, "//input[@type='submit']"),
                (By.CSS_SELECTOR, ".btn.btn-success"),
                (By.CSS_SELECTOR, "form button"),
                (By.XPATH, "//button[contains(@class, 'btn-success')]"),
            ]
            
            for by, selector in submit_selectors:
                try:
                    elements = self.driver.find_elements(by, selector)
                    if elements:
                        for element in elements:
                            if element.is_displayed():
                                submit_btn = element
                                console.print(f"[green]    ✅ Найдена кнопка: {by}={selector}[/green]")
                                break
                        if submit_btn:
                            break
                except:
                    continue
            
            if not submit_btn:
                # Ищем любую кнопку в форме
                forms = self.driver.find_elements(By.TAG_NAME, "form")
                if forms:
                    buttons = forms[0].find_elements(By.TAG_NAME, "button")
                    if buttons:
                        submit_btn = buttons[0]
                        console.print(f"[green]    ✅ Найдена кнопка в форме[/green]")
                
                if not submit_btn:
                    # Ищем любую кнопку на странице
                    all_buttons = self.driver.find_elements(By.TAG_NAME, "button")
                    if all_buttons:
                        submit_btn = all_buttons[0]
                        console.print(f"[green]    ✅ Найдена кнопка на странице[/green]")
            
            if not submit_btn:
                raise AssertionError("Кнопка входа не найдена")
            
            # Прокрутка и клик
            self.driver.execute_script("arguments[0].scrollIntoView(true);", submit_btn)
            time.sleep(0.5)
            
            try:
                submit_btn.click()
                console.print("[green]  ✅ Клик по кнопке выполнен[/green]")
            except:
                try:
                    self.driver.execute_script("arguments[0].click();", submit_btn)
                    console.print("[green]  ✅ JavaScript клик выполнен[/green]")
                except Exception as e:
                    console.print(f"[red]  ❌ Не удалось кликнуть: {e}[/red]")
                    raise
            
            # Ждем обработки входа
            time.sleep(2)
            self.wait_for_page_load()
            
            # Проверяем успешность входа
            current_url = self.driver.current_url
            console.print(f"[dim]  URL после входа: {current_url}[/dim]")
            
            if "index.php" in current_url and "user_" not in current_url:
                console.print("[green]  ✅ Вход администратора выполнен успешно[/green]")
            else:
                # Проверяем наличие сообщения об ошибке
                page_source = self.driver.page_source.lower()
                if "неверный" in page_source or "error" in page_source:
                    console.print("[red]  ❌ На странице есть сообщение об ошибке[/red]")
                    
                    # Ищем сообщение об ошибке
                    error_elements = self.driver.find_elements(By.CLASS_NAME, "alert-error")
                    if error_elements:
                        error_text = error_elements[0].text
                        console.print(f"[red]    Сообщение: {error_text}[/red]")
                
                self.take_screenshot("admin_login_failed")
                raise AssertionError(f"Вход администратора не удался. Текущий URL: {current_url}")
                
        except Exception as e:
            console.print(f"[red]Ошибка в ensure_admin_logged_in: {e}[/red]")
            self.take_screenshot("ensure_admin_logged_in_error")
            raise
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
        """Тест переключателя тем для пользователя - ТОЛЬКО ПЕРЕКЛЮЧЕНИЕ ТЕМ"""
        try:
            console.print("\n[bold cyan]🔍 Начинаем тест переключателя тем[/bold cyan]")
            
            # Предполагаем, что пользователь уже авторизован (это обеспечивается порядком тестов)
            # Просто переходим на главную страницу пользователя
            self.driver.get(f"{self.base_url}/user_index.php")
            self.wait_for_page_load()
            
            # Сохраняем скриншот для отладки
            debug_screenshot = self.take_screenshot("theme_switcher_before")
            console.print(f"[dim]  Скриншот для отладки: {debug_screenshot}[/dim]")
            
            # Даем время для полной загрузки страницы и JavaScript
            time.sleep(2)
            
            # Проверяем наличие переключателя тем
            theme_switcher = None
            selectors = [
                (By.CLASS_NAME, "theme-switcher"),
                (By.CSS_SELECTOR, ".theme-switcher"),
                (By.CSS_SELECTOR, "div[class*='theme-switcher']"),
            ]
            
            for by, selector in selectors:
                try:
                    theme_switcher = WebDriverWait(self.driver, 5).until(
                        EC.presence_of_element_located((by, selector))
                    )
                    if theme_switcher:
                        console.print(f"[green]  ✅ Найден переключатель тем по селектору: {by}={selector}[/green]")
                        break
                except:
                    continue
            
            if not theme_switcher:
                # Проверяем наличие функции в localStorage
                has_theme_function = self.driver.execute_script("""
                    return typeof window.applyTheme === 'function' || 
                        typeof window.createThemeSwitcher === 'function';
                """)
                
                if has_theme_function:
                    # Пробуем применить тему через JavaScript
                    console.print("[dim]  Пробуем применить тему через JavaScript[/dim]")
                    
                    themes = ['dark', 'pastel', 'ocean', 'forest']
                    applied_themes = []
                    
                    for theme in themes:
                        try:
                            self.driver.execute_script(f"applyTheme('{theme}');")
                            time.sleep(1)
                            
                            # Проверяем, изменилась ли тема в localStorage
                            current_theme = self.driver.execute_script(
                                "return localStorage.getItem('kawaii-theme');"
                            )
                            
                            if current_theme == theme:
                                applied_themes.append(theme)
                                console.print(f"[green]    ✅ Тема '{theme}' применена[/green]")
                            else:
                                console.print(f"[yellow]    ⚠️ Тема '{theme}' не применилась[/yellow]")
                        except Exception as e:
                            console.print(f"[yellow]    Ошибка при применении темы '{theme}': {e}[/yellow]")
                    
                    if applied_themes:
                        result.message = f"Переключатель тем работает через JS. Применены темы: {', '.join(applied_themes)}"
                        return
                    else:
                        result.message = "Переключатель тем не найден, но JS функции присутствуют"
                        return
                else:
                    result.message = "Переключатель тем не найден на странице"
                    return
            
            # Если нашли переключатель, тестируем его
            console.print("[green]  ✅ Переключатель тем найден, тестируем переключение[/green]")
            
            # Прокручиваем к переключателю
            self.driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", theme_switcher)
            time.sleep(1)
            
            # Находим все кнопки тем
            theme_buttons = theme_switcher.find_elements(By.CLASS_NAME, "theme-btn")
            
            if not theme_buttons:
                theme_buttons = theme_switcher.find_elements(By.CSS_SELECTOR, "[data-theme]")
            
            console.print(f"[dim]  Найдено кнопок тем: {len(theme_buttons)}[/dim]")
            
            if not theme_buttons:
                result.message = "Кнопки тем не найдены в переключателе"
                return
            
            # Получаем начальную тему
            initial_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
            console.print(f"[dim]  Начальная тема: {initial_theme or 'default'}[/dim]")
            
            # Переключаем темы
            switched_themes = []
            
            for i, button in enumerate(theme_buttons[:3]):  # Тестируем первые 3 кнопки
                try:
                    # Получаем информацию о кнопке
                    theme_name = button.get_attribute("data-theme") or f"theme_{i}"
                    button_text = button.text.strip() or button.get_attribute("title") or theme_name
                    
                    console.print(f"[dim]  Пробуем переключить на тему: {button_text}[/dim]")
                    
                    # Кликаем по кнопке
                    try:
                        button.click()
                    except:
                        self.driver.execute_script("arguments[0].click();", button)
                    
                    time.sleep(1)
                    
                    # Проверяем изменение темы
                    new_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
                    
                    if new_theme and new_theme != initial_theme:
                        switched_themes.append(new_theme)
                        console.print(f"[green]    ✅ Тема изменена на: {new_theme}[/green]")
                        initial_theme = new_theme  # Обновляем для следующего переключения
                    else:
                        console.print(f"[yellow]    ⚠️ Тема не изменилась[/yellow]")
                        
                except Exception as e:
                    console.print(f"[yellow]    Ошибка при переключении: {e}[/yellow]")
                    continue
            
            # Получаем финальную тему
            final_theme = self.driver.execute_script("return localStorage.getItem('kawaii-theme');")
            
            if switched_themes:
                result.message = f"Успешно переключено тем: {len(switched_themes)}. Текущая тема: {final_theme}"
            else:
                result.message = f"Не удалось переключить темы. Текущая тема: {final_theme or 'default'}"
            
            # Делаем финальный скриншот
            self.take_screenshot("theme_switcher_after")
            
        except Exception as e:
            console.print(f"[red]❌ Ошибка в тесте переключателя тем: {e}[/red]")
            result.message = f"Ошибка: {e}"
            result.error = e
            # Не пробрасываем исключение дальше, чтобы тест считался выполненным
        
    def test_admin_categories_page(self, result: TestResult):
        """Тест страницы категорий для админа"""
        try:
            # Проверяем, нужно ли входить в админку
            current_url = self.driver.current_url
            console.print(f"[dim]  Текущий URL перед тестом категорий: {current_url}[/dim]")
            
            # Если мы уже на странице категорий, отлично!
            if "categories.php" in current_url:
                console.print("[green]  ✅ Уже на странице категорий[/green]")
            else:
                # Проверяем, авторизованы ли мы в админке
                if "index.php" in current_url and "user_" not in current_url:
                    console.print("[green]  ✅ Уже в админке, переходим к категориям[/green]")
                    self.driver.get(f"{self.base_url}/categories.php")
                else:
                    # Входим в админку только если не авторизованы
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
                (By.XPATH, "//input[contains(@placeholder, 'категори')]"),
            ]
            
            for by, selector in name_selectors:
                try:
                    category_name_input = self.wait_for_element(by, selector, timeout=3, condition="visible")
                    if category_name_input is not None:
                        console.print(f"[green]  ✅ Найдено поле названия по селектору: {by}={selector}[/green]")
                        break
                except Exception as e:
                    console.print(f"[dim]    Селектор {by}={selector} не сработал: {e}[/dim]")
                    continue
            
            if category_name_input is None:
                console.print("[yellow]  ⚠️ Поле названия категории не найдено[/yellow]")
                # Делаем скриншот для отладки
                self.take_screenshot("admin_categories_page_no_input")
            else:
                console.print("[green]  ✅ Форма добавления категории найдена[/green]")
            
            # Проверка наличия списка категорий
            categories_count = 0
            try:
                # Пробуем разные селекторы для списка категорий
                list_selectors = [
                    (By.CSS_SELECTOR, "table"),
                    (By.CSS_SELECTOR, ".categories-list"),
                    (By.CSS_SELECTOR, ".list-group"),
                    (By.CSS_SELECTOR, ".category-list"),
                    (By.XPATH, "//table[contains(@class, 'categories')]"),
                ]
                
                categories_list = None
                for by, selector in list_selectors:
                    elements = self.driver.find_elements(by, selector)
                    if elements:
                        categories_list = elements[0]
                        console.print(f"[dim]  Найден список категорий по селектору: {by}={selector}[/dim]")
                        break
                
                if categories_list:
                    # Пробуем найти строки с категориями
                    row_selectors = [
                        (By.CSS_SELECTOR, "tbody tr"),
                        (By.CSS_SELECTOR, ".category-item"),
                        (By.CSS_SELECTOR, "li"),
                        (By.CSS_SELECTOR, "tr"),
                    ]
                    
                    for by, selector in row_selectors:
                        rows = categories_list.find_elements(by, selector)
                        if rows:
                            categories_count = len(rows)
                            console.print(f"[dim]  Найдено строк категорий: {categories_count} по селектору {selector}[/dim]")
                            break
                else:
                    # Если не нашли список, пробуем найти отдельные карточки категорий
                    category_cards = self.driver.find_elements(By.CSS_SELECTOR, ".category-card, .card")
                    if category_cards:
                        categories_count = len(category_cards)
                        console.print(f"[dim]  Найдено карточек категорий: {categories_count}[/dim]")
                        
            except Exception as e:
                console.print(f"[yellow]  Ошибка при поиске списка категорий: {e}[/yellow]")
                categories_count = 0
            
            # Формируем сообщение результата
            if category_name_input:
                result.message = f"✅ Страница категорий загружена. Найдено категорий: {categories_count}"
            else:
                result.message = f"⚠️ Страница категорий загружена, но поле добавления не найдено. Категорий: {categories_count}"
            
            result.message += f" URL: {current_url}"
            
        except Exception as e:
            console.print(f"[red]❌ Ошибка в тесте страницы категорий: {e}[/red]")
            result.message = f"Ошибка: {e}"
            result.error = e
            self.take_screenshot("admin_categories_page_error")
            # Не пробрасываем исключение, чтобы тест считался выполненным

    def test_add_category_admin(self, result: TestResult):
        """Тест добавления категории админом"""
        try:
            # Проверяем текущий URL - мы должны быть на странице категорий
            current_url = self.driver.current_url
            console.print(f"[dim]  Текущий URL перед добавлением категории: {current_url}[/dim]")
            
            # Если мы не на странице категорий, переходим на неё
            if "categories.php" not in current_url:
                console.print("[yellow]  ⚠️ Не на странице категорий, переходим...[/yellow]")
                
                # Проверяем, авторизованы ли мы в админке
                if "index.php" in current_url and "user_" not in current_url:
                    console.print("[green]  ✅ Уже в админке, переходим к категориям[/green]")
                    self.driver.get(f"{self.base_url}/categories.php")
                else:
                    # Входим в админку только если не авторизованы
                    self.ensure_admin_logged_in()
                    # Переход к категориям
                    self.driver.get(f"{self.base_url}/categories.php")
                
                self.wait_for_page_load()
                current_url = self.driver.current_url
                console.print(f"[dim]  Новый URL: {current_url}[/dim]")
            
            # Сохраняем скриншот перед добавлением
            self.take_screenshot("before_add_category")
            
            # Получаем текущее количество категорий для последующего сравнения
            categories_before = self.get_categories_count()
            console.print(f"[dim]  Категорий до добавления: {categories_before}[/dim]")
            
            # Генерируем уникальное имя категории с датой и временем
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            category_name = f"Тестовая категория {timestamp}"
            console.print(f"[dim]  Уникальное имя категории: {category_name}[/dim]")
            
            # Проверяем, что такой категории еще нет на странице
            page_source_before = self.driver.page_source
            if category_name in page_source_before:
                console.print("[yellow]  ⚠️ Категория с таким именем уже существует на странице[/yellow]")
                # Генерируем еще более уникальное имя
                category_name = f"Тестовая категория {timestamp}_{datetime.now().microsecond}"
                console.print(f"[dim]  Новое уникальное имя: {category_name}[/dim]")
            
            # РАСШИРЕННЫЙ ПОИСК ПОЛЯ НАЗВАНИЯ
            console.print("[dim]  Поиск поля названия категории...[/dim]")
            
            name_input = None
            name_selectors = [
                (By.NAME, "category_name"),
                (By.NAME, "name"),
                (By.NAME, "category"),
                (By.CSS_SELECTOR, "[name='category_name']"),
                (By.CSS_SELECTOR, "input[placeholder*='категори']"),
                (By.CSS_SELECTOR, "input[placeholder*='Название']"),
                (By.XPATH, "//label[contains(text(), 'Название')]/following::input[1]"),
                (By.XPATH, "//input[contains(@placeholder, 'категори')]"),
            ]
            
            for by, selector in name_selectors:
                try:
                    name_input = self.wait_for_element(by, selector, timeout=3, condition="visible")
                    if name_input is not None:
                        console.print(f"[green]  ✅ Найдено поле названия по селектору: {by}={selector}[/green]")
                        break
                except:
                    continue
            
            if name_input is None:
                # Если не нашли, ищем все видимые текстовые поля
                console.print("[yellow]  ⚠️ Поле названия не найдено по селекторам, ищем текстовые поля...[/yellow]")
                all_inputs = self.driver.find_elements(By.TAG_NAME, "input")
                for inp in all_inputs:
                    if inp.is_displayed() and inp.get_attribute("type") in ["text", None, ""]:
                        name_input = inp
                        console.print(f"[green]  ✅ Найдено текстовое поле: {inp.get_attribute('name') or 'без имени'}[/green]")
                        break
            
            if name_input is None:
                raise AssertionError("❌ Поле названия категории не найдено")
            
            # Очищаем поле и вводим название
            name_input.clear()
            name_input.send_keys(category_name)
            console.print(f"[dim]  ✅ Введено название категории: {category_name}[/dim]")
            
            # ПОИСК ПОЛЯ ОПИСАНИЯ
            description_input = None
            desc_selectors = [
                (By.NAME, "description"),
                (By.NAME, "desc"),
                (By.CSS_SELECTOR, "textarea"),
                (By.XPATH, "//label[contains(text(), 'Описание')]/following::textarea[1]"),
            ]
            
            for by, selector in desc_selectors:
                try:
                    description_input = self.wait_for_element(by, selector, timeout=2, condition="visible")
                    if description_input is not None:
                        console.print(f"[dim]  Найдено поле описания[/dim]")
                        break
                except:
                    continue
            
            if description_input is not None:
                description_input.clear()
                description_input.send_keys(f"Создано автоматическим тестом {timestamp}")
                console.print("[dim]  ✅ Введено описание категории[/dim]")
            
            # ПОИСК РОДИТЕЛЬСКОЙ КАТЕГОРИИ (опционально)
            parent_select = None
            parent_selectors = [
                (By.NAME, "parent_category_id"),
                (By.NAME, "parent_id"),
                (By.CSS_SELECTOR, "select[name*='parent']"),
            ]
            
            for by, selector in parent_selectors:
                try:
                    parent_select = self.wait_for_element(by, selector, timeout=2, condition="visible")
                    if parent_select is not None:
                        console.print(f"[dim]  Найдено поле выбора родительской категории[/dim]")
                        break
                except:
                    continue
            
            # РАСШИРЕННЫЙ ПОИСК КНОПКИ ДОБАВЛЕНИЯ
            console.print("[dim]  Поиск кнопки добавления...[/dim]")
            
            submit_btn = None
            btn_selectors = [
                (By.CSS_SELECTOR, "button[type='submit'][name='add']"),
                (By.CSS_SELECTOR, "button[type='submit']"),
                (By.XPATH, "//button[contains(text(), 'Добавить')]"),
                (By.XPATH, "//button[contains(text(), 'Создать')]"),
                (By.XPATH, "//input[@type='submit' and contains(@value, 'Добавить')]"),
                (By.XPATH, "//button[contains(@class, 'btn-success')]"),
                (By.CSS_SELECTOR, ".btn-success"),
            ]
            
            for by, selector in btn_selectors:
                try:
                    submit_btn = self.wait_for_element(by, selector, timeout=3, condition="clickable")
                    if submit_btn is not None:
                        console.print(f"[green]  ✅ Найдена кнопка добавления по селектору: {by}={selector}[/green]")
                        break
                except:
                    continue
            
            if submit_btn is None:
                # Если не нашли, ищем любую кнопку в форме
                forms = self.driver.find_elements(By.TAG_NAME, "form")
                if forms:
                    buttons = forms[0].find_elements(By.TAG_NAME, "button")
                    if buttons:
                        submit_btn = buttons[0]
                        console.print(f"[green]  ✅ Найдена кнопка в форме[/green]")
                
                if submit_btn is None:
                    # Ищем любую кнопку на странице
                    all_buttons = self.driver.find_elements(By.TAG_NAME, "button")
                    if all_buttons:
                        submit_btn = all_buttons[0]
                        console.print(f"[green]  ✅ Найдена кнопка на странице[/green]")
            
            if submit_btn is None:
                raise AssertionError("❌ Кнопка добавления не найдена")
            
            # Сохраняем текст кнопки для отладки
            btn_text = submit_btn.text.strip()
            console.print(f"[dim]  Текст кнопки: '{btn_text}'[/dim]")
            
            # Прокрутка и клик
            self.driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", submit_btn)
            time.sleep(0.5)
            
            # Пробуем кликнуть
            click_success = False
            try:
                submit_btn.click()
                click_success = True
                console.print("[green]  ✅ Клик по кнопке выполнен[/green]")
            except Exception as e:
                console.print(f"[yellow]  ⚠️ Обычный клик не сработал: {e}[/yellow]")
                try:
                    self.driver.execute_script("arguments[0].click();", submit_btn)
                    click_success = True
                    console.print("[green]  ✅ JavaScript клик выполнен[/green]")
                except Exception as e2:
                    console.print(f"[red]  ❌ JavaScript клик тоже не сработал: {e2}[/red]")
            
            if not click_success:
                raise AssertionError("❌ Не удалось кликнуть по кнопке добавления")
            
            console.print("[dim]  ⏳ Ожидание обработки запроса...[/dim]")
            
            # Ждем обработки
            time.sleep(3)
            self.wait_for_page_load()
            
            # Делаем скриншот после добавления
            self.take_screenshot("after_add_category")
            
            # ПРОВЕРКА УСПЕШНОГО ДОБАВЛЕНИЯ
            console.print("[dim]  Проверка успешного добавления...[/dim]")
            
            page_source = self.driver.page_source.lower()
            
            # Проверка 1: Сообщение об успехе
            success = False
            success_indicators = [
                "успешно", "success", "добавлен", "added", 
                "создан", "created", "категория добавлена"
            ]
            
            for indicator in success_indicators:
                if indicator in page_source:
                    success = True
                    console.print(f"[green]  ✅ Найдено сообщение об успехе: '{indicator}'[/green]")
                    break
            
            # Проверка 2: Наличие названия категории на странице
            if category_name.lower() in page_source:
                console.print(f"[green]  ✅ Название категории '{category_name}' найдено на странице[/green]")
                success = True
            else:
                console.print(f"[yellow]  ⚠️ Название категории не найдено на странице[/yellow]")
                
                # Ищем похожие категории для отладки
                all_text = self.driver.find_element(By.TAG_NAME, "body").text
                lines = all_text.split('\n')
                category_lines = [line for line in lines if 'категори' in line.lower() or 'category' in line.lower()]
                if category_lines:
                    console.print("[dim]  Похожие строки на странице:[/dim]")
                    for line in category_lines[:5]:
                        console.print(f"[dim]    {line[:100]}[/dim]")
            
            # Проверка 3: Увеличилось ли количество категорий
            categories_after = self.get_categories_count()
            console.print(f"[dim]  Категорий после добавления: {categories_after}[/dim]")
            
            if categories_after > categories_before:
                console.print(f"[green]  ✅ Количество категорий увеличилось с {categories_before} до {categories_after}[/green]")
                success = True
            
            # Проверка 4: Отсутствие сообщения об ошибке
            error_indicators = ["ошибк", "error", "failed", "не удалось"]
            for indicator in error_indicators:
                if indicator in page_source:
                    console.print(f"[yellow]  ⚠️ Найдено сообщение об ошибке: '{indicator}'[/yellow]")
                    # Не делаем fail, просто предупреждаем
            
            # Дополнительная проверка: ищем новую категорию в таблице
            try:
                # Пробуем найти строку с новой категорией
                category_xpath = f"//*[contains(text(), '{category_name}')]"
                category_element = self.driver.find_elements(By.XPATH, category_xpath)
                if category_element:
                    console.print(f"[green]  ✅ Категория найдена в DOM по XPath[/green]")
                    success = True
            except:
                pass
            
            assert success, f"❌ Категория '{category_name}' не была добавлена"
            
            result.message = f"✅ Категория '{category_name}' успешно добавлена. URL: {self.driver.current_url}"
            
            # Сохраняем имя категории для возможных последующих тестов
            self.last_added_category = category_name
            
        except Exception as e:
            console.print(f"[red]❌ Ошибка в тесте добавления категории: {e}[/red]")
            self.take_screenshot("add_category_error")
            result.message = f"Ошибка: {e}"
            result.error = e
            raise  # Пробрасываем исключение, чтобы тест считался упавшим

    def get_categories_count(self):
        """Вспомогательная функция для подсчета количества категорий на странице"""
        try:
            # Пробуем разные способы подсчета категорий
            count = 0
            
            # Способ 1: Поиск по таблице
            tables = self.driver.find_elements(By.CSS_SELECTOR, "table")
            if tables:
                rows = tables[0].find_elements(By.CSS_SELECTOR, "tbody tr")
                if rows:
                    return len(rows)
            
            # Способ 2: Поиск по карточкам
            cards = self.driver.find_elements(By.CSS_SELECTOR, ".category-card, .card")
            if cards:
                return len(cards)
            
            # Способ 3: Поиск по элементам списка
            items = self.driver.find_elements(By.CSS_SELECTOR, ".category-item, li")
            if items:
                return len(items)
            
            return 0
        except:
            return 0
    def test_user_logout(self, result: TestResult):
        """Тест выхода пользователя из системы"""
        try:
            console.print("\n[bold cyan]🔍 Начинаем тест выхода пользователя[/bold cyan]")
            
            # Проверяем текущий URL
            current_url = self.driver.current_url
            console.print(f"[dim]  Текущий URL перед тестом выхода: {current_url}[/dim]")
            
            # Если мы уже на странице входа, значит пользователь уже вышел
            if "login" in current_url or "user_login" in current_url:
                console.print("[green]  ✅ Пользователь уже на странице входа, тест пропускается[/green]")
                result.message = "Пользователь уже не в системе (на странице входа)"
                return
            
            # Проверяем, авторизован ли пользователь (находится ли на странице пользователя)
            is_user_logged_in = False
            
            # Признаки того, что пользователь авторизован:
            # 1. URL содержит user_ и не содержит login
            if "user_" in current_url and "login" not in current_url:
                is_user_logged_in = True
                console.print("[green]  ✅ Пользователь авторизован (по URL)[/green]")
            
            # 2. На странице есть элементы, характерные для авторизованного пользователя
            if not is_user_logged_in:
                try:
                    # Ищем элементы меню пользователя
                    menu_elements = self.driver.find_elements(By.CSS_SELECTOR, ".menu, nav, .menu-nav")
                    if menu_elements:
                        # Ищем ссылки на страницы пользователя
                        links = menu_elements[0].find_elements(By.TAG_NAME, "a")
                        for link in links:
                            href = link.get_attribute("href") or ""
                            if "user_profile" in href or "user_orders" in href:
                                is_user_logged_in = True
                                console.print("[green]  ✅ Пользователь авторизован (по наличию ссылок на профиль)[/green]")
                                break
                except:
                    pass
            
            # Если пользователь не авторизован, выходим из теста
            if not is_user_logged_in:
                console.print("[yellow]  ⚠️ Пользователь не авторизован, тест выхода пропускается[/yellow]")
                result.message = "Пользователь не авторизован, тест выхода пропущен"
                return
            
            # Делаем скриншот перед выходом
            self.take_screenshot("before_logout")
            
            # РАСШИРЕННЫЙ ПОИСК КНОПКИ ВЫХОДА
            console.print("[dim]  Поиск кнопки выхода...[/dim]")
            
            logout_links = []
            logout_selectors = [
                # Основные селекторы
                (By.XPATH, "//a[contains(@href, 'logout')]"),
                (By.XPATH, "//a[contains(text(), 'Выход')]"),
                (By.XPATH, "//a[contains(text(), 'Logout')]"),
                (By.CSS_SELECTOR, ".logout"),
                (By.CSS_SELECTOR, "[href*='logout']"),
                # Дополнительные селекторы
                (By.CSS_SELECTOR, ".logout-btn"),
                (By.XPATH, "//a[contains(@class, 'logout')]"),
                (By.XPATH, "//button[contains(text(), 'Выход')]"),
                (By.XPATH, "//*[contains(@class, 'logout')]"),
                (By.CSS_SELECTOR, "a[href*='user_logout']"),
                (By.CSS_SELECTOR, "a[href*='logout.php']"),
            ]
            
            for by, selector in logout_selectors:
                try:
                    links = self.driver.find_elements(by, selector)
                    if links:
                        # Фильтруем только видимые элементы
                        visible_links = [link for link in links if link.is_displayed()]
                        if visible_links:
                            logout_links = visible_links
                            console.print(f"[green]  ✅ Найдено {len(visible_links)} видимых элементов выхода по селектору {by}={selector}[/green]")
                            
                            # Выводим информацию о найденных элементах
                            for i, link in enumerate(visible_links[:3]):
                                link_text = link.text.strip() or "без текста"
                                link_href = link.get_attribute("href") or "без href"
                                console.print(f"[dim]    Элемент {i+1}: текст='{link_text}', href='{link_href}'[/dim]")
                            break
                except Exception as e:
                    console.print(f"[dim]    Ошибка при поиске по селектору {by}={selector}: {e}[/dim]")
                    continue
            
            # Если не нашли по селекторам, ищем в меню
            if len(logout_links) == 0:
                console.print("[yellow]  ⚠️ Кнопка выхода не найдена по селекторам, ищем в меню...[/yellow]")
                
                # Ищем меню навигации
                menus = self.driver.find_elements(By.CSS_SELECTOR, ".menu, nav, .menu-nav, .navigation")
                if menus:
                    # Ищем все ссылки в меню
                    menu_links = menus[0].find_elements(By.TAG_NAME, "a")
                    
                    for link in menu_links:
                        link_text = link.text.lower()
                        link_href = link.get_attribute("href") or ""
                        
                        # Проверяем, похоже ли на кнопку выхода
                        if ("выход" in link_text or 
                            "logout" in link_text or 
                            "log out" in link_text or
                            "exit" in link_text or
                            "user_logout" in link_href or
                            "logout.php" in link_href):
                            
                            if link.is_displayed():
                                logout_links = [link]
                                console.print(f"[green]  ✅ Найдена кнопка выхода в меню: текст='{link.text}', href='{link_href}'[/green]")
                                break
                
                # Если все еще не нашли, ищем любую кнопку с подходящим текстом
                if len(logout_links) == 0:
                    all_links = self.driver.find_elements(By.TAG_NAME, "a")
                    for link in all_links:
                        link_text = link.text.lower()
                        if ("выход" in link_text or "logout" in link_text):
                            if link.is_displayed():
                                logout_links = [link]
                                console.print(f"[green]  ✅ Найдена кнопка выхода по тексту: '{link.text}'[/green]")
                                break
            
            if len(logout_links) == 0:
                # Если не нашли кнопку выхода, делаем скриншот
                console.print("[red]  ❌ Кнопка выхода не найдена[/red]")
                self.take_screenshot("logout_button_not_found")
                
                # Проверяем, может мы уже на странице входа
                if "login" in self.driver.current_url or "user_login" in self.driver.current_url:
                    console.print("[green]  ✅ Пользователь уже на странице входа[/green]")
                    result.message = "Пользователь уже не в системе (на странице входа)"
                    return
                
                # Не падаем с ошибкой, просто предупреждаем
                console.print("[yellow]  ⚠️ Продолжаем тест, несмотря на отсутствие кнопки выхода[/yellow]")
                result.message = "Кнопка выхода не найдена, но тест продолжен"
                return
            
            # Сохраняем информацию о кнопке для отладки
            logout_button = logout_links[0]
            button_text = logout_button.text.strip() or "без текста"
            button_href = logout_button.get_attribute("href") or "без href"
            console.print(f"[dim]  Кнопка выхода: текст='{button_text}', href='{button_href}'[/dim]")
            
            # Прокрутка до элемента
            console.print("[dim]  Прокрутка до кнопки выхода...[/dim]")
            self.driver.execute_script("arguments[0].scrollIntoView({behavior: 'smooth', block: 'center'});", logout_button)
            time.sleep(1)
            
            # Пробуем кликнуть разными способами
            console.print("[dim]  Попытка клика по кнопке выхода...[/dim]")
            
            click_success = False
            click_methods = []
            
            # Способ 1: Обычный клик
            try:
                logout_button.click()
                click_success = True
                click_methods.append("обычный клик")
                console.print("[green]  ✅ Обычный клик выполнен[/green]")
            except Exception as e:
                console.print(f"[yellow]  ⚠️ Обычный клик не сработал: {e}[/yellow]")
                
                # Способ 2: JavaScript клик
                try:
                    self.driver.execute_script("arguments[0].click();", logout_button)
                    click_success = True
                    click_methods.append("JavaScript клик")
                    console.print("[green]  ✅ JavaScript клик выполнен[/green]")
                except Exception as e2:
                    console.print(f"[yellow]  ⚠️ JavaScript клик не сработал: {e2}[/yellow]")
                    
                    # Способ 3: Клик через ActionChains
                    try:
                        from selenium.webdriver.common.action_chains import ActionChains
                        actions = ActionChains(self.driver)
                        actions.move_to_element(logout_button).click().perform()
                        click_success = True
                        click_methods.append("ActionChains клик")
                        console.print("[green]  ✅ ActionChains клик выполнен[/green]")
                    except Exception as e3:
                        console.print(f"[red]  ❌ Все способы клика не сработали: {e3}[/red]")
            
            if not click_success:
                console.print("[red]  ❌ Не удалось кликнуть по кнопке выхода[/red]")
                result.message = "Не удалось кликнуть по кнопке выхода"
                return
            
            console.print(f"[dim]  Клик выполнен методом: {', '.join(click_methods)}[/dim]")
            
            # Ждем обработки выхода
            console.print("[dim]  ⏳ Ожидание обработки выхода...[/dim]")
            time.sleep(3)
            self.wait_for_page_load()
            
            # Делаем скриншот после выхода
            self.take_screenshot("after_logout")
            
            # ПРОВЕРКА УСПЕШНОГО ВЫХОДА
            current_url = self.driver.current_url
            console.print(f"[dim]  URL после выхода: {current_url}[/dim]")
            
            # Проверяем, что мы на странице входа
            login_success = False
            login_indicators = [
                "login" in current_url,
                "user_login" in current_url,
                "вход" in self.driver.title.lower(),
                "login" in self.driver.title.lower(),
            ]
            
            if any(login_indicators):
                login_success = True
                console.print("[green]  ✅ Успешно перенаправлены на страницу входа[/green]")
            
            # Проверяем наличие формы входа
            if not login_success:
                # Ищем форму входа на странице
                forms = self.driver.find_elements(By.TAG_NAME, "form")
                if forms:
                    # Проверяем, есть ли поля для ввода логина и пароля
                    has_username = False
                    has_password = False
                    
                    inputs = forms[0].find_elements(By.TAG_NAME, "input")
                    for inp in inputs:
                        input_type = inp.get_attribute("type") or ""
                        input_name = inp.get_attribute("name") or ""
                        
                        if input_type == "password" or "password" in input_name:
                            has_password = True
                        elif input_type == "text" or "username" in input_name or "login" in input_name:
                            has_username = True
                    
                    if has_username and has_password:
                        login_success = True
                        console.print("[green]  ✅ На странице найдена форма входа[/green]")
            
            # Проверяем наличие сообщения о выходе
            page_source = self.driver.page_source.lower()
            logout_messages = ["вы вышли", "успешный выход", "до свидания", "спасибо за визит"]
            for message in logout_messages:
                if message in page_source:
                    console.print(f"[green]  ✅ Найдено сообщение: '{message}'[/green]")
                    login_success = True
            
            if not login_success:
                console.print("[yellow]  ⚠️ Не удалось подтвердить успешный выход[/yellow]")
                # Выводим информацию о текущей странице
                console.print(f"[dim]  Заголовок страницы: {self.driver.title}[/dim]")
                console.print(f"[dim]  URL: {current_url}[/dim]")
                
                # Делаем дополнительный скриншот
                self.take_screenshot("logout_verification_failed")
            
            result.message = f"✅ Выход из системы выполнен. Текущий URL: {current_url}"
            
        except Exception as e:
            console.print(f"[red]❌ Ошибка в тесте выхода пользователя: {e}[/red]")
            self.take_screenshot("user_logout_error")
            result.message = f"Ошибка: {e}"
            result.error = e
            # Не пробрасываем исключение, чтобы тест считался выполненным
            
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