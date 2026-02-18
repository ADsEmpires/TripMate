-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 09, 2025 at 09:05 PM
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
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `profile_pic` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `name`, `email`, `password`, `created_at`, `profile_pic`) VALUES
(1, 'ronI', 'admin@gmail.com', '$2y$10$pB35ZWhGYt7rDSwCBDOSKu.bQFx9OcvSVzP4fr3/MnuOtjNHyBOF6', '2025-08-05 19:01:34', 'uploads/admin_profile_1_1761409612.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `appearance_settings`
--

CREATE TABLE `appearance_settings` (
  `id` int(11) NOT NULL,
  `theme` varchar(20) DEFAULT 'light',
  `primary_color` varchar(7) DEFAULT '#2563eb',
  `secondary_color` varchar(7) DEFAULT '#10b981',
  `logo_url` varchar(500) DEFAULT NULL,
  `favicon_url` varchar(500) DEFAULT NULL,
  `custom_css` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appearance_settings`
--

INSERT INTO `appearance_settings` (`id`, `theme`, `primary_color`, `secondary_color`, `logo_url`, `favicon_url`, `custom_css`, `updated_at`) VALUES
(1, 'light', '#2563eb', '#10b981', NULL, NULL, NULL, '2025-10-17 19:29:30');

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

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('beach','mountain','city','village','historical','religious') NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `budget` decimal(10,2) DEFAULT NULL COMMENT 'Average cost per person per day',
  `best_season` varchar(50) DEFAULT NULL,
  `image_urls` text DEFAULT NULL COMMENT 'JSON array of image URLs',
  `map_link` varchar(255) DEFAULT NULL,
  `attractions` text DEFAULT NULL COMMENT 'JSON array of attractions',
  `hotels` text DEFAULT NULL COMMENT 'JSON array of nearby hotels',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `season` varchar(50) DEFAULT NULL,
  `people` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `type`, `description`, `location`, `budget`, `best_season`, `image_urls`, `map_link`, `attractions`, `hotels`, `created_at`, `updated_at`, `season`, `people`) VALUES
(4, 'Darjeeling Tea Gardens', 'mountain', 'temple king ', 'Darjeeling, West Bengal', 5000.00, NULL, '[\"uploads\\/68aeeb75c9390_688fbd477e712_Darjeeling Tea Gardens2.jpg\"]', 'https://maps.app.goo.gl/MpEhSRdiWNFcKYSk9', NULL, NULL, '2025-08-27 11:26:45', '2025-08-27 11:26:45', 'winter', 9);

-- --------------------------------------------------------

--
-- Table structure for table `email_settings`
--

CREATE TABLE `email_settings` (
  `id` int(11) NOT NULL,
  `smtp_host` varchar(255) DEFAULT 'smtp.gmail.com',
  `smtp_port` int(11) DEFAULT 587,
  `smtp_username` varchar(255) DEFAULT NULL,
  `smtp_password` varchar(255) DEFAULT NULL,
  `smtp_encryption` varchar(10) DEFAULT 'tls',
  `from_name` varchar(255) DEFAULT 'TripMate',
  `from_email` varchar(255) DEFAULT 'noreply@tripmate.com',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_settings`
--

INSERT INTO `email_settings` (`id`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_encryption`, `from_name`, `from_email`, `updated_at`) VALUES
(1, 'smtp.gmail.com', 587, NULL, NULL, 'tls', 'TripMate', 'noreply@tripmate.com', '2025-10-17 19:29:30');

-- --------------------------------------------------------

--
-- Table structure for table `errors`
--

CREATE TABLE `errors` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `errors`
--

INSERT INTO `errors` (`id`, `message`, `ip_address`, `user_id`, `created_at`) VALUES
(2, 'Error [2] Undefined variable $recent_errors in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 1585', '::1', 1, '2025-09-13 19:13:42'),
(3, 'Error [2] Undefined variable $top_error_ips in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 1623', '::1', 1, '2025-09-13 19:13:42'),
(4, 'Error [2] Undefined variable $recent_errors in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 1585', '::1', 1, '2025-09-13 19:36:45'),
(5, 'Error [2] Undefined variable $top_error_ips in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 1623', '::1', 1, '2025-09-13 19:36:45'),
(6, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\demo_das.php on line 1307', '::1', 1, '2025-09-14 18:23:09'),
(7, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\demo_das.php on line 1307', '::1', 1, '2025-09-14 18:23:11'),
(8, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\demo_das.php on line 1307', '::1', 1, '2025-09-14 18:23:11'),
(9, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\demo_das.php on line 1307', '::1', 1, '2025-09-14 18:23:12'),
(10, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\demo_das.php on line 1307', '::1', 1, '2025-09-14 18:26:25'),
(11, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\demo_das.php on line 1307', '::1', 1, '2025-09-14 18:32:05'),
(12, 'Error [2] Trying to access array offset on value of type null in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 304', '::1', 1, '2025-09-15 02:15:41'),
(13, 'Error [2] Undefined variable $bookings_table_exists in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 298', '::1', 1, '2025-09-15 02:16:57'),
(14, 'Error [2] Undefined variable $last_week_decline in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 1342', '::1', 1, '2025-09-15 02:16:57'),
(15, 'Error [2] Undefined variable $bookings_table_exists in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 298', '::1', 1, '2025-09-15 02:17:10'),
(16, 'Error [2] Undefined variable $bookings_table_exists in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 295', '::1', 1, '2025-09-15 02:17:19'),
(17, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=Darjeeling%2C+West+Bengal&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 223', '::1', 1, '2025-10-05 19:11:27'),
(18, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=KOLKATA&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 223', '::1', 1, '2025-10-05 19:11:30'),
(19, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=dfgdfgdfg&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 223', '::1', 1, '2025-10-05 19:11:33'),
(20, 'Error [2] file_get_contents(): php_network_getaddresses: getaddrinfo failed: This is usually a temporary error during hostname resolution and means that the local server did not receive a response from an authoritative server.  in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 238', '::1', 1, '2025-10-05 19:11:34'),
(21, 'Error [2] file_get_contents(https://api.quotable.io/random?tags=travel|adventure): Failed to open stream: php_network_getaddresses: getaddrinfo failed: This is usually a temporary error during hostname resolution and means that the local server did not receive a response from an authoritative server.  in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 238', '::1', 1, '2025-10-05 19:11:34'),
(22, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=Darjeeling%2C+West+Bengal&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 184', '::1', 1, '2025-10-05 19:29:24'),
(23, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=KOLKATA&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 184', '::1', 1, '2025-10-05 19:29:27'),
(24, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=dfgdfgdfg&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 184', '::1', 1, '2025-10-05 19:29:30'),
(25, 'Error [2] file_get_contents(): php_network_getaddresses: getaddrinfo failed: This is usually a temporary error during hostname resolution and means that the local server did not receive a response from an authoritative server.  in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 203', '::1', 1, '2025-10-05 19:29:31'),
(26, 'Error [2] file_get_contents(https://api.quotable.io/random?tags=travel|adventure): Failed to open stream: php_network_getaddresses: getaddrinfo failed: This is usually a temporary error during hostname resolution and means that the local server did not receive a response from an authoritative server.  in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 203', '::1', 1, '2025-10-05 19:29:31'),
(27, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=Darjeeling%2C+West+Bengal&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 206', '::1', 1, '2025-10-05 19:39:11'),
(28, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=KOLKATA&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 206', '::1', 1, '2025-10-05 19:39:14'),
(29, 'Error [2] file_get_contents(https://nominatim.openstreetmap.org/search?q=dfgdfgdfg&amp;format=json&amp;limit=1): Failed to open stream: HTTP request failed! HTTP/1.1 403 Forbidden\r\n in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 206', '::1', 1, '2025-10-05 19:39:17'),
(30, 'Error [2] file_get_contents(): php_network_getaddresses: getaddrinfo failed: This is usually a temporary error during hostname resolution and means that the local server did not receive a response from an authoritative server.  in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 236', '::1', 1, '2025-10-05 19:39:17'),
(31, 'Error [2] file_get_contents(https://api.quotable.io/random?tags=travel|adventure): Failed to open stream: php_network_getaddresses: getaddrinfo failed: This is usually a temporary error during hostname resolution and means that the local server did not receive a response from an authoritative server.  in C:\\xampp\\htdocs\\mejor_project\\updated upto 09.09.25\\tripmate\\admin\\admin_dasbord.php on line 236', '::1', 1, '2025-10-05 19:39:17');

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `message_type` enum('incoming','outgoing') DEFAULT 'incoming',
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `messages`
--

INSERT INTO `messages` (`id`, `user_id`, `subject`, `message`, `message_type`, `status`, `created_at`) VALUES
(1, 1, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(2, 2, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(3, 3, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(4, 4, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(5, 5, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(6, 6, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(7, 7, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(8, 8, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(9, 9, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(10, 10, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(11, 11, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(12, 12, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(13, 13, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(14, 14, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(15, 15, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(16, 16, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(17, 17, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(18, 18, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(19, 19, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(20, 20, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(21, 21, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(22, 22, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(23, 23, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(24, 24, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(25, 25, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(26, 26, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(27, 27, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(28, 28, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(29, 29, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(30, 30, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(31, 31, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(32, 32, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(33, 33, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(34, 34, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(35, 35, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(36, 36, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(37, 37, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(38, 38, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(39, 39, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(40, 40, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(41, 41, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(42, 42, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(43, 43, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(44, 44, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(45, 45, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(46, 46, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(47, 47, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(48, 48, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(49, 49, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(50, 50, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(51, 51, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(52, 52, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(53, 53, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(54, 54, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(55, 55, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(56, 56, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(57, 57, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(58, 58, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(59, 59, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(60, 60, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(61, 61, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(62, 62, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(63, 63, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(64, 64, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(65, 65, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(66, 66, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(67, 67, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(68, 68, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(69, 69, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(70, 70, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(71, 71, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(72, 72, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(73, 73, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(74, 74, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(75, 75, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(76, 76, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(77, 77, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(78, 78, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(79, 79, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(80, 80, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(81, 81, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(82, 82, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(83, 83, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(84, 84, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(85, 85, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(86, 86, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(87, 87, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(88, 88, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(89, 89, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(90, 90, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(91, 91, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(92, 92, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(93, 93, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(94, 94, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(95, 95, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(96, 96, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(97, 97, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(98, 98, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(99, 99, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(100, 100, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(101, 101, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(102, 105, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(103, 108, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(104, 109, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(105, 110, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(106, 111, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(107, 112, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(108, 113, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(109, 114, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(110, 115, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(111, 116, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(112, 117, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(113, 118, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(114, 119, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(115, 120, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(116, 121, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(117, 122, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(118, 123, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(119, 124, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(120, 125, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(121, 126, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(122, 127, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(123, 128, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(124, 129, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(125, 130, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(126, 131, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(127, 132, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(128, 133, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(129, 134, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(130, 135, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(131, 136, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(132, 137, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(133, 138, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(134, 139, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(135, 140, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(136, 141, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(137, 142, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(138, 143, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(139, 144, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(140, 145, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(141, 146, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(142, 147, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(143, 148, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(144, 149, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(145, 150, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(146, 151, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(147, 152, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(148, 153, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(149, 154, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(150, 155, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(151, 156, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(152, 157, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(153, 158, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(154, 159, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(155, 160, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(156, 161, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(157, 162, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(158, 163, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(159, 164, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(160, NULL, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:06'),
(161, 1, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(162, 2, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(163, 3, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(164, 4, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(165, 5, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(166, 6, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(167, 7, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(168, 8, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(169, 9, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(170, 10, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(171, 11, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(172, 12, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(173, 13, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(174, 14, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(175, 15, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(176, 16, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(177, 17, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(178, 18, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(179, 19, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(180, 20, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(181, 21, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(182, 22, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(183, 23, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(184, 24, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(185, 25, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(186, 26, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(187, 27, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(188, 28, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(189, 29, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(190, 30, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(191, 31, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(192, 32, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(193, 33, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(194, 34, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(195, 35, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(196, 36, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(197, 37, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(198, 38, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(199, 39, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(200, 40, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(201, 41, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(202, 42, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(203, 43, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(204, 44, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(205, 45, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(206, 46, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(207, 47, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(208, 48, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(209, 49, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(210, 50, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(211, 51, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(212, 52, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(213, 53, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(214, 54, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(215, 55, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(216, 56, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(217, 57, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(218, 58, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(219, 59, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(220, 60, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(221, 61, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(222, 62, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(223, 63, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(224, 64, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(225, 65, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(226, 66, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(227, 67, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(228, 68, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(229, 69, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(230, 70, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(231, 71, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(232, 72, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(233, 73, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(234, 74, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(235, 75, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(236, 76, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(237, 77, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(238, 78, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(239, 79, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(240, 80, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(241, 81, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(242, 82, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(243, 83, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(244, 84, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(245, 85, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(246, 86, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(247, 87, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(248, 88, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(249, 89, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(250, 90, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(251, 91, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(252, 92, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(253, 93, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(254, 94, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(255, 95, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(259, 99, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(260, 100, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(261, 101, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(262, 105, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(263, 108, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(264, 109, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(265, 110, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(266, 111, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(267, 112, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(268, 113, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(269, 114, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(270, 115, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(271, 116, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(272, 117, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(273, 118, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(274, 119, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(275, 120, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(276, 121, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(277, 122, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(278, 123, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(279, 124, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(280, 125, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(281, 126, 'huu', 'dfgdfgddg', 'outgoing', 'read', '2025-10-28 18:56:07'),
(282, 127, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(283, 128, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(284, 129, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(285, 130, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(286, 131, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(287, 132, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(288, 133, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(289, 134, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(290, 135, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(291, 136, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(292, 137, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(293, 138, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(294, 139, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(295, 140, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(296, 141, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(297, 142, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(298, 143, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(299, 144, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(300, 145, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(301, 146, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(302, 147, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(303, 148, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(304, 149, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(305, 150, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(306, 151, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(307, 152, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(308, 153, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(309, 154, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(310, 155, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(311, 156, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(312, 157, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(313, 158, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(314, 159, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(315, 160, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(316, 161, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(317, 162, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(318, 163, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(319, 164, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07'),
(320, NULL, 'huu', 'dfgdfgddg', 'outgoing', '', '2025-10-28 18:56:07');

-- --------------------------------------------------------

--
-- Table structure for table `security_settings`
--

CREATE TABLE `security_settings` (
  `id` int(11) NOT NULL,
  `max_login_attempts` int(11) DEFAULT 5,
  `session_timeout` int(11) DEFAULT 30,
  `password_min_length` int(11) DEFAULT 8,
  `enable_2fa` tinyint(4) DEFAULT 0,
  `force_ssl` tinyint(4) DEFAULT 0,
  `ip_whitelist` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `security_settings`
--

INSERT INTO `security_settings` (`id`, `max_login_attempts`, `session_timeout`, `password_min_length`, `enable_2fa`, `force_ssl`, `ip_whitelist`, `updated_at`) VALUES
(1, 5, 30, 8, 0, 0, NULL, '2025-10-17 19:29:30');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `auth_provider` enum('manual','google','facebook','instagram') NOT NULL DEFAULT 'manual',
  `provider_id` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_level` enum('normal','high') DEFAULT 'normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `auth_provider`, `provider_id`, `profile_pic`, `created_at`, `user_level`) VALUES
(1, 'user1234', 'user20@gmail.com', '$2y$10$5CZXHrFKjL43euzkhq99kuSOl9UQMGJ4/6taojQDzkwhaz3WHPJMW', 'manual', NULL, NULL, '2025-08-03 06:30:14', 'high'),
(2, 'user1002', 'user2@gmail.com', '$2y$10$qAgCXcSl/JNmz94UKihu/NxoYhM9lGg1WeYDYYbUzsyMa91rKM8uI', 'facebook', NULL, NULL, '2025-07-17 13:00:00', 'high'),
(3, 'user1003', 'user3@gmail.com', '$2y$10$/0./JWR3GJYG1h4xnAObKR2XJ92zlveZViwk44EZb6o2KD2rOgPXk', 'instagram', NULL, NULL, '2025-07-12 13:00:00', 'normal'),
(4, 'user1004', 'user4@gmail.com', '$2y$10$dMKak5X7QUHi9Va/H/PAaHqvzVChyizvPRHdYDiIHV.QABQjshHl6', 'google', NULL, NULL, '2025-08-09 13:00:00', 'normal'),
(5, 'user1005', 'user5@gmail.com', '$2y$10$.Nj962kH.qqauHAP7wFDXrvBKulc/GRnHzx61Ibd34TkDMwEUbcpb', 'manual', NULL, NULL, '2025-08-05 13:00:00', 'high'),
(6, 'user1006', 'user6@gmail.com', '$2y$10$0Dfy8UNZDkEBav78nIEIPF8Vunt/D0rmJrzap7rv0riR/C1MJCAzS', 'facebook', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(7, 'user1007', 'user7@gmail.com', '$2y$10$o4uEmhCoPnfUGQ1kK9xPyIq.6sMJUAWjCDXyTlYoJCQV14UzaZKbC', 'instagram', NULL, NULL, '2025-07-22 13:00:00', 'normal'),
(8, 'user1008', 'user8@gmail.com', '$2y$10$Zjt9jXmXBER6OmfIIYblYT9My9iCtOAMryY2WXKwfDIqbDszvcqtD', 'google', NULL, NULL, '2025-07-24 13:00:00', 'normal'),
(9, 'user1009', 'user9@gmail.com', '$2y$10$lEtS8brRimsmUosIBk9Ev/V4QirtOWaaJryvYbuKVu7Y0gT/2dYwE', 'manual', NULL, NULL, '2025-07-15 13:00:00', 'normal'),
(10, 'user1010', 'user10@gmail.com', '$2y$10$muqAQnzERXafTTzRcrDIBVuwt9QtKMs6UcGWuG30sq.k7mGH3ATk7', 'facebook', NULL, NULL, '2025-08-08 13:00:00', 'high'),
(11, 'user1011', 'user11@gmail.com', '$2y$10$jz/Q9Yrdrx4aE49G1YgG/d1zgevRL7r7Jw/Y0IsgHdFCpZ1yVDM1S', 'instagram', NULL, NULL, '2025-08-02 13:00:00', 'normal'),
(12, 'user1012', 'user12@gmail.com', '$2y$10$3fYkx2deGnjext929iy7ttHeQJTRxHJtFhGYlNSD3d/YVSjoAizyQ', 'google', NULL, NULL, '2025-07-13 13:00:00', 'normal'),
(13, 'user1013', 'user13@gmail.com', '$2y$10$S8Ev2f4twxVPmkyRL3BlLejcw.qb7LertreCq8tQF.tHq5WSQ4ieO', 'manual', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(14, 'user1014', 'user14@gmail.com', '$2y$10$j9LV85mU4joYwPfI6wV.03dWXAnQtPBduTKMuKRUiv8CFuf8lRGvg', 'facebook', NULL, NULL, '2025-07-29 13:00:00', 'normal'),
(15, 'user1015', 'user15@gmail.com', '$2y$10$Jg4F1tYBIxInNOrAfjsYuOQf.dUT6sB.Tvuj3IL2hj008GG7pgddP', 'instagram', NULL, NULL, '2025-07-31 13:00:00', 'normal'),
(16, 'user1016', 'user16@gmail.com', '$2y$10$POM7sLe1eLMnzL9nBFZgFMx8/CQP7t78AEawaiwIedMk.a857.VxW', 'google', NULL, NULL, '2025-08-01 13:00:00', 'normal'),
(17, 'user1017', 'user17@gmail.com', '$2y$10$dnTc.3Kr02nXqYdekVpokDBFcoFsbmjg6PP8y1Mz26SsTvwoW7siZ', 'manual', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(18, 'user1018', 'user18@gmail.com', '$2y$10$fBXM6AEYklgARxPcufLEkXMW0yNl5yrtj3t/uHdvWOnAOjusFqmLO', 'facebook', NULL, NULL, '2025-08-03 13:00:00', 'normal'),
(19, 'user1019', 'user19@gmail.com', '$2y$10$.0K2LeX7HkUbxQbuIa220pljBELCfUwS.AUboNDU39hZg4o5dQQqb', 'instagram', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(20, 'user1020', 'user20@gmail.com', '$2y$10$TvlF1nX2w1.bPhsYUeIzK6f0GQjargI1kSneazzFpJJSgFYCJeARJ', 'google', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(21, 'user1021', 'user21@gmail.com', '$2y$10$6yz7TC/e4txRF83AaNGlfb0UzX2qTxMFF4u51Gk2Ch/YtG6VIxrR.', 'manual', NULL, NULL, '2025-07-14 13:00:00', 'normal'),
(22, 'user1022', 'user22@gmail.com', '$2y$10$diOOFdi/F9qv/HGKfIU0ytxVnwNwSqbG.DQYnPT2hJShg1hrD..sq', 'facebook', NULL, NULL, '2025-07-21 13:00:00', 'normal'),
(23, 'user1023', 'user23@gmail.com', '$2y$10$LvSCYGO6YWv.lvTzhRiRvvCIsuI059OMfi3rP.smBXw6VdkeoXnjP', 'instagram', NULL, NULL, '2025-07-28 13:00:00', 'normal'),
(24, 'user1024', 'user24@gmail.com', '$2y$10$1m2zkzeDGaW6lhhTpI6vyYrLC6uLMDzojOOzNNeVXCySFPqzVSMYz', 'google', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(25, 'user1025', 'user25@gmail.com', '$2y$10$HSOF6sKK6s9hYz34odIUYHwevZOTeW052idDX7gcgR1N6ViGog17T', 'manual', NULL, NULL, '2025-07-15 13:00:00', 'normal'),
(26, 'user1026', 'user26@gmail.com', '$2y$10$9XF17HlMJ99sHd8PzQGZfoYe91YPs1ovQn.2Kni7Oq1ilWs66aobT', 'facebook', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(27, 'user1027', 'user27@gmail.com', '$2y$10$kRK71r7uQ0eGbvXgyu3d.I8qPgJ01vg2rbPMcKehd6oq8A9Cflw5D', 'instagram', NULL, NULL, '2025-07-13 13:00:00', 'normal'),
(28, 'user1028', 'user28@gmail.com', '$2y$10$st2qGGZSW6HRmyXCZnE8Hh2E5Fjkv9bBlJgCFXtUIjDAvm1ln6QZU', 'google', NULL, NULL, '2025-07-17 13:00:00', 'normal'),
(29, 'user1029', 'user29@gmail.com', '$2y$10$mFGCOTIE8sF3Ut6irpiXZUPt014sBS5uhWf7lbiPfxNJe9MAouTLe', 'manual', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(30, 'user1030', 'user30@gmail.com', '$2y$10$qyW9PeBwPBqrNDhExB1OTDiUzXqKQYJ.Y79Uu8jGZ.6tNr8zq6jy0', 'facebook', NULL, NULL, '2025-07-15 13:00:00', 'normal'),
(31, 'user1031', 'user31@gmail.com', '$2y$10$OshltdHAePSMRl4XSioFZJFZWAxNHfk5kws1RrWDvpcGHxhUZiAos', 'instagram', NULL, NULL, '2025-07-18 13:00:00', 'normal'),
(32, 'user1032', 'user32@gmail.com', '$2y$10$BBQuEFk8EdURxWapPdryyzAipxa3pHIs9E7oYFj9hGgiphsfewTdQ', 'google', NULL, NULL, '2025-07-19 13:00:00', 'normal'),
(33, 'user1033', 'user33@gmail.com', '$2y$10$CdhRA/Wwb8mP3tZ.76PdPkDV13kGA8.EH79HxNJxTAKTThMLfFhxZ', 'manual', NULL, NULL, '2025-08-01 13:00:00', 'normal'),
(34, 'user1034', 'user34@gmail.com', '$2y$10$LSu6BthiPOk9pmL9C5OTUM8JpztFcM5RTi3QNCeMVpWpE3Z1O6boo', 'facebook', NULL, NULL, '2025-08-10 13:00:00', 'normal'),
(35, 'user1035', 'user35@gmail.com', '$2y$10$RzJCpFf8SMVYxzel2P8A2/a8gMTSLkFtxKTi9QTXCZp7HOphAUYUH', 'instagram', NULL, NULL, '2025-07-28 13:00:00', 'normal'),
(36, 'user1036', 'user36@gmail.com', '$2y$10$IkafvmoBWiZoxl3mlEdLLHerAflGEXfKvuttbpTidc5sRC.l6NjYS', 'google', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(37, 'user1037', 'user37@gmail.com', '$2y$10$dWEhJfXgrZCc6AnZ6frLV0v/83X8p.jblfs9aXrqxZFq2D8SDD75E', 'manual', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(38, 'user1038', 'user38@gmail.com', '$2y$10$ECln8FJ8KrnogH/DMFJPJaUjebSEr21X4nvphLgHgLEDjaY12VLZk', 'facebook', NULL, NULL, '2025-07-14 13:00:00', 'normal'),
(39, 'user1039', 'user39@gmail.com', '$2y$10$WOjIdHT/j.is6XlnLJOJbE2Pwna6F5FFAKn1xdmRYKqnNAa9254pG', 'instagram', NULL, NULL, '2025-08-09 13:00:00', 'normal'),
(40, 'user1040', 'user40@gmail.com', '$2y$10$VYHTWIgtzkYldE3OoyoKAP9bO1wZX3Ekuzh/JFGLgM15o2zW3Gd31', 'google', NULL, NULL, '2025-07-20 13:00:00', 'normal'),
(41, 'user1041', 'user41@gmail.com', '$2y$10$tQLhJdLHNWYcHtEkGRG9wGPm8o/exkmN0pqkk6MG9WlMAVkpwUrOM', 'manual', NULL, NULL, '2025-07-13 13:00:00', 'normal'),
(42, 'user1042', 'user42@gmail.com', '$2y$10$uENLkpV/NPszh/2Ep2ZdATVMyOZC0uwWHs1YsTTHFVGvmFNFngTlT', 'facebook', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(43, 'user1043', 'user43@gmail.com', '$2y$10$8FYzwE37vuF7Ly0F05NfGk6iBlXhBYIsE91AgwJ5SrUUeoAiAMsXT', 'instagram', NULL, NULL, '2025-08-05 13:00:00', 'normal'),
(44, 'user1044', 'user44@gmail.com', '$2y$10$Na5mhaM6QdW7G6C7oBNS3lUUWzyjzNk6ZYtkKWCekunNfGAnVYTDf', 'google', NULL, NULL, '2025-07-27 13:00:00', 'normal'),
(45, 'user1045', 'user45@gmail.com', '$2y$10$WS6KW6IEEq6CITWkY0n.JJspTHSIVDRvwaZwnWk1bqudXTkaCi3m4', 'manual', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(46, 'user1046', 'user46@gmail.com', '$2y$10$n2hd4P0J6JHlU8uIbTlulhHAvpPaLQEepkmugqL6KcwsNajQrRZcO', 'facebook', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(47, 'user1047', 'user47@gmail.com', '$2y$10$jcQ/a7CjbVs4uuYwyQJJ8yK27VQK4C8TPRe1bqaNwN..nKHzFeffD', 'instagram', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(48, 'user1048', 'user48@gmail.com', '$2y$10$NZBRRYU7ca0vDyiskUBd0cujvBiFFsmA7QI8Pbi0eQNxpShI6t0Rg', 'google', NULL, NULL, '2025-08-05 13:00:00', 'normal'),
(49, 'user1049', 'user49@gmail.com', '$2y$10$s4eRbhkRU8IvR5EBnwYEBLsScdk601nhuLb94JdmFm3WUIRUkrriv', 'manual', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(50, 'user1050', 'user50@gmail.com', '$2y$10$YhPgeDsNP4LcXeSN5NigfjAbAdwdJ3hFpULw.oAhMSkQ8b3x5Sfl5', 'facebook', NULL, NULL, '2025-07-28 13:00:00', 'normal'),
(51, 'user1051', 'user51@gmail.com', '$2y$10$iQ1ahAWPJTiP/a/jLymsiwiQJrulFStT9l3UAXkXipXExawNYipOr', 'instagram', NULL, NULL, '2025-07-17 13:00:00', 'normal'),
(52, 'user1052', 'user52@gmail.com', '$2y$10$l8Zj7QygnXBBo5vRwWSgve3gNfjUnCBCO8ji0bMrlEyniBbYSDvQE', 'google', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(53, 'user1053', 'user53@gmail.com', '$2y$10$9CThsxFqDyq7VEdj/ZhXrMcocuGHhKsFqW/WkRcPXQZeyp.tv0SOU', 'manual', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(54, 'user1054', 'user54@gmail.com', '$2y$10$BEjgh4s2Se4MM37nGj5NUoFZrwRtoY4sklwntTLAn8GSMD2YTDh4w', 'facebook', NULL, NULL, '2025-07-21 13:00:00', 'normal'),
(55, 'user1055', 'user55@gmail.com', '$2y$10$EcFsxB4ZBSQT2s/DtCjlK.0I6MOJRkMSStvLlb.B1ux8cFMcAdYlC', 'instagram', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(56, 'user1056', 'user56@gmail.com', '$2y$10$sd8h4Yee3CpIeW3kMTV.Iqwf/vP8ePM52kGt0VIe9Ye58uhJATk8H', 'google', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(57, 'user1057', 'user57@gmail.com', '$2y$10$kkwMvjaLAN99wZ5piBjjWSfF8lvqrgMHoiE.vJIN8xiM3nLV.Duj5', 'manual', NULL, NULL, '2025-08-07 13:00:00', 'normal'),
(58, 'user1058', 'user58@gmail.com', '$2y$10$PntF7EGidPL0Z.F0OBzDluP/fJ0Gki0xCJxLJbOGUNnjB7ek21EgD', 'facebook', NULL, NULL, '2025-08-10 13:00:00', 'normal'),
(59, 'user1059', 'user59@gmail.com', '$2y$10$TOzXmP5u0SyQYWQzQCnSdM8X/BcaxOROT4ktp4L5WpzWLvZ36MHiU', 'instagram', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(60, 'user1060', 'user60@gmail.com', '$2y$10$06pk0wMOG4YECzB9xrWiQyCYBtII7HymFLXE5A//PZ.UpbtdbBrJp', 'google', NULL, NULL, '2025-07-18 13:00:00', 'normal'),
(61, 'user1061', 'user61@gmail.com', '$2y$10$8p8KNnfA/zBTflZhwiIZ8xd2g57vO2OQVPH4VYPT0qyIpFr0Wv6fj', 'manual', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(62, 'user1062', 'user62@gmail.com', '$2y$10$yvKCPhCa2PbQES8.L2IimSNmWsfOrGPSiwDNx36SIB/7D45..wAeh', 'facebook', NULL, NULL, '2025-07-31 13:00:00', 'normal'),
(63, 'user1063', 'user63@gmail.com', '$2y$10$sQh2FTMAOwC.7QzGVHPjebbzqG3JyY.Uojq3EV4LEY7NxM8olHTkU', 'instagram', NULL, NULL, '2025-08-08 13:00:00', 'normal'),
(64, 'user1064', 'user64@gmail.com', '$2y$10$CMwOM/px1CqSWO0Ew3Yrn12Qc7XYd3KALAMig/4WtO3GX5QPHUFVW', 'google', NULL, NULL, '2025-07-18 13:00:00', 'normal'),
(65, 'user1065', 'user65@gmail.com', '$2y$10$X8ipcw1gldo4qjNUCQmnDn30G0tAMM4tbf8MG9zzeLezpqKvXr4WM', 'manual', NULL, NULL, '2025-07-15 13:00:00', 'normal'),
(66, 'user1066', 'user66@gmail.com', '$2y$10$rsni4bxaJ8VWJ51rottk.rLB/sfpwzskI.JlgNsmKQi98wFRubDm.', 'facebook', NULL, NULL, '2025-08-10 13:00:00', 'normal'),
(67, 'user1067', 'user67@gmail.com', '$2y$10$OoKw5FjzcCIsa8511sRJ6yfE.mCv7uhkTnRIcvWsDEz0w2eH/eSm2', 'instagram', NULL, NULL, '2025-07-27 13:00:00', 'normal'),
(68, 'user1068', 'user68@gmail.com', '$2y$10$.Nsk7xv.Q8pEY.GY.hoTy/CzSSgXskd0UJIsZ6WS5NESv5hxCnEkY', 'google', NULL, NULL, '2025-07-24 13:00:00', 'normal'),
(69, 'user1069', 'user69@gmail.com', '$2y$10$7Wj7otkOLKI79FjNZi2yD5Lrlt8ZNJGkK0ZYFszWbCAku7y7Qkls6', 'manual', NULL, NULL, '2025-07-19 13:00:00', 'normal'),
(70, 'user1070', 'user70@gmail.com', '$2y$10$/4pl7iZPjVx5B5JqFQhwnwymJ7/mhQvSR2fEYIu15ukDNjCcC/6Bz', 'facebook', NULL, NULL, '2025-07-18 13:00:00', 'normal'),
(71, 'user1071', 'user71@gmail.com', '$2y$10$vd.eQvwEayOLcyfDYQVYA/6LWewm7UDNJgsucVeJf01YL5FCMG91r', 'instagram', NULL, NULL, '2025-07-27 13:00:00', 'normal'),
(72, 'user1072', 'user72@gmail.com', '$2y$10$WqHGSBxNYLJctWACFsu7Rt0W6n6TU9Smwg9mur8S3ez8KzTPZzyA0', 'google', NULL, NULL, '2025-07-31 13:00:00', 'normal'),
(73, 'user1073', 'user73@gmail.com', '$2y$10$yN1lVNGrT4ouo0vD.qhpP8h0rRQ/tDeYBGCFUSXoCqmsyQ2rCQY5v', 'manual', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(74, 'user1074', 'user74@gmail.com', '$2y$10$RHzUjjzFuYCUbji3WdfuwCz6qLR8Iug4fQFYZnKWeY46/rq/7v8rH', 'facebook', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(75, 'user1075', 'user75@gmail.com', '$2y$10$2m8yLmkF0O0oza7n3q9X9TkJtBKZRn1EjZzjuM0Dqs03q2TGk.M7G', 'instagram', NULL, NULL, '2025-08-05 13:00:00', 'normal'),
(76, 'user1076', 'user76@gmail.com', '$2y$10$rgcupQ/3LBjlQRQR1ThR9lPFioUpbfDexCKiRjCdjzsB8lQx56s8w', 'google', NULL, NULL, '2025-07-12 13:00:00', 'normal'),
(77, 'user1077', 'user77@gmail.com', '$2y$10$5mmoNzqzzvd/trGqs3CgCVwNIz43oltHmLju/ls0xpkqaECVM51sg', 'manual', NULL, NULL, '2025-08-03 13:00:00', 'normal'),
(78, 'user1078', 'user78@gmail.com', '$2y$10$mbAeWA4yvwi6Zo5ThbGnfx..vc3GdV4P52GYkigjydrJS74ycN0PF', 'facebook', NULL, NULL, '2025-07-17 13:00:00', 'normal'),
(79, 'user1079', 'user79@gmail.com', '$2y$10$bNB6gnE4VQyCMNeMxLxBmo8aWoAWGCzi0iEwO92i4iJ90mUUrueQp', 'instagram', NULL, NULL, '2025-07-24 13:00:00', 'normal'),
(80, 'user1080', 'user80@gmail.com', '$2y$10$9I0iG15or1QLZZaeb7b1jHtyVw1w6ElVyPhKv511ZVsvTDz4GocEe', 'google', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(81, 'user1081', 'user81@gmail.com', '$2y$10$0GDy1lmlvFknDtLyZZ5GFa0xA3qqD/imJsq.SENHujCfhTEzONSzV', 'manual', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(82, 'user1082', 'user82@gmail.com', '$2y$10$QxfEF6iBld8DgUOt2PBJG/lSGrbfbFoVIyiPFAdexQmoq9ZaAlqCq', 'facebook', NULL, NULL, '2025-07-25 13:00:00', 'normal'),
(83, 'user1083', 'user83@gmail.com', '$2y$10$Yd4fYu8ha/7sQugWsbM7F.OyTv9VAPCrIkSCei1H2xXsTDLtdYxgG', 'instagram', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(84, 'user1084', 'user84@gmail.com', '$2y$10$5a8m5/IuWNnPtzWhQt0wIXEHreQRcuOyEQhbOKp2IGjt0PCPg1W5h', 'google', NULL, NULL, '2025-08-03 13:00:00', 'normal'),
(85, 'user1085', 'user85@gmail.com', '$2y$10$.m4RnosFBrCJgCXPbD8JZTixvr1yVJvR8vjea4R00PIz6Yw/JTSOK', 'manual', NULL, NULL, '2025-07-26 13:00:00', 'normal'),
(86, 'user1086', 'user86@gmail.com', '$2y$10$4YX6V3W5ABvgwUiSK7OdTJ74kX.YqsQYkfEwCxpcrH50pVY45Ou3M', 'facebook', NULL, NULL, '2025-07-23 13:00:00', 'normal'),
(87, 'user1087', 'user87@gmail.com', '$2y$10$FGdB7MWv5oCTUTZQmaoL1MiaiUOCgJw9I6EkKOhITDDPcU5aYUiwq', 'instagram', NULL, NULL, '2025-07-22 13:00:00', 'normal'),
(88, 'user1088', 'user88@gmail.com', '$2y$10$KV2Z0SipIPpZOoUYFbatlz.MgH7A7KGj86UbN.2EdSIiVZUrIeVIH', 'google', NULL, NULL, '2025-07-21 13:00:00', 'normal'),
(89, 'user1089', 'user89@gmail.com', '$2y$10$WpAPvB7TazVHEXbWzyHUpMlbQMmBrPI6rYOUf3oe0Z1kP7Tp6bdl8', 'manual', NULL, NULL, '2025-07-13 13:00:00', 'normal'),
(90, 'user1090', 'user90@gmail.com', '$2y$10$RvQZOI3F1QPSVLQaTF//5DFQNcbTe.tuw4fXniNGRMIgaHUkzFH5n', 'facebook', NULL, NULL, '2025-08-10 13:00:00', 'normal'),
(91, 'user1091', 'user91@gmail.com', '$2y$10$5pzYtk0D5yUuPk4ILFeZ3FCL5AVZZOdLgugZAoQfRuhLB0feOqPFU', 'instagram', NULL, NULL, '2025-08-03 13:00:00', 'normal'),
(92, 'user1092', 'user92@gmail.com', '$2y$10$ApeuW41DGBYV5eIYoz1CAYgk7fmmB69ImW6t8bePgUs0LA5KXqPjz', 'google', NULL, NULL, '2025-08-01 13:00:00', 'normal'),
(93, 'user1093', 'user93@gmail.com', '$2y$10$Zmz65D4ZHrF8U084x1qSEWzU4puoBJ1Vqctz9iLHsNbq9I66wMBPj', 'manual', NULL, NULL, '2025-08-08 13:00:00', 'normal'),
(94, 'user1094', 'user94@gmail.com', '$2y$10$yXQRMllynpHjvwIrESuXISsqF74AU4G0.zA/Zhd2ofqbelxM12O2Z', 'facebook', NULL, NULL, '2025-08-05 13:00:00', 'normal'),
(95, 'user1095', 'user95@gmail.com', '$2y$10$tGX5YYUIKssudIdO5JCwx9R3x.yoYi0yNGZeTZAnZ7jjSkh1j6cw3', 'instagram', NULL, NULL, '2025-07-26 13:00:00', 'normal'),
(96, 'user1096', 'user96@gmail.com', '$2y$10$bUTCaKqPyVKM78RkdrYnMBdrJKj4VO/FNjiTOBj.9GO6xMQlo11Fz', 'google', NULL, NULL, '2025-07-30 13:00:00', 'normal'),
(97, 'user1097', 'user97@gmail.com', '$2y$10$BkkYDQ57R.hhENLkJjM.jF14hp7FVdDoZjVenQuzlcUfkciNG6VBn', 'manual', NULL, NULL, '2025-07-13 13:00:00', 'normal'),
(98, 'user1098', 'user98@gmail.com', '$2y$10$ip9fMF6zmFyOgJ.sKmkMSuHbbjQaJJ3AC0kRo8VnLX4PKmPrdgdI4', 'facebook', NULL, NULL, '2025-08-03 13:00:00', 'normal'),
(99, 'user1099', 'user99@gmail.com', '$2y$10$5/0fTxHvlmRWK3HLhZLUkbdGMaWzOOSV0qHzOtBud8rhsc6a2vWMz', 'instagram', NULL, NULL, '2025-08-04 13:00:00', 'normal'),
(100, 'user1100', 'user100@gmail.com', '$2y$10$e.VT5Zg5QS8e2b1e67VL8sjHSqjMbegZR6n8kr.duSZ6NsWgGSdJ7', 'google', NULL, NULL, '2025-07-15 13:00:00', 'normal'),
(101, 'ranajit@123', 'ranajitbarik200@gmail.com', '$2y$10$LuyZT8rMMvM.bMdYYEgzh.IbCgWS2IOt8XH/bygGS.lnANgycPZbC', 'manual', NULL, NULL, '2025-08-10 14:14:38', 'high'),
(105, 'user1001', 'user1@gmail.com', '$2y$10$Ny1.Is.rMQmO3e7s05HFLlQVlUHvPWmQjCatUyhL5K5X8Z1Ct.0Oq', 'manual', NULL, NULL, '2025-07-11 13:00:00', 'normal'),
(108, 'hii`', 'hii@gmail.com', '$2y$10$8xc44dERDY.Kt7qEW1jhpOoyOVt2jrXGPoi9mE2VOiyfHWf2F5Waq', 'manual', NULL, NULL, '2025-08-30 19:15:28', 'normal'),
(109, 'user1', 'user@gmail.com', '$2y$10$PcBe8AHQWtSFwseKuO58Q.b/CyfumeN3hYTF7yyWIiYGYoKLoa/za', 'manual', NULL, NULL, '2025-08-31 18:42:18', 'normal'),
(110, 'hello', 'hello@gmail.com', '$2y$10$aKHAGqZktW4nwiIyJOUIF.vzvaJeqGF3x55A9xfJgWjf.Ehh0ExlO', 'manual', NULL, NULL, '2025-09-02 17:34:08', 'normal'),
(111, 'roni1', 'roni1@gmail.com', '$2y$10$qNmu1Z94R0asJvJexfl8XeuZ6XwdKLlG5HQVgEhtlKdUGkmT7I7TC', 'manual', NULL, NULL, '2025-09-13 17:47:08', 'normal'),
(112, 'roni2', 'roni2@gmail.com', '$2y$10$jq7iq6fNlTXRNWChpC2vMuTBbwZoMqfQrCsET2TXf30rGEYI6vv3G', 'manual', NULL, NULL, '2025-09-13 17:52:14', 'normal'),
(113, 'roni3', 'roni3@gmail.com', '$2y$10$pYuwNSbl69nOoa.YbR9Js.Jv/VvKOuaDNGpqpdafSe36BUJqta7vi', 'manual', NULL, NULL, '2025-09-15 02:04:05', 'normal'),
(114, 'tesuser1', 'tesuser1@gmail.com', '$2y$10$iXawbw2g3rbCzYREUppT..FBENWX78V6wjo3gNHywA.Y5LK8NT79.', 'manual', NULL, NULL, '2025-10-05 20:03:34', 'normal'),
(115, 'user1234', 'user20@gmail.com', '$2y$10$85CZXHFFK|_4SeuzknqeekuSCISUAMGJ4/tajDzkw.', 'manual', NULL, NULL, '2026-08-08 06:30:14', 'high'),
(116, 'user1', 'user2e@gmail.com', '$2y$10$92nGMs2S1A9VWOWSC5XKXeE4QYV5JZ8KZ1M2N3B4V5C6D7E8F9G0H', 'manual', NULL, NULL, '2026-07-17 12:50:00', ''),
(117, 'useri002', 'user2@amsil.com', '$2y$10$LkRqP1s2T3u4V5W6X7Y8Z9A0B1C2D3E4F5G6H7I8J9K0L1M2N3O4P', 'manual', NULL, NULL, '2026-07-12 12:50:00', 'normal'),
(118, 'user0003', 'alice.johnson@mail.com', '$2y$10$AbCdEfGhIjKlMnOpQrStUvWxYz0123456789AbCdEfGhIjKlMnOpQ', 'manual', NULL, NULL, '2026-01-15 04:15:00', 'normal'),
(119, 'user0004', 'bob.smith@domain.net', '$2y$10$ZaQxSwCdEfVgBhNjMlKiHoP1q2w3e4r5t6y7u8i9o0p1a2s3d4f5g6', 'manual', NULL, NULL, '2026-01-22 08:40:30', ''),
(120, 'user0005', 'charlie.brown@web.org', '$2y$10$HjKmLnOpQrStUvWxYz0123456789AbCdEfGhIjKlMnOpQrStUvWxYz', 'manual', NULL, NULL, '2026-02-05 05:50:15', 'normal'),
(121, 'user0006', 'diana.princ3@example.co', '$2y$10$QwErTyUiOpAsDfGhJkLzXcVbNm0123456789QwErTyUiOpAsDfGhJkL', 'manual', NULL, NULL, '2026-02-18 11:25:40', 'high'),
(122, 'user0007', 'evan.tech@server.io', '$2y$10$ZlXkCjVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkL', 'manual', NULL, NULL, '2026-03-03 03:00:25', 'normal'),
(123, 'user0008', 'fiona.gray@network.com', '$2y$10$MnBvCxZlKjHgFeDcBaQsWeRtYuIoPkOlNiUmBtVgYcXdRfTgYhNju', 'manual', NULL, NULL, '2026-03-19 08:15:10', ''),
(124, 'user0009', 'george.wilson@online.org', '$2y$10$PoIuYtTrEwQasDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQ', 'manual', NULL, NULL, '2026-04-02 04:45:45', 'normal'),
(125, 'user0010', 'hannah.martin@connect.net', '$2y$10$LzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLz', 'manual', NULL, NULL, '2026-04-25 11:50:30', 'high'),
(126, 'user0011', 'ian.davis@system.io', '$2y$10$QaZsXwSvCtBvGnHmJkLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiO', 'manual', NULL, NULL, '2026-05-07 09:05:20', 'normal'),
(127, 'user0012', 'julia.moore@digital.com', '$2y$10$WsCdEfRvTgYhNjuMkiLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOp', 'manual', NULL, NULL, '2026-05-21 06:20:15', ''),
(128, 'user0013', 'kevin.taylor@cloud.org', '$2y$10$EdCfRbGnHmJkLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGh', 'manual', NULL, NULL, '2026-06-03 03:35:40', 'normal'),
(129, 'user0014', 'lisa.anderson@webnet.co', '$2y$10$FvGbHnJmKkLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJk', 'manual', NULL, NULL, '2026-06-28 10:55:55', 'high'),
(130, 'user0015', 'mike.clark@servix.net', '$2y$10$GnHmJkLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXc', 'manual', NULL, NULL, '2026-07-14 07:10:30', 'normal'),
(131, 'user0016', 'nina.rodriguez@mailbox.io', '$2y$10$HmJkLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVb', 'manual', NULL, NULL, '2026-08-01 10:25:25', ''),
(132, 'user0017', 'oscar.lee@connectx.com', '$2y$10$JkLoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNm', 'manual', NULL, NULL, '2026-08-29 04:40:10', 'normal'),
(133, 'user0018', 'paula.white@netplus.org', '$2y$10$LoPiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQw', 'manual', NULL, NULL, '2026-09-12 13:00:45', 'high'),
(134, 'user0019', 'quincy.adams@systemx.co', '$2y$10$PiuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwEr', 'manual', NULL, NULL, '2026-09-30 09:15:20', 'normal'),
(135, 'user0020', 'rachel.green@webmax.net', '$2y$10$IuYtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErT', 'manual', NULL, NULL, '2026-10-15 05:30:35', ''),
(136, 'user0021', 'samuel.hall@cloudmax.io', '$2y$10$UyTReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTy', 'manual', NULL, NULL, '2026-10-28 10:45:50', 'normal'),
(137, 'user0022', 'tina.king@digiserv.com', '$2y$10$YtReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyU', 'manual', NULL, NULL, '2026-11-05 07:55:15', 'normal'),
(138, 'user0023', 'umar.nelson@netverse.org', '$2y$10$TReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUi', 'manual', NULL, NULL, '2026-11-20 04:10:30', 'high'),
(139, 'user0024', 'vicky.scott@webworld.co', '$2y$10$ReWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiO', 'manual', NULL, NULL, '2026-12-03 10:25:45', 'normal'),
(140, 'user0025', 'walter.cook@connectpro.net', '$2y$10$eWqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOp', 'manual', NULL, NULL, '2026-12-18 06:40:10', ''),
(141, 'user0026', 'xena.baker@servpro.io', '$2y$10$WqAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpA', 'manual', NULL, NULL, '2027-01-04 02:55:25', 'normal'),
(142, 'user0027', 'yousef.young@netcore.com', '$2y$10$qAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAs', 'manual', NULL, NULL, '2027-01-22 09:10:40', 'normal'),
(143, 'user0028', 'zoe.carter@webcore.org', '$2y$10$AsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsD', 'manual', NULL, NULL, '2027-02-07 06:25:55', 'high'),
(144, 'user0029', 'adam.mitchell@cloudhub.co', '$2y$10$sDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDf', 'manual', NULL, NULL, '2027-02-25 11:40:10', 'normal'),
(145, 'user0030', 'bella.perez@nexusnet.net', '$2y$10$DfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfG', 'manual', NULL, NULL, '2027-03-10 07:55:25', ''),
(146, 'user0031', 'carlos.robinson@linkup.io', '$2y$10$fGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGh', 'manual', NULL, NULL, '2027-03-29 04:10:40', 'normal'),
(147, 'user0032', 'dana.hernandez@meshnet.com', '$2y$10$GhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJ', 'manual', NULL, NULL, '2027-04-12 10:25:55', 'normal'),
(148, 'user0033', 'ethan.wright@fastweb.org', '$2y$10$hJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJk', 'manual', NULL, NULL, '2027-04-30 06:40:10', 'high'),
(149, 'user0034', 'frida.lopez@speednet.co', '$2y$10$JkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkL', 'manual', NULL, NULL, '2027-05-14 02:55:25', 'normal'),
(150, 'user0035', 'gary.morris@quicklink.net', '$2y$10$kLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLz', 'manual', NULL, NULL, '2027-05-28 09:10:40', ''),
(151, 'user0036', 'helen.rogers@rapidweb.io', '$2y$10$LzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzX', 'manual', NULL, NULL, '2027-06-11 05:25:55', 'normal'),
(152, 'user0037', 'isaac.reed@swiftnet.com', '$2y$10$zXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXc', 'manual', NULL, NULL, '2027-06-26 11:40:10', 'normal'),
(153, 'user0038', 'jasmine.stewart@expressweb.org', '$2y$10$XcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcV', 'manual', NULL, NULL, '2027-07-09 07:55:25', 'high'),
(154, 'user0039', 'kurt.nguyen@instantnet.co', '$2y$10$cVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVb', 'manual', NULL, NULL, '2027-07-24 04:10:40', 'normal'),
(155, 'user0040', 'luna.parker@directlink.net', '$2y$10$VbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbN', 'manual', NULL, NULL, '2027-08-06 10:25:55', ''),
(156, 'user0041', 'marcus.collins@simplenet.io', '$2y$10$bNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNm', 'manual', NULL, NULL, '2027-08-21 06:40:10', 'normal'),
(157, 'user0042', 'nora.bell@easyserv.com', '$2y$10$NmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQ', 'manual', NULL, NULL, '2027-09-03 02:55:25', 'normal'),
(158, 'user0043', 'owen.murphy@quickweb.org', '$2y$10$mQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQw', 'manual', NULL, NULL, '2027-09-19 09:10:40', 'high'),
(159, 'user0044', 'penny.cox@fastserv.co', '$2y$10$QwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwE', 'manual', NULL, NULL, '2027-10-02 05:25:55', 'normal'),
(160, 'user0045', 'quentin.ward@speedyweb.net', '$2y$10$wErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwEr', 'manual', NULL, NULL, '2027-10-17 11:40:10', ''),
(161, 'user0046', 'ruby.torres@instantweb.io', '$2y$10$ErTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErT', 'manual', NULL, NULL, '2027-11-01 07:55:25', 'normal'),
(162, 'user0047', 'simon.hughes@directweb.com', '$2y$10$rTyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTy', 'manual', NULL, NULL, '2027-11-16 04:10:40', 'normal'),
(163, 'user0048', 'tara.price@simpleweb.org', '$2y$10$TyUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyU', 'manual', NULL, NULL, '2027-12-03 10:25:55', 'high'),
(164, 'user0049', 'ulysses.brooks@easyweb.co', '$2y$10$yUiOpAsDfGhJkLzXcVbNmQwErTyUiOpAsDfGhJkLzXcVbNmQwErTyUi', 'manual', NULL, NULL, '2027-12-20 06:40:10', 'normal'),
(166, 'roni.exe', 'roni_exe@gmail.com', '$2y$10$8cK8xP9RCb/wzs3iG26chOTRaa2Oxumrw2R0nXOWGdGEBFN.3bNLe', 'manual', NULL, NULL, '2025-10-28 20:23:06', 'normal'),
(167, 'roni.exe', 'roni@gmail.com', '$2y$10$wFG4duFUuTnhMtqxqevtCuE65bWH9ODBE5Bs8AMMvlBwtaCiu1ZPK', 'manual', NULL, NULL, '2025-11-05 18:41:38', 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity`
--

CREATE TABLE `user_activity` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `page_name` varchar(255) NOT NULL,
  `page_type` enum('destination','blog','home','about','contact','login','register','profile') NOT NULL,
  `activity_type` enum('view','click') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(255) DEFAULT NULL,
  `activity_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_activity`
--

INSERT INTO `user_activity` (`id`, `user_id`, `page_name`, `page_type`, `activity_type`, `ip_address`, `user_agent`, `session_id`, `activity_date`, `created_at`) VALUES
(1, 1, 'Home Page', 'home', 'view', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_001', '2025-11-09', '2025-11-09 19:24:34'),
(2, 1, 'Destination List', 'destination', 'click', '192.168.1.100', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_001', '2025-11-09', '2025-11-09 19:24:34'),
(3, 2, 'Travel Blog', 'blog', 'view', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_002', '2025-11-09', '2025-11-09 19:24:34'),
(4, 2, 'About Us', 'about', 'click', '192.168.1.101', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_002', '2025-11-09', '2025-11-09 19:24:34'),
(5, NULL, 'Home Page', 'home', 'view', '192.168.1.102', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_003', '2025-11-09', '2025-11-09 19:24:34'),
(6, NULL, 'Destination List', 'destination', 'click', '192.168.1.103', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_004', '2025-11-09', '2025-11-09 19:24:34'),
(7, NULL, 'Contact Page', 'contact', 'view', '192.168.1.104', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_005', '2025-11-09', '2025-11-09 19:24:34'),
(8, 1, 'Home Page', 'home', 'view', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_006', '2025-11-10', '2025-11-09 19:24:34'),
(9, NULL, 'Destination List', 'destination', 'view', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_007', '2025-11-10', '2025-11-09 19:24:34'),
(10, 2, 'Travel Blog', 'blog', 'click', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_008', '2025-11-10', '2025-11-09 19:24:34'),
(11, NULL, 'About Us', 'about', 'view', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_009', '2025-11-10', '2025-11-09 19:24:34'),
(12, 3, 'Contact Page', 'contact', 'click', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_010', '2025-11-10', '2025-11-09 19:24:34'),
(13, NULL, 'Home Page', 'home', 'click', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_011', '2025-11-10', '2025-11-09 19:24:34'),
(14, 1, 'Destination Details', 'destination', 'view', '192.168.1.105', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_012', '2025-11-08', '2025-11-09 19:24:34'),
(15, NULL, 'Travel Blog', 'blog', 'view', '192.168.1.106', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_013', '2025-11-08', '2025-11-09 19:24:34'),
(16, 2, 'Home Page', 'home', 'click', '192.168.1.107', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_014', '2025-11-07', '2025-11-09 19:24:34'),
(17, NULL, 'About Us', 'about', 'click', '192.168.1.108', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_015', '2025-11-07', '2025-11-09 19:24:34'),
(18, 1, 'Contact Page', 'contact', 'view', '192.168.1.109', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36', 'sess_016', '2025-11-06', '2025-11-09 19:24:34');

-- --------------------------------------------------------

--
-- Table structure for table `user_ips`
--

CREATE TABLE `user_ips` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_ips`
--

INSERT INTO `user_ips` (`id`, `user_id`, `ip_address`, `user_agent`, `login_time`) VALUES
(1, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 19:20:12'),
(2, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 19:20:21'),
(3, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 19:20:29'),
(4, 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-31 18:42:35'),
(5, 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-31 19:29:10'),
(6, 110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-04 19:05:15'),
(7, 110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-04 19:05:46'),
(8, 110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-04 19:06:51'),
(9, 110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-04 19:07:15'),
(10, 110, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-04 19:08:25'),
(11, 167, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36 Edg/141.0.0.0', '2025-11-05 19:01:49'),
(19, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-08 17:26:48'),
(20, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 16:59:34'),
(21, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 19:34:37'),
(22, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 20:00:01'),
(23, 1, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36 Edg/142.0.0.0', '2025-11-09 20:02:06');

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

-- --------------------------------------------------------

--
-- Table structure for table `website_analytics`
--

CREATE TABLE `website_analytics` (
  `id` int(11) NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `page_type` enum('destination','blog','home','about','contact','login','register','profile') NOT NULL,
  `views` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `logged_in_views` int(11) DEFAULT 0,
  `logged_in_clicks` int(11) DEFAULT 0,
  `guest_views` int(11) DEFAULT 0,
  `guest_clicks` int(11) DEFAULT 0,
  `date_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_analytics`
--

INSERT INTO `website_analytics` (`id`, `page_name`, `page_type`, `views`, `clicks`, `logged_in_views`, `logged_in_clicks`, `guest_views`, `guest_clicks`, `date_date`, `created_at`) VALUES
(1, 'Contact Page', 'contact', 0, 1, 0, 1, 0, 0, '2025-11-10', '2025-11-09 19:24:34'),
(2, 'Home Page', 'home', 1, 1, 1, 0, 0, 1, '2025-11-10', '2025-11-09 19:24:34'),
(3, 'Destination List', 'destination', 1, 0, 0, 0, 1, 0, '2025-11-10', '2025-11-09 19:24:34'),
(4, 'Travel Blog', 'blog', 0, 1, 0, 1, 0, 0, '2025-11-10', '2025-11-09 19:24:34'),
(5, 'About Us', 'about', 1, 0, 0, 0, 1, 0, '2025-11-10', '2025-11-09 19:24:34'),
(6, 'Contact Page', 'contact', 1, 0, 0, 0, 1, 0, '2025-11-09', '2025-11-09 19:24:34'),
(7, 'Home Page', 'home', 2, 0, 1, 0, 1, 0, '2025-11-09', '2025-11-09 19:24:34'),
(8, 'Destination List', 'destination', 0, 2, 0, 1, 0, 1, '2025-11-09', '2025-11-09 19:24:34'),
(9, 'Travel Blog', 'blog', 1, 0, 1, 0, 0, 0, '2025-11-09', '2025-11-09 19:24:34'),
(10, 'About Us', 'about', 0, 1, 0, 1, 0, 0, '2025-11-09', '2025-11-09 19:24:34'),
(11, 'Destination Details', 'destination', 1, 0, 1, 0, 0, 0, '2025-11-08', '2025-11-09 19:24:34'),
(12, 'Travel Blog', 'blog', 1, 0, 0, 0, 1, 0, '2025-11-08', '2025-11-09 19:24:34'),
(13, 'About Us', 'about', 0, 1, 0, 0, 0, 1, '2025-11-07', '2025-11-09 19:24:34'),
(14, 'Home Page', 'home', 0, 1, 0, 1, 0, 0, '2025-11-07', '2025-11-09 19:24:34'),
(15, 'Contact Page', 'contact', 1, 0, 1, 0, 0, 0, '2025-11-06', '2025-11-09 19:24:34');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `appearance_settings`
--
ALTER TABLE `appearance_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_settings`
--
ALTER TABLE `email_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `errors`
--
ALTER TABLE `errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `security_settings`
--
ALTER TABLE `security_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_ips`
--
ALTER TABLE `user_ips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `website_analytics`
--
ALTER TABLE `website_analytics`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `appearance_settings`
--
ALTER TABLE `appearance_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `calendar_events`
--
ALTER TABLE `calendar_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `email_settings`
--
ALTER TABLE `email_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `errors`
--
ALTER TABLE `errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=321;

--
-- AUTO_INCREMENT for table `security_settings`
--
ALTER TABLE `security_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `user_activity`
--
ALTER TABLE `user_activity`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `user_ips`
--
ALTER TABLE `user_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `user_levels`
--
ALTER TABLE `user_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `website_analytics`
--
ALTER TABLE `website_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `errors`
--
ALTER TABLE `errors`
  ADD CONSTRAINT `errors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity`
--
ALTER TABLE `user_activity`
  ADD CONSTRAINT `user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_ips`
--
ALTER TABLE `user_ips`
  ADD CONSTRAINT `user_ips_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD CONSTRAINT `user_levels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
