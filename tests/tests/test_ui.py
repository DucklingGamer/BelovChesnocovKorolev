import pytest
import time
from pages.register_page import RegisterPage
from pages.login_page import LoginPage
from pages.cart_page import CartPage
from config import BASE_URL, TEST_USER

def test_user_registration(browser):
    # Создаём объект страницы регистрации
    register_page = RegisterPage(browser)
    register_page.open(BASE_URL)

    # Генерируем уникальные данные
    unique_username = f"testuser_{int(time.time())}"
    unique_email = f"test_{int(time.time())}@example.com"

    # Регистрируемся
    register_page.register(
        unique_username,
        unique_email,
        TEST_USER["phone"],
        TEST_USER["password"]
    )

    # Проверяем успех (появление сообщения или редирект)
    time.sleep(2)  # небольшая задержка для обработки
    assert ("успешн" in browser.page_source.lower()
            or "login" in browser.current_url)

def test_user_login(browser):
    login_page = LoginPage(browser)
    login_page.open(BASE_URL)
    login_page.login(TEST_USER["username"], TEST_USER["password"])

    # Проверяем, что попали на главную страницу пользователя
    assert "user_index.php" in browser.current_url
    # Дополнительно можно проверить приветствие
    assert TEST_USER["username"] in browser.page_source