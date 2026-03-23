from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class RegisterPage:
    """Класс для работы со страницей регистрации пользователя"""

    def __init__(self, driver):
        self.driver = driver
        # Локаторы элементов (проверьте, совпадают ли они с вашим сайтом)
        self.username_input = (By.NAME, "username")
        self.email_input = (By.NAME, "email")
        self.phone_input = (By.NAME, "phone")
        self.password_input = (By.NAME, "password")
        self.confirm_input = (By.NAME, "confirm_password")
        self.submit_button = (By.CSS_SELECTOR, "button[type='submit']")

    def open(self, base_url):
        """Открыть страницу регистрации"""
        self.driver.get(f"{base_url}/user_register.php")

    def register(self, username, email, phone, password):
        """Заполнить форму регистрации и отправить"""
        # Ожидание появления поля username
        WebDriverWait(self.driver, 10).until(
            EC.visibility_of_element_located(self.username_input)
        ).send_keys(username)

        self.driver.find_element(*self.email_input).send_keys(email)
        self.driver.find_element(*self.phone_input).send_keys(phone)
        self.driver.find_element(*self.password_input).send_keys(password)
        self.driver.find_element(*self.confirm_input).send_keys(password)
        self.driver.find_element(*self.submit_button).click()