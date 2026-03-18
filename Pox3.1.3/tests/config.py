BASE_URL = "http://localhost:3000"  # ваш локальный сервер

TEST_USER = {
    "username": "testuser_" + __import__("time").strftime("%Y%m%d%H%M%S"),
    "password": "Test123!@#",
    "email": lambda: f"test_{__import__('time').strftime('%Y%m%d%H%M%S')}@example.com",
    "phone": "+79991234567"
}

ADMIN_USER = {
    "username": "456456",
    "password": "456456"
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

DB_CATALOG = {
    "host": "134.90.167.42",
    "port": 10306,
    "user": "Chesnokov",
    "password": "CAaSRQUQ/5qvp29f",
    "database": "project_Chesnokov"
}

DB_ORDERS = {
    "host": "134.90.167.42",
    "port": 10306,
    "user": "Belov",
    "password": "B6EQr.7PN]*u8Ffn",
    "database": "project_Belov"
}