#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Тестирование подключения к базам данных Кавай Магазина
Запуск: python test_db_connection.py
"""

import time
import json
import sys
import os
import base64
from datetime import datetime
from typing import Dict, List, Tuple, Optional

# Попытка импортировать mysql.connector
try:
    import mysql.connector
    from mysql.connector import Error
    MYSQL_AVAILABLE = True
except ImportError:
    MYSQL_AVAILABLE = False
    print("❌ Библиотека mysql.connector не установлена!")
    print("Установите ее командой: pip install mysql-connector-python")
    sys.exit(1)

# Цвета для вывода в консоль
class Colors:
    HEADER = '\033[95m'
    BLUE = '\033[94m'
    GREEN = '\033[92m'
    YELLOW = '\033[93m'
    RED = '\033[91m'
    END = '\033[0m'
    BOLD = '\033[1m'

class DatabaseTester:
    """Класс для тестирования подключения к базам данных"""
    
    def __init__(self):
        self.results = {
            'local': {'status': False, 'message': '', 'tables': {}, 'error': None, 'all_tables': []},
            'catalog': {'status': False, 'message': '', 'tables': {}, 'error': None, 'all_tables': []},
            'orders': {'status': False, 'message': '', 'tables': {}, 'error': None, 'all_tables': []}
        }
        self.start_time = datetime.now()
        self.test_results = []
        
    def print_header(self, text: str):
        """Вывод заголовка"""
        print(f"\n{Colors.HEADER}{'='*60}{Colors.END}")
        print(f"{Colors.BOLD}{Colors.BLUE}🔍 {text}{Colors.END}")
        print(f"{Colors.HEADER}{'='*60}{Colors.END}")
    
    def print_success(self, text: str):
        """Вывод успешного сообщения"""
        print(f"{Colors.GREEN}  ✅ {text}{Colors.END}")
    
    def print_error(self, text: str):
        """Вывод сообщения об ошибке"""
        print(f"{Colors.RED}  ❌ {text}{Colors.END}")
    
    def print_warning(self, text: str):
        """Вывод предупреждения"""
        print(f"{Colors.YELLOW}  ⚠️ {text}{Colors.END}")
    
    def print_info(self, text: str):
        """Вывод информационного сообщения"""
        print(f"  📌 {text}")
    
    def print_dim(self, text: str):
        """Вывод приглушенного текста"""
        print(f"  {Colors.BLUE}{text}{Colors.END}")
    
    def test_local_database(self):
        """Тестирование подключения к локальной БД (Bd_belov)"""
        print(f"\n{Colors.BOLD}📦 ТЕСТ 1: Локальная БД (Bd_belov){Colors.END}")
        print(f"{Colors.BLUE}{'─'*50}{Colors.END}")
        
        # Параметры подключения
        config = {
            'host': 'localhost',
            'port': 3306,
            'database': 'Bd_belov',
            'user': 'admin',
            'password': 'admin',
            'charset': 'utf8mb4'
        }
        
        print(f"  Хост: {config['host']}:{config['port']}")
        print(f"  База данных: {config['database']}")
        print(f"  Пользователь: {config['user']}")
        
        conn = None
        try:
            # Замеряем время подключения
            start_time = time.time()
            
            # Подключение к БД
            conn = mysql.connector.connect(
                host=config['host'],
                user=config['user'],
                password=config['password'],
                database=config['database'],
                port=config['port'],
                charset=config['charset'],
                connection_timeout=5
            )
            
            connect_time = time.time() - start_time
            self.print_success(f"Подключение успешно ({connect_time:.3f} сек)")
            
            cursor = conn.cursor()
            
            # Получаем список таблиц
            cursor.execute("SHOW TABLES")
            tables = [table[0] for table in cursor.fetchall()]
            
            self.print_info(f"Найдено таблиц: {len(tables)}")
            
            # Детальная информация о таблицах
            table_details = {}
            important_tables = ['site_users', 'users']
            found_important = []
            
            for table in tables:
                try:
                    cursor.execute(f"SELECT COUNT(*) FROM `{table}`")
                    count = cursor.fetchone()[0]
                    table_details[table] = count
                    
                    if table in important_tables:
                        found_important.append(table)
                        self.print_success(f"Таблица {table}: {count} записей")
                    else:
                        self.print_dim(f"Таблица {table}: {count} записей")
                except Exception as e:
                    table_details[table] = '?'
                    self.print_warning(f"Таблица {table}: не удалось получить количество ({e})")
            
            # Проверяем наличие важных таблиц
            for imp_table in important_tables:
                if imp_table not in tables:
                    self.print_warning(f"Важная таблица {imp_table} не найдена")
            
            # Сохраняем результаты
            self.results['local']['status'] = True
            self.results['local']['message'] = f"Подключено, таблиц: {len(tables)}"
            self.results['local']['tables'] = table_details
            self.results['local']['connect_time'] = connect_time
            self.results['local']['found_important'] = found_important
            self.results['local']['all_tables'] = tables
            
            cursor.close()
            
        except mysql.connector.Error as e:
            self.print_error(f"Ошибка MySQL: {e}")
            self.results['local']['status'] = False
            self.results['local']['message'] = f"Ошибка: {e}"
            self.results['local']['error'] = str(e)
        except Exception as e:
            self.print_error(f"Неизвестная ошибка: {e}")
            self.results['local']['status'] = False
            self.results['local']['message'] = f"Ошибка: {e}"
            self.results['local']['error'] = str(e)
        finally:
            if conn and conn.is_connected():
                conn.close()
    
    def test_catalog_database(self):
        """Тестирование подключения к БД каталога (project_Chesnokov)"""
        print(f"\n{Colors.BOLD}📦 ТЕСТ 2: БД каталога (project_Chesnokov){Colors.END}")
        print(f"{Colors.BLUE}{'─'*50}{Colors.END}")
        
        # Параметры подключения
        config = {
            'host': '134.90.167.42',
            'port': 10306,
            'database': 'project_Chesnokov',
            'user': 'Chesnokov',
            'password': 'CAaSRQUQ/5qvp29f',
            'charset': 'utf8mb4'
        }
        
        print(f"  Хост: {config['host']}:{config['port']}")
        print(f"  База данных: {config['database']}")
        print(f"  Пользователь: {config['user']}")
        
        conn = None
        try:
            # Замеряем время подключения
            start_time = time.time()
            
            # Подключение к БД
            conn = mysql.connector.connect(
                host=config['host'],
                user=config['user'],
                password=config['password'],
                database=config['database'],
                port=config['port'],
                charset=config['charset'],
                connection_timeout=10
            )
            
            connect_time = time.time() - start_time
            self.print_success(f"Подключение успешно ({connect_time:.3f} сек)")
            
            cursor = conn.cursor()
            
            # Получаем список таблиц
            cursor.execute("SHOW TABLES")
            tables = [table[0] for table in cursor.fetchall()]
            
            self.print_info(f"Найдено таблиц: {len(tables)}")
            
            # Проверяем наличие основных таблиц
            required_tables = ['products', 'categories', 'additional_services']
            table_details = {}
            
            for table in required_tables:
                if table in tables:
                    cursor.execute(f"SELECT COUNT(*) FROM `{table}`")
                    count = cursor.fetchone()[0]
                    table_details[table] = count
                    self.print_success(f"Таблица {table}: {count} записей")
                else:
                    table_details[table] = 0
                    self.print_warning(f"Таблица {table} не найдена")
            
            # Проверка целостности данных
            if 'products' in tables and 'categories' in tables:
                try:
                    cursor.execute("""
                        SELECT COUNT(*) FROM products 
                        WHERE category_id IS NOT NULL 
                        AND category_id NOT IN (SELECT category_id FROM categories)
                    """)
                    invalid_products = cursor.fetchone()[0]
                    if invalid_products > 0:
                        self.print_warning(f"Товаров с несуществующими категориями: {invalid_products}")
                    else:
                        self.print_success("Связь products-categories корректна")
                except Exception as e:
                    self.print_warning(f"Не удалось проверить связи: {e}")
            
            # Сохраняем результаты
            self.results['catalog']['status'] = True
            self.results['catalog']['message'] = f"Подключено, таблиц: {len(tables)}"
            self.results['catalog']['tables'] = table_details
            self.results['catalog']['connect_time'] = connect_time
            self.results['catalog']['all_tables'] = tables
            
            cursor.close()
            
        except mysql.connector.Error as e:
            self.print_error(f"Ошибка MySQL: {e}")
            self.results['catalog']['status'] = False
            self.results['catalog']['message'] = f"Ошибка: {e}"
            self.results['catalog']['error'] = str(e)
        except Exception as e:
            self.print_error(f"Неизвестная ошибка: {e}")
            self.results['catalog']['status'] = False
            self.results['catalog']['message'] = f"Ошибка: {e}"
            self.results['catalog']['error'] = str(e)
        finally:
            if conn and conn.is_connected():
                conn.close()
    
    def test_orders_database(self):
        """Тестирование подключения к БД заказов (project_Belov)"""
        print(f"\n{Colors.BOLD}📦 ТЕСТ 3: БД заказов (project_Belov){Colors.END}")
        print(f"{Colors.BLUE}{'─'*50}{Colors.END}")
        
        # Параметры подключения
        config = {
            'host': '134.90.167.42',
            'port': 10306,
            'database': 'project_Belov',
            'user': 'Belov',
            'password': 'B6EQr.7PN]*u8Ffn',
            'charset': 'utf8mb4'
        }
        
        print(f"  Хост: {config['host']}:{config['port']}")
        print(f"  База данных: {config['database']}")
        print(f"  Пользователь: {config['user']}")
        
        conn = None
        try:
            # Замеряем время подключения
            start_time = time.time()
            
            # Подключение к БД
            conn = mysql.connector.connect(
                host=config['host'],
                user=config['user'],
                password=config['password'],
                database=config['database'],
                port=config['port'],
                charset=config['charset'],
                connection_timeout=10
            )
            
            connect_time = time.time() - start_time
            self.print_success(f"Подключение успешно ({connect_time:.3f} сек)")
            
            cursor = conn.cursor()
            
            # Получаем список таблиц
            cursor.execute("SHOW TABLES")
            tables = [table[0] for table in cursor.fetchall()]
            
            self.print_info(f"Найдено таблиц: {len(tables)}")
            
            # Проверяем наличие основных таблиц
            required_tables = ['clients', 'orders', 'order_items', 'order_services']
            table_details = {}
            
            for table in required_tables:
                if table in tables:
                    cursor.execute(f"SELECT COUNT(*) FROM `{table}`")
                    count = cursor.fetchone()[0]
                    table_details[table] = count
                    self.print_success(f"Таблица {table}: {count} записей")
                else:
                    table_details[table] = 0
                    self.print_warning(f"Таблица {table} не найдена")
            
            # Проверка связей
            if 'orders' in tables and 'clients' in tables:
                try:
                    cursor.execute("""
                        SELECT COUNT(*) FROM orders 
                        WHERE client_id NOT IN (SELECT client_id FROM clients)
                    """)
                    invalid_orders = cursor.fetchone()[0]
                    if invalid_orders > 0:
                        self.print_warning(f"Заказов с несуществующими клиентами: {invalid_orders}")
                    else:
                        self.print_success("Связь orders-clients корректна")
                except Exception as e:
                    self.print_warning(f"Не удалось проверить связи: {e}")
            
            # Сохраняем результаты
            self.results['orders']['status'] = True
            self.results['orders']['message'] = f"Подключено, таблиц: {len(tables)}"
            self.results['orders']['tables'] = table_details
            self.results['orders']['connect_time'] = connect_time
            self.results['orders']['all_tables'] = tables
            
            cursor.close()
            
        except mysql.connector.Error as e:
            self.print_error(f"Ошибка MySQL: {e}")
            self.results['orders']['status'] = False
            self.results['orders']['message'] = f"Ошибка: {e}"
            self.results['orders']['error'] = str(e)
        except Exception as e:
            self.print_error(f"Неизвестная ошибка: {e}")
            self.results['orders']['status'] = False
            self.results['orders']['message'] = f"Ошибка: {e}"
            self.results['orders']['error'] = str(e)
        finally:
            if conn and conn.is_connected():
                conn.close()
    
    def run_all_tests(self):
        """Запуск всех тестов"""
        self.print_header("ТЕСТИРОВАНИЕ ПОДКЛЮЧЕНИЯ К БАЗАМ ДАННЫХ")
        print(f"{Colors.BOLD}Время начала: {self.start_time.strftime('%d.%m.%Y %H:%M:%S')}{Colors.END}")
        
        # Тест 1: Локальная БД
        self.test_local_database()
        
        # Тест 2: БД каталога
        self.test_catalog_database()
        
        # Тест 3: БД заказов
        self.test_orders_database()
        
        # Вывод сводки
        self.print_summary()
        
        # Сохранение JSON отчета
        json_file = self.save_json_report()
        
        # Создание HTML отчета с возможностью скачать PDF
        html_file = self.generate_html_report_with_pdf()
        
        print(f"\n{Colors.GREEN}✅ JSON отчет: {json_file}{Colors.END}")
        print(f"{Colors.GREEN}✅ HTML отчет: {html_file}{Colors.END}")
        print(f"{Colors.BLUE}📊 Откройте HTML файл в браузере и нажмите кнопку 'Скачать PDF'{Colors.END}")
    
    def print_summary(self):
        """Вывод сводки результатов"""
        self.print_header("СВОДКА РЕЗУЛЬТАТОВ")
        
        total = len(self.results)
        successful = sum(1 for r in self.results.values() if r['status'])
        
        print(f"\n{Colors.BOLD}Всего тестов: {total}{Colors.END}")
        print(f"{Colors.GREEN}✅ Успешно: {successful}{Colors.END}")
        print(f"{Colors.RED}❌ Ошибок: {total - successful}{Colors.END}")
        
        print(f"\n{Colors.BOLD}Детали по базам данных:{Colors.END}")
        
        for db_name, result in self.results.items():
            if result['status']:
                status_icon = f"{Colors.GREEN}✅{Colors.END}"
                time_info = f" ({result.get('connect_time', 0):.3f} сек)"
            else:
                status_icon = f"{Colors.RED}❌{Colors.END}"
                time_info = ""
            
            db_display = {
                'local': 'Локальная БД (Bd_belov)',
                'catalog': 'БД каталога (project_Chesnokov)',
                'orders': 'БД заказов (project_Belov)'
            }.get(db_name, db_name)
            
            print(f"\n{status_icon} {Colors.BOLD}{db_display}{Colors.END}{time_info}")
            print(f"  {result['message']}")
            
            if result['status'] and result['tables']:
                print(f"  {Colors.BLUE}Таблицы:{Colors.END}")
                for table, count in list(result['tables'].items())[:5]:
                    print(f"    • {table}: {count} записей")
            
            if not result['status'] and result['error']:
                print(f"  {Colors.RED}Ошибка: {result['error']}{Colors.END}")
    
    def save_json_report(self):
        """Сохранение отчета в JSON файл"""
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"db_test_report_{timestamp}.json"
        
        report = {
            'test_start': self.start_time.strftime('%Y-%m-%d %H:%M:%S'),
            'test_end': datetime.now().strftime('%Y-%m-%d %H:%M:%S'),
            'summary': {
                'total': len(self.results),
                'successful': sum(1 for r in self.results.values() if r['status']),
                'failed': sum(1 for r in self.results.values() if not r['status'])
            },
            'results': self.results
        }
        
        with open(filename, 'w', encoding='utf-8') as f:
            json.dump(report, f, ensure_ascii=False, indent=2, default=str)
        
        return filename
    
    def generate_html_report_with_pdf(self):
        """Генерация HTML отчета с кнопкой для скачивания PDF"""
        timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
        filename = f"db_test_report_{timestamp}.html"
        
        # Подготовка данных для отчета
        total = len(self.results)
        successful = sum(1 for r in self.results.values() if r['status'])
        failed = total - successful
        
        # Создаем HTML
        html_content = f"""<!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>📊 Отчет тестирования БД - Кавай Магазин</title>
        <style>
            body {{
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                color: #333;
            }}
            .container {{
                max-width: 1200px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                padding: 30px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            }}
            h1 {{
                color: #764ba2;
                margin-top: 0;
                border-bottom: 3px solid #667eea;
                padding-bottom: 10px;
            }}
            .header {{
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
                flex-wrap: wrap;
                gap: 15px;
            }}
            .button-group {{
                display: flex;
                gap: 10px;
            }}
            .download-btn {{
                background: linear-gradient(135deg, #48bb78, #38a169);
                color: white;
                border: none;
                padding: 12px 25px;
                border-radius: 50px;
                font-size: 1rem;
                font-weight: bold;
                cursor: pointer;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: all 0.3s;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                border: 2px solid transparent;
            }}
            .download-btn:hover {{
                transform: translateY(-2px);
                box-shadow: 0 6px 12px rgba(0,0,0,0.15);
                background: linear-gradient(135deg, #38a169, #2f855a);
            }}
            .download-btn:active {{
                transform: translateY(0);
            }}
            .download-btn.print-btn {{
                background: linear-gradient(135deg, #667eea, #5a67d8);
            }}
            .download-btn.print-btn:hover {{
                background: linear-gradient(135deg, #5a67d8, #4c51bf);
            }}
            .summary {{
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }}
            .summary-card {{
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }}
            .summary-card.success {{ background: linear-gradient(135deg, #48bb78 0%, #38a169 100%); }}
            .summary-card.error {{ background: linear-gradient(135deg, #f56565 0%, #c53030 100%); }}
            .summary-number {{
                font-size: 2.5rem;
                font-weight: bold;
                margin: 10px 0;
            }}
            .database-card {{
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }}
            .database-card.success {{ border-left: 5px solid #48bb78; }}
            .database-card.error {{ border-left: 5px solid #f56565; }}
            .database-title {{
                font-size: 1.2rem;
                font-weight: bold;
                margin-bottom: 15px;
                color: #764ba2;
            }}
            .table-list {{
                background: #f7fafc;
                padding: 15px;
                border-radius: 8px;
                margin-top: 10px;
            }}
            .table-item {{
                display: flex;
                justify-content: space-between;
                padding: 5px 0;
                border-bottom: 1px solid #e2e8f0;
            }}
            .badge {{
                display: inline-block;
                padding: 3px 8px;
                border-radius: 12px;
                font-size: 0.75rem;
                font-weight: bold;
            }}
            .badge-success {{ background: #48bb78; color: white; }}
            .badge-warning {{ background: #fbbf24; color: white; }}
            .badge-error {{ background: #f56565; color: white; }}
            .timestamp {{
                color: #718096;
                font-size: 0.9rem;
                margin-top: 20px;
                text-align: right;
                border-top: 1px solid #e2e8f0;
                padding-top: 20px;
            }}
            .pdf-content {{
                display: none;
            }}
            .loading {{
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1000;
                justify-content: center;
                align-items: center;
                color: white;
                font-size: 1.5rem;
            }}
            .loading.active {{
                display: flex;
            }}
            .spinner {{
                border: 4px solid #f3f3f3;
                border-top: 4px solid #48bb78;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin-right: 15px;
            }}
            @keyframes spin {{
                0% {{ transform: rotate(0deg); }}
                100% {{ transform: rotate(360deg); }}
            }}
        </style>
    </head>
    <body>
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <span>Генерация PDF...</span>
        </div>

        <div class="container" id="report-content">
            <div class="header">
                <h1>📊 Отчет тестирования подключения к БД</h1>
                <div class="button-group">
                    <button class="download-btn" onclick="downloadAsPDF()">
                        <span>📥</span> Скачать PDF
                    </button>
                    <button class="download-btn print-btn" onclick="window.print()">
                        <span>🖨️</span> Печать
                    </button>
                </div>
            </div>
            
            <div class="summary">
                <div class="summary-card">
                    <div>Всего тестов</div>
                    <div class="summary-number">{total}</div>
                </div>
                <div class="summary-card success">
                    <div>Успешно</div>
                    <div class="summary-number">{successful}</div>
                </div>
                <div class="summary-card error">
                    <div>Ошибок</div>
                    <div class="summary-number">{failed}</div>
                </div>
            </div>
            
            <div class="timestamp">
                <strong>Тест запущен:</strong> {self.start_time.strftime('%d.%m.%Y %H:%M:%S')}<br>
                <strong>Тест завершен:</strong> {datetime.now().strftime('%d.%m.%Y %H:%M:%S')}
            </div>
    """
        
        # Добавляем информацию по каждой БД
        for db_name, result in self.results.items():
            db_titles = {
                'local': 'Локальная БД (Bd_belov)',
                'catalog': 'БД каталога (project_Chesnokov)',
                'orders': 'БД заказов (project_Belov)'
            }
            
            status_class = 'success' if result['status'] else 'error'
            status_text = '✅ Подключено' if result['status'] else '❌ Ошибка'
            connect_time = result.get('connect_time', 0)
            
            html_content += f"""
            <div class="database-card {status_class}">
                <div class="database-title">{db_titles.get(db_name, db_name)}</div>
                <p><strong>Статус:</strong> <span class="badge badge-{status_class}">{status_text}</span></p>
                <p><strong>Время подключения:</strong> {connect_time:.3f} сек</p>
                <p><strong>{result['message']}</strong></p>
    """
            
            if result['status'] and result['tables']:
                html_content += """
                <div class="table-list">
                    <strong>Таблицы:</strong>
    """
                # Показываем все таблицы
                for table, count in result['tables'].items():
                    html_content += f"""
                    <div class="table-item">
                        <span>📊 {table}</span>
                        <span class="badge badge-success">{count} записей</span>
                    </div>
    """
                
                # Показываем остальные таблицы, если они есть
                if 'all_tables' in result and len(result['all_tables']) > len(result['tables']):
                    other_tables = [t for t in result['all_tables'] if t not in result['tables']]
                    if other_tables:
                        html_content += f"""
                    <div class="table-item">
                        <span>📊 И другие таблицы...</span>
                        <span class="badge badge-warning">еще {len(other_tables)}</span>
                    </div>
    """
                html_content += "            </div>\n"
            
            if not result['status'] and result['error']:
                html_content += f"""
                <div style="color: #f56565; margin-top: 10px; padding: 10px; background: #fff5f5; border-radius: 5px;">
                    <strong>Ошибка:</strong> {result['error']}
                </div>
    """
            
            html_content += "        </div>\n"
        
        # Добавляем JavaScript для генерации PDF
        html_content += f"""
        </div>

        <!-- Подключаем библиотеку html2pdf.js через CDN с резервным вариантом -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script>
            // Функция для показа/скрытия индикатора загрузки
            function showLoading(show) {{
                document.getElementById('loading').classList.toggle('active', show);
            }}

            // Функция для скачивания PDF
            function downloadAsPDF() {{
                const element = document.getElementById('report-content');
                const loading = document.getElementById('loading');
                
                // Показываем индикатор загрузки
                showLoading(true);
                
                // Настройки PDF
                const opt = {{
                    margin:       0.5,
                    filename:     'db_test_report_{timestamp}.pdf',
                    image:        {{ type: 'jpeg', quality: 0.98 }},
                    html2canvas:  {{ scale: 2, letterRendering: true, useCORS: true }},
                    jsPDF:        {{ unit: 'in', format: 'a4', orientation: 'portrait' }}
                }};
                
                // Сохраняем оригинальный заголовок
                const originalTitle = document.querySelector('h1').innerHTML;
                
                // Изменяем заголовок для PDF
                document.querySelector('h1').innerHTML = '🌸 Отчет тестирования БД';
                
                // Генерируем PDF
                try {{
                    html2pdf().set(opt).from(element).save()
                        .then(() => {{
                            // Восстанавливаем оригинальный заголовок
                            document.querySelector('h1').innerHTML = originalTitle;
                            showLoading(false);
                        }})
                        .catch((error) => {{
                            console.error('Ошибка при генерации PDF:', error);
                            document.querySelector('h1').innerHTML = originalTitle;
                            showLoading(false);
                            alert('Ошибка при генерации PDF. Попробуйте использовать кнопку "Печать".');
                        }});
                }} catch (error) {{
                    console.error('Ошибка:', error);
                    document.querySelector('h1').innerHTML = originalTitle;
                    showLoading(false);
                    alert('Ошибка при генерации PDF. Попробуйте использовать кнопку "Печать".');
                }}
            }}

            // Проверяем, загрузилась ли библиотека
            if (typeof html2pdf === 'undefined') {{
                console.warn('Библиотека html2pdf.js не загружена. Загружаем альтернативный вариант...');
                
                // Пробуем загрузить с другого CDN
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/html2pdf.js@0.10.1/dist/html2pdf.bundle.min.js';
                script.onload = function() {{
                    console.log('html2pdf.js успешно загружен с резервного CDN');
                }};
                script.onerror = function() {{
                    console.error('Не удалось загрузить html2pdf.js');
                    document.querySelector('.download-btn').disabled = true;
                    document.querySelector('.download-btn').style.opacity = '0.5';
                    document.querySelector('.download-btn').title = 'Библиотека не загрузилась. Используйте кнопку Печать.';
                }};
                document.head.appendChild(script);
            }}

            // Добавляем обработчик для печати
            window.onbeforeprint = function() {{
                // Можно добавить специальные стили для печати
            }};
        </script>

        <!-- Альтернативный способ: если html2pdf не работает, показываем сообщение -->
        <noscript>
            <div style="background: #f56565; color: white; padding: 10px; text-align: center; margin-top: 20px;">
                Для скачивания PDF включите JavaScript или используйте кнопку "Печать"
            </div>
        </noscript>
    </body>
    </html>
    """
        
        with open(filename, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        print(f"{Colors.GREEN}✅ HTML отчет создан: {filename}{Colors.END}")
        print(f"{Colors.BLUE}📊 Откройте файл в браузере: {filename}{Colors.END}")
        print(f"{Colors.BLUE}🖱️ Нажмите кнопку 'Скачать PDF' для сохранения отчета{Colors.END}")
        
        return filename

    def main():
        """Главная функция"""
        print(f"{Colors.BOLD}{Colors.HEADER}")
        print("🌸 ТЕСТИРОВАНИЕ ПОДКЛЮЧЕНИЯ К БАЗАМ ДАННЫХ КАВАЙ МАГАЗИНА 🌸")
        print(f"{Colors.END}")
        
        # Проверка наличия mysql.connector
        if not MYSQL_AVAILABLE:
            print(f"\n{Colors.YELLOW}Попробуйте установить библиотеку:{Colors.END}")
            print(f"{Colors.GREEN}pip install mysql-connector-python{Colors.END}")
            sys.exit(1)
        
        # Создание и запуск тестов
        tester = DatabaseTester()
        
        try:
            tester.run_all_tests()
            print(f"\n{Colors.GREEN}{Colors.BOLD}✅ Тестирование завершено!{Colors.END}")
            
        except KeyboardInterrupt:
            print(f"\n{Colors.YELLOW}⚠️ Тестирование прервано пользователем{Colors.END}")
        except Exception as e:
            print(f"\n{Colors.RED}❌ Критическая ошибка: {e}{Colors.END}")
            import traceback
            traceback.print_exc()

    if __name__ == "__main__":
        main()