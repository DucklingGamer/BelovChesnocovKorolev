<?php
// Простой скрипт для первоначальной настройки
echo "<h2>🌸 Настройка системы Кавай Магазина 🍥</h2>";

// Проверяем наличие файлов
$files = ['config.php', 'login.php', 'logout.php', 'index.php', 'style.css', 'theme.js'];
$all_files_exist = true;

foreach ($files as $file) {
    if (!file_exists($file)) {
        echo "<p style='color: red;'>❌ Отсутствует файл: $file</p>";
        $all_files_exist = false;
    } else {
        echo "<p style='color: green;'>✅ Файл найден: $file</p>";
    }
}

if ($all_files_exist) {
    echo "<hr>";
    echo "<h3>Следующие шаги:</h3>";
    echo "<ol>";
    echo "<li><a href='register.php'>Зарегистрировать первого пользователя</a></li>";
    echo "<li><a href='login.php'>Войти в систему</a></li>";
    echo "<li><a href='check_database.php'>Проверить подключение к основной БД</a></li>";
    echo "</ol>";
} else {
    echo "<p style='color: red;'><strong>Не все файлы найдены. Загрузите все файлы системы.</strong></p>";
}
?>