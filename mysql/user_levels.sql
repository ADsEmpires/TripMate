-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 04:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `tripmate`
--

-- --------------------------------------------------------

--
-- Table structure for table `user_levels`
--

CREATE TABLE `user_levels` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `level` enum('normal','high') DEFAULT 'normal',
  `achievements` text DEFAULT NULL,
  `destinations_added` int(11) DEFAULT 0,
  `tasks_completed` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_levels`
--

INSERT INTO `user_levels` (`id`, `user_id`, `level`, `achievements`, `destinations_added`, `tasks_completed`, `created_at`, `updated_at`) VALUES
(1, 1, 'high', 'Top Contributor, Destination Expert, Adventure Seeker', 5, 12, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(2, 2, 'high', 'Travel Guru, Community Leader, Photography Master', 8, 20, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(3, 5, 'high', 'Review Master, Local Guide, Culture Expert', 3, 8, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(4, 10, 'high', 'Explorer Pro, Photo Contributor, Trip Planner', 7, 15, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(5, 101, 'high', 'New High Level User, Quick Learner, Active Member', 2, 5, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(6, 3, 'normal', 'New Explorer', 1, 2, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(7, 4, 'normal', 'Casual Traveler', 0, 1, '2025-08-31 19:55:18', '2025-08-31 19:55:18'),
(8, 6, 'normal', 'Weekend Adventurer', 2, 3, '2025-08-31 19:55:18', '2025-08-31 19:55:18');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `user_levels`
--
ALTER TABLE `user_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD CONSTRAINT `user_levels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
