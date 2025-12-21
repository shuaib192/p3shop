-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Dec 21, 2025 at 04:28 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.1.17

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `p3shop`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Biscuits'),
(2, 'Drink'),
(3, 'Energy Drink');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `quantity_in_stock` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `category_id` int(11) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `units_per_pack` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `quantity_in_stock`, `price`, `created_at`, `category_id`, `cost_price`, `units_per_pack`) VALUES
(3, 'BEES CRUNCHY', 'Biscuits', 8, 250.00, '2025-12-01 12:01:53', 1, 175.00, 1),
(4, 'Short Bread', 'Biscuits', 0, 350.00, '2025-12-01 12:02:42', 1, 245.00, 1),
(6, 'Digestive', 'Biscuits', 16, 300.00, '2025-12-01 12:03:55', 1, 210.00, 1),
(7, 'MunchKins', 'Biscuits', 3, 500.00, '2025-12-01 12:05:02', 1, 350.00, 1),
(8, 'Eggrich', 'Biscuits', 25, 100.00, '2025-12-01 12:06:09', 1, 70.00, 1),
(9, 'Butter Cookies', 'Biscuits', 0, 100.00, '2025-12-01 12:09:28', 1, 70.00, 1),
(10, 'FANTA', 'Drink', 1, 450.00, '2025-12-01 12:28:49', 2, 315.00, 1),
(11, 'FEARLESS', 'Energy Drink', 0, 500.00, '2025-12-01 12:29:16', 3, 350.00, 1),
(12, 'COKE', 'Drink', 0, 450.00, '2025-12-01 12:29:49', 2, 315.00, 1),
(13, 'NUTRI - MILK', 'Drink', 0, 600.00, '2025-12-01 12:30:14', 2, 420.00, 1),
(14, 'BOTTLE WATER', 'Drink', 1, 150.00, '2025-12-01 12:30:39', 2, 105.00, 1),
(15, 'BOTTLE WATER2', 'Drink', 3, 150.00, '2025-12-10 15:37:57', 2, 105.00, 1);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `sale_date` date NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'Cash',
  `cost_price` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `product_id`, `user_id`, `quantity_sold`, `total_price`, `sale_date`, `payment_method`, `cost_price`) VALUES
