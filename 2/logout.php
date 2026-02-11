<?php
require 'config.php';

// Уничтожаем сессию
$_SESSION = [];
session_destroy();

// Перенаправляем на страницу входа
header("Location: login.php");
exit;
?>