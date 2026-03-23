from playwright.sync_api import sync_playwright
from config import BASE_URL, TEST_USER

def test_user_login():
    with sync_playwright() as p:
        browser = p.firefox.launch(headless=False)
        page = browser.new_page()
        page.goto(f"{BASE_URL}/user_login.php")
        page.fill("input[name='username']", TEST_USER["username"])
        page.fill("input[name='password']", TEST_USER["password"])
        page.click("button[type='submit']")
        page.wait_for_url("**/user_index.php")
        assert page.url.endswith("user_index.php")
        browser.close()