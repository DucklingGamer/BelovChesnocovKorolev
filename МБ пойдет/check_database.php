<?php
require 'config.php';

// Проверка и создание таблиц если их нет
function checkDatabase() {
    global $pdo;
    
    echo "<style>
        body { font-family: 'Nunito', sans-serif; padding: 20px; background: #fff0f3; }
        .check { padding: 15px; margin: 10px 0; border-radius: 10px; }
        .ok { background: #d4edda; color: #155724; border-left: 5px solid #48bb78; }
        .error { background: #f8d7da; color: #721c24; border-left: 5px solid #ff4d6d; }
        .warning { background: #fff3cd; color: #856404; border-left: 5px solid #ffb703; }
        h1, h2 { color: #ff4d6d; }
        .sql { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 25px; text-decoration: none; color: white; font-weight: bold; }
    </style>";
    
    echo "<h1>🌸 Проверка базы данных Кавай Магазина 🍥</h1>";
    
    // 1. Проверка таблицы categories
    echo "<h2>1. Таблица categories</h2>";
    try {
        $pdo->query("SELECT 1 FROM categories LIMIT 1");
        echo "<div class='check ok'>✅ Таблица categories существует</div>";
        
        $count = $pdo->query("SELECT COUNT(*) as cnt FROM categories")->fetch()['cnt'];
        echo "<div class='check " . ($count > 0 ? 'ok' : 'warning') . "'>";
        echo $count > 0 ? "✅" : "⚠️";
        echo " Записей в таблице: $count</div>";
        
        if ($count == 0) {
            // Создаем демо-категории
            $demo_categories = [
                ['Фигурки', 'Коллекционные аниме-фигурки'],
                ['Игрушки', 'Мягкие игрушки и плюшевые персонажи'],
                ['Косплей', 'Парики, костюмы и аксессуары'],
                ['Манга', 'Японские комиксы и графические романы'],
                ['Постеры', 'Плакаты и постеры с аниме'],
                ['Аксессуары', 'Кружки, значки, наклейки']
            ];
            
            foreach ($demo_categories as $category) {
                $stmt = $pdo->prepare("INSERT INTO categories (category_name, description) VALUES (?, ?)");
                $stmt->execute([$category[0], $category[1]]);
            }
            echo "<div class='check ok'>✅ Создано демо-категорий: " . count($demo_categories) . "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='check error'>❌ Таблица categories не существует: " . $e->getMessage() . "</div>";
        
        // Создаем таблицу
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
                category_id INT PRIMARY KEY AUTO_INCREMENT,
                category_name VARCHAR(100) NOT NULL,
                description TEXT,
                parent_category_id INT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            echo "<div class='check ok'>✅ Таблица categories создана</div>";
        } catch (Exception $e2) {
            echo "<div class='check error'>❌ Не удалось создать таблицу: " . $e2->getMessage() . "</div>";
        }
    }
    
    // 2. Проверка таблицы products
    echo "<h2>2. Таблица products</h2>";
    try {
        $result = $pdo->query("DESCRIBE products");
        $columns = $result->fetchAll();
        
        echo "<div class='check ok'>✅ Таблица products существует</div>";
        
        // Проверка наличия поля category_id
        $has_category_id = false;
        foreach ($columns as $col) {
            if ($col['Field'] == 'category_id') {
                $has_category_id = true;
                break;
            }
        }
        
        if ($has_category_id) {
            echo "<div class='check ok'>✅ Поле category_id существует</div>";
        } else {
            echo "<div class='check error'>❌ Поле category_id отсутствует!</div>";
            try {
                $pdo->exec("ALTER TABLE products ADD COLUMN category_id INT");
                echo "<div class='check ok'>✅ Поле category_id добавлено</div>";
            } catch (Exception $e) {
                echo "<div class='check error'>❌ Не удалось добавить поле: " . $e->getMessage() . "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='check error'>❌ Таблица products не существует: " . $e->getMessage() . "</div>";
        
        // Создаем таблицу
        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS products (
                product_id INT PRIMARY KEY AUTO_INCREMENT,
                product_name VARCHAR(200) NOT NULL,
                description TEXT,
                price DECIMAL(10, 2) NOT NULL,
                stock_quantity INT DEFAULT 0,
                category_id INT,
                is_available BOOLEAN DEFAULT true,
                image_url VARCHAR(500),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
            )");
            echo "<div class='check ok'>✅ Таблица products создана</div>";
        } catch (Exception $e2) {
            echo "<div class='check error'>❌ Не удалось создать таблицу: " . $e2->getMessage() . "</div>";
        }
    }
    
    // 3. Проверка связи
    echo "<h2>3. Связь между таблицами</h2>";
    try {
        $result = $pdo->query("
            SELECT p.product_id, p.product_name, c.category_id, c.category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            LIMIT 5
        ");
        $sample = $result->fetchAll();
        
        echo "<div class='check ok'>✅ Связь работает корректно</div>";
        
        if (!empty($sample)) {
            echo "<div class='sql'>Пример связи:<br>";
            foreach ($sample as $row) {
                echo "• {$row['product_name']} → ";
                if ($row['category_name']) {
                    echo "<span style='color: green;'>{$row['category_name']}</span>";
                } else {
                    echo "<span style='color: red;'>Без категории</span>";
                }
                echo "<br>";
            }
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='check error'>❌ Ошибка связи: " . $e->getMessage() . "</div>";
    }
    
    echo "<hr>";
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='categories.php' class='btn' style='background: #ff4d6d;'>🏷️ Перейти к категориям</a>";
    echo "<a href='products.php' class='btn' style='background: #cdb4db;'>📦 Перейти к товарам</a>";
    echo "<a href='index.php' class='btn' style='background: #a2d2ff;'>🏠 На главную</a>";
    echo "</div>";
}

checkDatabase();
?>