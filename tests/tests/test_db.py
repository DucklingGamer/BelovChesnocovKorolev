import pytest
from config import TEST_USER

def test_user_created_in_db(db_local):   # было db_orders
    with db_local.cursor() as cursor:
        cursor.execute("SELECT id FROM site_users WHERE username = %s", (TEST_USER["username"],))
        user = cursor.fetchone()
    assert user is not None, "Пользователь не найден в БД"