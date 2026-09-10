-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Isäntä: db
-- Luontiaika: 10.09.2026 klo 10:50
-- Palvelimen versio: 8.0.46
-- PHP-versio 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Tietokanta: `taloushallinto`
--

-- --------------------------------------------------------

--
-- Rakenne taululle `transactions`
--

CREATE TABLE `transactions` (
  `id` int NOT NULL,
  `date` date NOT NULL,
  `type` enum('income','expense') NOT NULL,
  `category` enum('income','general_expense','travel','phone_data') NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `vat_rate` decimal(5,2) DEFAULT '0.00',
  `vat_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `transactions`
--

INSERT INTO `transactions` (`id`, `date`, `type`, `category`, `description`, `amount`, `vat_rate`, `vat_amount`, `created_at`) VALUES
(1, '2023-01-15', 'income', 'income', 'Sales revenue', 5000.00, 24.00, 1200.00, '2026-09-09 07:03:01'),
(2, '2023-01-20', 'expense', 'general_expense', 'Office supplies', 200.00, 24.00, 48.00, '2026-09-09 07:03:01'),
(3, '2023-01-25', 'expense', 'travel', 'Business trip to Helsinki', 150.00, 0.00, 0.00, '2026-09-09 07:03:01'),
(4, '2023-02-10', 'expense', 'phone_data', 'Monthly phone bill', 50.00, 24.00, 12.00, '2026-09-09 07:03:01'),
(5, '2023-03-05', 'income', 'income', 'Consulting services', 3000.00, 24.00, 720.00, '2026-09-09 07:03:01'),
(6, '2023-03-12', 'expense', 'general_expense', 'Software licenses', 150.00, 24.00, 36.00, '2026-09-09 07:03:01'),
(7, '2023-04-01', 'expense', 'travel', 'Client meeting expenses', 80.00, 0.00, 0.00, '2026-09-09 07:03:01'),
(8, '2023-04-15', 'expense', 'phone_data', 'Internet bill', 40.00, 24.00, 9.60, '2026-09-09 07:03:01');

-- --------------------------------------------------------

--
-- Rakenne taululle `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Vedos taulusta `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `created_at`) VALUES
(1, 'admin', '123', '2026-09-10 09:26:20');

--
-- Indexes for dumped tables
--

--
-- Indeksit taulukolle `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_category` (`category`);

--
-- Indeksit taulukolle `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
