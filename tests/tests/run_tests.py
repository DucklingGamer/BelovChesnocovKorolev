#!/usr/bin/env python3
import os
import sys
import pytest
import webbrowser
from datetime import datetime
from weasyprint import HTML

def add_download_link(html_file, pdf_filename):
    """
    Добавляет кнопку скачивания PDF в HTML-отчёт.
    Кнопка размещается перед закрывающим тегом </body>.
    """
    with open(html_file, 'r', encoding='utf-8') as f:
        content = f.read()

    button_html = f'''
    <div style="text-align: center; margin: 20px;">
        <a href="{pdf_filename}" download style="display: inline-block; padding: 10px 20px; background-color: #ff4d6d; color: white; text-decoration: none; border-radius: 5px; font-weight: bold;">📥 Скачать PDF-отчёт</a>
    </div>
    '''

    if '</body>' in content:
        # Вставляем кнопку перед закрывающим тегом body
        content = content.replace('</body>', button_html + '</body>', 1)
        with open(html_file, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"✅ Кнопка скачивания добавлена в {html_file}")
    else:
        print("⚠️ Не найден тег </body>, кнопка не добавлена.")

def run_tests():
    # Создаём папку для отчётов
    reports_dir = "reports"
    os.makedirs(reports_dir, exist_ok=True)

    # Генерируем имена файлов с временной меткой
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    html_file = os.path.join(reports_dir, f"report_{timestamp}.html")
    pdf_file = os.path.join(reports_dir, f"report_{timestamp}.pdf")

    # Параметры pytest
    pytest_args = [
        "tests/",
        f"--html={html_file}",
        "--self-contained-html",
        "-v"
    ]

    # Запуск тестов
    exit_code = pytest.main(pytest_args)

    # Конвертация HTML в PDF
    if os.path.exists(html_file):
        print(f"🔄 Конвертация {html_file} -> {pdf_file}")
        HTML(html_file).write_pdf(pdf_file)
        print(f"✅ PDF-отчёт сохранён: {pdf_file}")

        # Добавляем кнопку скачивания PDF в HTML
        add_download_link(html_file, os.path.basename(pdf_file))

        # Автоматически открываем HTML в браузере (опционально)
        webbrowser.open(html_file)
    else:
        print("❌ HTML-отчёт не создан.")

    return exit_code

if __name__ == "__main__":
    sys.exit(run_tests())