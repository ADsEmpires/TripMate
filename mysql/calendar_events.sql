-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 04, 2025 at 04:27 AM
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
-- Table structure for table `calendar_events`
--

CREATE TABLE `calendar_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `event_time` time DEFAULT NULL,
  `event_type` enum('meeting','task','reminder','event') DEFAULT 'event',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `calendar_events`
--

INSERT INTO `calendar_events` (`id`, `title`, `description`, `event_date`, `event_time`, `event_type`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Weekly Team Meeting', 'Team sync for project updates', '2025-08-30', '10:00:00', 'meeting', 1, '2025-08-28 20:24:11', '2025-08-28 20:24:11'),
(2, 'System Maintenance', 'Monthly server maintenance window', '2025-09-02', '02:00:00', 'task', 1, '2025-08-28 20:24:11', '2025-08-28 20:24:11'),
(3, 'New Feature Launch', 'Launch of user profile enhancements', '2025-09-07', '00:00:00', 'event', 1, '2025-08-28 20:24:11', '2025-08-28 20:24:11'),
(4, 'Client Workshop', 'Presentation for new client', '2025-09-04', '14:30:00', 'meeting', 1, '2025-08-28 20:24:11', '2025-08-28 20:24:11'),
(5, 'Code Review', 'Review of new authentication system', '2025-08-31', '11:00:00', 'task', 1, '2025-08-28 20:24:11', '2025-08-28 20:24:11');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