(6, 3, 4, 1, 250.00, '2025-12-02', 'Cash', 175.00),
(7, 13, 4, 1, 600.00, '2025-12-03', 'Transfer', 420.00),
(8, 12, 6, 4, 1800.00, '2025-12-04', 'Transfer', 315.00),
(9, 4, 6, 7, 2450.00, '2025-12-04', 'Transfer', 245.00),
(10, 3, 6, 1, 250.00, '2025-12-04', 'Transfer', 175.00),
(11, 11, 6, 3, 1500.00, '2025-12-04', 'Transfer', 350.00),
(12, 7, 6, 3, 1500.00, '2025-12-04', 'Cash', 350.00),
(13, 8, 6, 4, 400.00, '2025-12-04', 'Cash', 70.00),
(14, 9, 6, 14, 1400.00, '2025-12-04', 'Cash', 70.00),
(15, 14, 6, 3, 450.00, '2025-12-04', 'Cash', 105.00),
(16, 13, 6, 1, 600.00, '2025-12-04', 'Cash', 420.00),
(17, 10, 6, 1, 450.00, '2025-12-04', 'Cash', 315.00),
(18, 13, 6, 1, 600.00, '2025-12-04', 'Transfer', 420.00),
(19, 14, 6, 3, 450.00, '2025-12-06', 'Transfer', 105.00),
(20, 12, 6, 1, 450.00, '2025-12-06', 'Transfer', 315.00),
(21, 7, 6, 2, 1000.00, '2025-12-06', 'Transfer', 350.00),
(22, 11, 6, 2, 1000.00, '2025-12-06', 'Transfer', 350.00),
(23, 10, 6, 1, 450.00, '2025-12-06', 'Transfer', 315.00),
(24, 9, 6, 6, 600.00, '2025-12-06', 'Transfer', 70.00),
(25, 14, 6, 5, 750.00, '2025-12-06', 'Cash', 105.00),
(26, 12, 6, 2, 900.00, '2025-12-06', 'Cash', 315.00),
(27, 4, 6, 1, 350.00, '2025-12-06', 'Cash', 245.00),
(28, 14, 6, 1, 150.00, '2025-12-06', 'Cash', 105.00),
(29, 14, 6, 3, 450.00, '2025-12-06', 'Transfer', 105.00),
(30, 7, 6, 1, 500.00, '2025-12-06', 'Transfer', 350.00),
(31, 11, 6, 1, 500.00, '2025-12-06', 'Transfer', 350.00),
(32, 10, 6, 1, 450.00, '2025-12-06', 'Transfer', 315.00),
(33, 6, 6, 1, 300.00, '2025-12-06', 'Transfer', 210.00),
(34, 13, 6, 1, 600.00, '2025-12-06', 'Transfer', 420.00),
(35, 8, 6, 3, 300.00, '2025-12-06', 'Transfer', 70.00),
(36, 12, 6, 1, 450.00, '2025-12-06', 'Cash', 315.00),
(37, 3, 6, 2, 500.00, '2025-12-07', 'Transfer', 175.00),
(38, 7, 6, 1, 500.00, '2025-12-07', 'Transfer', 350.00),
(39, 12, 6, 3, 1350.00, '2025-12-07', 'Transfer', 315.00),
(40, 9, 6, 2, 200.00, '2025-12-07', 'Cash', 70.00),
(41, 13, 6, 1, 600.00, '2025-12-07', 'Cash', 420.00),
(42, 10, 6, 1, 450.00, '2025-12-07', 'Transfer', 315.00),
(43, 14, 6, 4, 600.00, '2025-12-07', 'Transfer', 105.00),
(44, 4, 6, 2, 700.00, '2025-12-07', 'Transfer', 245.00),
(45, 11, 6, 1, 500.00, '2025-12-07', 'Transfer', 350.00),
(46, 4, 6, 2, 700.00, '2025-12-08', 'Transfer', 245.00),
(47, 4, 6, 1, 350.00, '2025-12-08', 'Cash', 245.00),
(48, 13, 6, 1, 600.00, '2025-12-08', 'Transfer', 420.00),
(49, 13, 6, 1, 600.00, '2025-12-08', 'Cash', 420.00),
(50, 7, 6, 1, 500.00, '2025-12-08', 'Transfer', 350.00),
(51, 6, 6, 1, 300.00, '2025-12-08', 'Cash', 210.00),
(52, 8, 6, 2, 200.00, '2025-12-08', 'Transfer', 70.00),
(53, 3, 6, 4, 1000.00, '2025-12-09', 'Transfer', 175.00),
(54, 10, 6, 4, 1800.00, '2025-12-09', 'Transfer', 315.00),
(55, 10, 6, 3, 1350.00, '2025-12-10', 'Transfer', 315.00),
(56, 12, 6, 1, 450.00, '2025-12-10', 'Transfer', 315.00),
(57, 4, 6, 2, 700.00, '2025-12-10', 'Cash', 245.00),
(58, 6, 6, 1, 300.00, '2025-12-10', 'Transfer', 210.00),
(59, 8, 6, 1, 100.00, '2025-12-10', 'Transfer', 70.00),
(60, 11, 6, 1, 500.00, '2025-12-10', 'Cash', 350.00),
(61, 9, 6, 3, 300.00, '2025-12-10', 'Transfer', 70.00),
(62, 15, 6, 1, 150.00, '2025-12-10', 'Transfer', 105.00),
(63, 15, 6, 1, 150.00, '2025-12-10', 'Transfer', 105.00),
(64, 15, 6, 1, 150.00, '2025-12-11', 'Cash', 105.00),
(65, 15, 6, 3, 450.00, '2025-12-11', 'Transfer', 105.00),
(66, 6, 6, 1, 300.00, '2025-12-11', 'Cash', 210.00),
(67, 4, 6, 1, 350.00, '2025-12-11', 'Cash', 245.00),
(68, 3, 6, 1, 250.00, '2025-12-11', 'Transfer', 175.00),
(69, 9, 6, 2, 200.00, '2025-12-11', 'Cash', 70.00),
(70, 13, 6, 1, 600.00, '2025-12-11', 'Transfer', 420.00),
(71, 15, 6, 2, 300.00, '2025-12-12', 'Cash', 105.00),
(72, 6, 6, 1, 300.00, '2025-12-12', 'Cash', 210.00),
(73, 11, 6, 1, 500.00, '2025-12-12', 'Transfer', 350.00),
(74, 7, 6, 1, 500.00, '2025-12-12', 'Transfer', 350.00),
(75, 13, 6, 1, 600.00, '2025-12-12', 'Transfer', 420.00),
(76, 9, 6, 2, 200.00, '2025-12-12', 'Cash', 70.00),
(77, 15, 6, 1, 150.00, '2025-12-12', 'Transfer', 105.00),
(78, 15, 6, 1, 150.00, '2025-12-13', 'Card', 105.00),
(79, 9, 6, 4, 400.00, '2025-12-13', 'Cash', 70.00),
(80, 9, 6, 3, 300.00, '2025-12-13', 'Transfer', 70.00),
(81, 4, 6, 1, 350.00, '2025-12-13', 'Card', 245.00),
(82, 8, 6, 1, 100.00, '2025-12-13', 'Transfer', 70.00),
(83, 7, 6, 1, 500.00, '2025-12-13', 'Cash', 350.00),
(84, 15, 6, 3, 450.00, '2025-12-16', 'Transfer', 105.00),
(85, 4, 6, 3, 1050.00, '2025-12-16', 'Cash', 245.00),
(86, 13, 6, 1, 600.00, '2025-12-16', 'Transfer', 420.00),
(87, 7, 6, 1, 500.00, '2025-12-16', 'Transfer', 350.00),
(88, 15, 6, 1, 150.00, '2025-12-16', 'Cash', 105.00),
(89, 7, 6, 5, 2500.00, '2025-12-16', 'Transfer', 350.00),
(90, 6, 6, 3, 900.00, '2025-12-16', 'Transfer', 210.00),
(91, 3, 6, 4, 1000.00, '2025-12-16', 'Transfer', 175.00),
(92, 15, 6, 3, 450.00, '2025-12-16', 'Cash', 105.00),
(93, 4, 6, 1, 350.00, '2025-12-16', 'Cash', 245.00),
(94, 7, 6, 2, 1000.00, '2025-12-17', 'Transfer', 350.00),
(95, 13, 6, 1, 600.00, '2025-12-17', 'Cash', 420.00),
(96, 3, 6, 1, 250.00, '2025-12-17', 'Cash', 175.00),
(97, 13, 6, 1, 600.00, '2025-12-17', 'Transfer', 420.00),
(98, 4, 6, 2, 700.00, '2025-12-17', 'Transfer', 245.00),
(99, 4, 6, 1, 350.00, '2025-12-17', 'Cash', 245.00),
(100, 3, 6, 1, 250.00, '2025-12-17', 'Transfer', 175.00),
(101, 11, 6, 1, 500.00, '2025-12-17', 'Cash', 350.00),
(102, 9, 6, 3, 300.00, '2025-12-17', 'Cash', 70.00),
(103, 11, 6, 1, 500.00, '2025-12-18', 'Cash', 350.00),
(104, 7, 6, 1, 500.00, '2025-12-18', 'Transfer', 350.00),
(105, 9, 6, 5, 500.00, '2025-12-18', 'Cash', 70.00),
(106, 3, 6, 1, 250.00, '2025-12-18', 'Cash', 175.00),
(107, 11, 6, 1, 500.00, '2025-12-19', 'Cash', 350.00),
(108, 7, 6, 2, 1000.00, '2025-12-19', 'Transfer', 350.00),
(109, 9, 6, 4, 400.00, '2025-12-19', 'Transfer', 70.00);

