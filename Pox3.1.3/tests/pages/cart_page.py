from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class CartPage:
    """Класс для работы с корзиной"""

    def __init__(self, driver):
        self.driver = driver
        self.cart_items = (By.CSS_SELECTOR, ".cart-item")  # уточните селектор на вашем сайте
        self.checkout_button = (By.LINK_TEXT, "Оформить заказ")
        self.clear_button = (By.LINK_TEXT, "Очистить")

    def open(self, base_url):
        self.driver.get(f"{base_url}/user_cart.php")

    def get_items_count(self):
        """Вернуть количество товаров в корзине"""
        return len(self.driver.find_elements(*self.cart_items))

    def proceed_to_checkout(self):
        """Перейти к оформлению заказа"""
        self.driver.find_element(*self.checkout_button).click()

    def clear_cart(self):
        """Очистить корзину"""
        self.driver.find_element(*self.clear_button).click()