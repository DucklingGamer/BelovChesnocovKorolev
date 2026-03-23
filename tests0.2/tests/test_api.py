import requests
import pytest
from config import BASE_URL, TEST_USER, ADMIN_USER
import time

# Фикстура для сессии (чтобы сохранять куки между запросами)
@pytest.fixture
def session():
    s = requests.Session()
    yield s
    s.close()

def test_homepage_accessible():
    """Главная страница (неавторизованный доступ) должна быть доступна"""
    resp = requests.get(f"{BASE_URL}/user_login.php")
    assert resp.status_code == 200
    assert "Вход для покупателей" in resp.text

def test_register_user(session):
    """Регистрация нового пользователя через POST"""
    # Генерируем уникальные данные
    username = TEST_USER["username"]
    email = TEST_USER["email"]()
    phone = TEST_USER["phone"]
    password = TEST_USER["password"]

    data = {
        "username": username,
        "email": email,
        "phone": phone,
        "password": password,
        "confirm_password": password
    }
    resp = session.post(f"{BASE_URL}/user_register.php", data=data)
    # После успешной регистрации обычно редирект или страница с сообщением
    assert resp.status_code in (200, 302)
    # Проверяем наличие сообщения об успехе
    if resp.status_code == 200:
        assert "успешн" in resp.text.lower() or "success" in resp.text.lower()
    else:
        # Если редирект, проверяем заголовок Location
        location = resp.headers.get("Location", "")
        assert "login" in location or "index" in location

def test_login_user(session):
    """Вход пользователя через POST"""
    data = {
        "username": TEST_USER["username"],
        "password": TEST_USER["password"]
    }
    resp = session.post(f"{BASE_URL}/user_login.php", data=data, allow_redirects=False)
    # Ожидаем редирект на user_index.php
    assert resp.status_code == 302
    location = resp.headers.get("Location", "")
    assert "user_index.php" in location

    # Выполним GET на страницу после редиректа (сессия сохраняется в session)
    index_resp = session.get(f"{BASE_URL}/user_index.php")
    assert index_resp.status_code == 200
    # Проверяем, что имя пользователя отображается
    assert TEST_USER["username"] in index_resp.text

def test_access_protected_page_without_login():
    """Попытка зайти на защищённую страницу без авторизации должна редиректить на логин"""
    resp = requests.get(f"{BASE_URL}/user_profile.php", allow_redirects=False)
    assert resp.status_code == 302
    location = resp.headers.get("Location", "")
    assert "user_login.php" in location

def test_logout(session):
    """Выход из системы"""
    # Сначала залогинимся
    login_data = {"username": TEST_USER["username"], "password": TEST_USER["password"]}
    session.post(f"{BASE_URL}/user_login.php", data=login_data)
    # Затем выходим
    logout_resp = session.get(f"{BASE_URL}/user_logout.php", allow_redirects=False)
    assert logout_resp.status_code == 302
    location = logout_resp.headers.get("Location", "")
    assert "user_login.php" in location

    # Пытаемся зайти на защищённую страницу – должны снова редиректить
    profile_resp = session.get(f"{BASE_URL}/user_profile.php", allow_redirects=False)
    assert profile_resp.status_code == 302
    assert "user_login.php" in profile_resp.headers.get("Location", "")

def test_admin_login(session):
    """Вход администратора"""
    data = {
        "username": ADMIN_USER["username"],
        "password": ADMIN_USER["password"]
    }
    resp = session.post(f"{BASE_URL}/login.php", data=data, allow_redirects=False)
    assert resp.status_code == 302
    location = resp.headers.get("Location", "")
    assert "index.php" in location  # админка редиректит на index.php

    # Проверяем доступ к админ-странице
    admin_page = session.get(f"{BASE_URL}/index.php")
    assert admin_page.status_code == 200
    # Проверяем, что есть элементы меню админа
    assert "Категории" in admin_page.text
    assert "Товары" in admin_page.text