-- --------------------------------------------------------

--
-- Table structure for table `stock_log`
--

CREATE TABLE `stock_log` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `log_timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `stock_log`
--

INSERT INTO `stock_log` (`id`, `product_id`, `user_id`, `quantity_change`, `reason`, `log_timestamp`) VALUES
(1, 3, 1, 24, 'Initial stock for new product', '2025-12-01 12:01:53'),
(2, 4, 1, 24, 'Initial stock for new product', '2025-12-01 12:02:42'),
(4, 6, 1, 24, 'Initial stock for new product', '2025-12-01 12:03:55'),
(5, 7, 1, 24, 'Initial stock for new product', '2025-12-01 12:05:02'),
(6, 8, 1, 36, 'Initial stock for new product', '2025-12-01 12:06:09'),
(7, 9, 1, 48, 'Initial stock for new product', '2025-12-01 12:09:28'),
(8, 10, 1, 12, 'Initial stock for new product', '2025-12-01 12:28:49'),
(9, 11, 1, 12, 'Initial stock for new product', '2025-12-01 12:29:16'),
(10, 12, 1, 12, 'Initial stock for new product', '2025-12-01 12:29:49'),
(11, 13, 1, 12, 'Initial stock for new product', '2025-12-01 12:30:14'),
(12, 14, 1, 20, 'Initial stock for new product', '2025-12-01 12:30:39'),
(13, 15, 1, 20, 'Initial stock for new product', '2025-12-10 15:37:57');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$iXkIG5q0sczTQxMqmUyFzuJggc3lplKiaFl8XL2HT.Gwt1QW8uzOG', 'admin', '2025-11-16 16:57:22'),
(4, 'staff1', '$2y$10$vd5oyzuWK900bB0SeET8u.Zp3LEY1OtPtlG/GjfAQOXx.GacIfORu', 'staff', '2025-12-02 16:54:51'),
(5, 'MAIMUNA', '$2y$10$8PHOyrLa4TwMigEnAZKIouk72ZwksZ9FHS4zMhkJa2FQcRSub5PuK', 'staff', '2025-12-02 17:04:08'),
(6, 'SADIYA', '$2y$10$4mylHE11bwvWcinOkJ6lvuTHow8kbDMxONSR7VrtTv3WNplq43hXK', 'staff', '2025-12-02 17:04:25'),
(7, 'staff2', '$2y$10$lE41bo8vfUMingLFveDoWOMJLfZF9mdFzEg0PHZycp44py1FNAFdy', 'staff', '2025-12-20 10:46:55');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `stock_log`
--
ALTER TABLE `stock_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=110;

--
-- AUTO_INCREMENT for table `stock_log`
--
ALTER TABLE `stock_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
