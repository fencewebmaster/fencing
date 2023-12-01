-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 01, 2023 at 05:31 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fencing_calculator`
--

-- --------------------------------------------------------

--
-- Table structure for table `fc_planners`
--

CREATE TABLE `fc_planners` (
  `id` int(16) NOT NULL,
  `planner_id` varchar(64) NOT NULL,
  `site_id` int(8) NOT NULL,
  `site_url` varchar(128) NOT NULL,
  `section_count` int(16) NOT NULL,
  `notes` text NOT NULL,
  `name` varchar(64) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `address` varchar(512) NOT NULL,
  `fence_type` varchar(512) NOT NULL,
  `timeframe` varchar(64) NOT NULL,
  `installer` varchar(64) NOT NULL,
  `extra` varchar(512) NOT NULL,
  `color_data` text NOT NULL,
  `products_data` text NOT NULL,
  `fence_data` text NOT NULL,
  `cart_data` text NOT NULL,
  `cart_items_data` text NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `fc_planners`
--
ALTER TABLE `fc_planners`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `fc_planners`
--
ALTER TABLE `fc_planners`
  MODIFY `id` int(16) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
