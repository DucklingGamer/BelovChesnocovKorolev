import pytest
from selenium import webdriver
from selenium.webdriver.firefox.service import Service
from webdriver_manager.firefox import GeckoDriverManager
import pymysql
from config import *
from config import DB_LOCAL

@pytest.fixture(scope="function")
def browser():
    service = Service(GeckoDriverManager().install())
    options = webdriver.FirefoxOptions()
    # options.add_argument("--headless")  # раскомментировать для фонового режима
    driver = webdriver.Firefox(service=service, options=options)
    driver.implicitly_wait(5)
    yield driver
    driver.quit()

@pytest.fixture(scope="session")
def db_orders():
    conn = pymysql.connect(**DB_ORDERS, charset='utf8mb4')
    yield conn
    conn.close()
    
@pytest.fixture(scope="session")
def db_local():
    """Подключение к локальной БД Bd_belov (таблица site_users)"""
    conn = pymysql.connect(**DB_LOCAL)
    yield conn
    conn.close()
    
@pytest.fixture
def cleanup_user(db_local):
    """Удаляет тестового пользователя после теста (если передан username)"""
    usernames = []
    def _add(username):
        usernames.append(username)
    yield _add
    for username in usernames:
        with db_local.cursor() as cursor:
            cursor.execute("DELETE FROM site_users WHERE username = %s", (username,))
        db_local.commit()