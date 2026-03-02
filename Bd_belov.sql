-- phpMyAdmin SQL Dump
-- version 5.1.3-3.red80
-- https://www.phpmyadmin.net/
--
-- Хост: localhost
-- Время создания: Мар 02 2026 г., 03:11
-- Версия сервера: 10.11.11-MariaDB
-- Версия PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `Bd_belov`
--

-- --------------------------------------------------------

--
-- Структура таблицы `clients`
--

CREATE TABLE `clients` (
  `client_id` int(11) NOT NULL,
  `full_name` varchar(200) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `registration_date` timestamp NULL DEFAULT current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Структура таблицы `site_users`
--

CREATE TABLE `site_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email_confirmed` tinyint(1) DEFAULT 0,
  `phone_confirmed` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `site_users`
--

INSERT INTO `site_users` (`id`, `username`, `password_hash`, `email`, `phone`, `email_confirmed`, `phone_confirmed`, `created_at`) VALUES
(1, 'Плотный', '$2y$10$zgNw4QfrRhNPVf.cWsvRheZHHZ81uHbPLCPsx2RO4IQ.UG0cZ58yG', 'aboba@gmail.com', '+7 983 210 49 91', 0, 0, '2026-02-16 04:50:02'),
(2, 'Plotniy', '$2y$10$iOta3F/M62OEuCRggW2/k.FhYVYy7pXO32EAfAimg8fem.rKhZDZy', 'obab@gmai.com', '+79234209875', 0, 0, '2026-02-16 05:06:23'),
(3, 'Kiril', '$2y$10$6lLIuQKc.BHhr.xYmRe7zOS7YI3qL9JKUw9OkzpxwbTNWKCp.rIAm', 'Kiril@gmail.com', '3094859043858', 0, 0, '2026-02-25 03:11:42'),
(4, '123321', '$2y$10$Pko34EJT21hb1pMh7b1LSe7hUJg9Br/aQtj75ph.2rJQoXnmu9kpy', '123321@123.ru', '123321', 0, 0, '2026-02-25 04:01:02'),
(5, '456456', '$2y$10$VPZOw1CIYu10gTBLE5BWzOcFjLULbhA5YUjgH0cCZ8KZyZErXmaj6', '123321@12.ru', '456456', 0, 0, '2026-02-25 04:49:58');

-- --------------------------------------------------------

--
-- Структура таблицы `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `remote_db_user` varchar(50) DEFAULT 'Chesnokov',
  `remote_db_password` varchar(255) DEFAULT 'CAaSRQUQ/5qvp29f',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Дамп данных таблицы `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `remote_db_user`, `remote_db_password`, `created_at`) VALUES
(1, 'AdmMat', '$2y$10$5rH2Z1nRqX/M7Rp.KMt88.35Ya3P/Wy7ERXPqJuZdbWBc13MuKPVm', 'Chesnokov', 'CAaSRQUQ/5qvp29f', '2026-02-16 04:51:28'),
(2, 'Kiril', '$2y$10$T8bYz2jlIbgNNan1MnVWmeC6iVX8mnc1JWPNMyvJmQ0pM6SFRJV7y', 'Chesnokov', 'CAaSRQUQ/5qvp29f', '2026-02-25 02:09:01'),
(3, '123123123', '$2y$10$3fjO76gv.0DXKGqWBvdOZO2QvnWO2iR/muFG2ckxBDOXr2MdmHWyq', 'Chesnokov', 'CAaSRQUQ/5qvp29f', '2026-02-25 03:59:18'),
(4, '321123', '$2y$10$FLy1bx1wVdaVqcDH2uvcr.4ylyElpRGmSL6I13tlxeFD9Fw.HqYQy', 'Chesnokov', 'CAaSRQUQ/5qvp29f', '2026-02-25 04:41:18'),
(5, '456456', '$2y$10$sEopsVszdGiadoNahJyBAO7tY0n9lDSi53ToqAakyIwkQ746QN4XW', 'Chesnokov', 'CAaSRQUQ/5qvp29f', '2026-02-25 04:47:54');

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `site_users`
--
ALTER TABLE `site_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Индексы таблицы `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT для сохранённых таблиц
--

--
-- AUTO_INCREMENT для таблицы `clients`
--
ALTER TABLE `clients`
  MODIFY `client_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT для таблицы `site_users`
--
ALTER TABLE `site_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT для таблицы `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
