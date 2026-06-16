-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: May 27, 2026 at 02:21 PM
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
-- Database: `tripmate`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_suggestions`
--

CREATE TABLE `activity_suggestions` (
  `id` int(11) NOT NULL,
  `itinerary_day_id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `activity_type` enum('sightseeing','adventure','food','wellness','cultural','shopping') DEFAULT 'sightseeing',
  `description` text DEFAULT NULL,
  `time_required` int(11) DEFAULT NULL COMMENT 'Time in minutes',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `time_of_day` enum('morning','afternoon','evening','night') DEFAULT 'morning',
  `location` varchar(255) DEFAULT NULL,
  `coordinates` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Latitude and longitude' CHECK (json_valid(`coordinates`)),
  `rating` decimal(3,2) DEFAULT NULL,
  `priority` int(11) DEFAULT 0 COMMENT 'Higher priority = more recommended',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'admin@123', 'admin@gmail.com', '$2y$10$AlP0uyFkcON5ui8rCxzsLeaIrg.KCOjsZ8rB2sAnNayZg5QeehSIG', '2025-08-05 13:31:34', NULL),
(4, 'adm', 'adm@gmail.com', '$2y$10$zg7TP2EARQ4FjemjJLYbye8HitGUxfR6.rp0Pab4sQTH.jiVMsZG2', '2025-08-08 12:27:22', NULL),
(5, 'Arnab', 'adn@gmail.com', '$2y$10$rGcnFJVjy9LlCKcnbpt7FuEyvZSzqx0.eEZFhBLhV6Z1lGjzLD1aO', '2025-08-09 09:45:42', NULL),
(7, 'roni', 'ranajitbarik85@gmail.com', '$2y$10$L0gfOwhw1kSWJX.AShfRcezIP77JVByzhC/5.VedLlLEFp7uDcRwO', '2026-03-18 18:24:42', NULL),
(9, 'roni1', 'ranajitbarik2005@gmail.com', '123456', '2026-03-19 18:54:20', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_ips`
--

CREATE TABLE `admin_ips` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_ips`
--

INSERT INTO `admin_ips` (`id`, `admin_id`, `ip_address`, `user_agent`, `login_time`) VALUES
(19, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-14 09:39:25'),
(20, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-14 09:39:34'),
(21, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-17 04:13:18'),
(22, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-17 04:13:22'),
(23, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-27 15:18:22'),
(24, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-27 15:18:24'),
(25, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-27 15:23:56'),
(26, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 12:07:38'),
(27, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 12:07:53'),
(28, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 12:07:54');

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `booking_title` varchar(255) NOT NULL,
  `booking_type` varchar(50) DEFAULT 'standard',
  `booking_date` date DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `number_of_people` int(11) DEFAULT 1,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `booking_details` text DEFAULT NULL,
  `booking_status` enum('pending','confirmed','completed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','cancelled','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `destination_id`, `booking_title`, `booking_type`, `booking_date`, `start_date`, `end_date`, `number_of_people`, `total_amount`, `booking_details`, `booking_status`, `payment_status`, `payment_method`, `transaction_id`, `admin_notes`, `created_at`, `updated_at`) VALUES
(2, 2, 1, 'Flight booking - Bali', 'flight', NULL, '2026-06-01', '2026-06-08', 2, 5000.00, '{\"booking_type\":\"flight\",\"destination_id\":1,\"destination_name\":\"Bali\",\"flight_id\":1,\"hotel_id\":0,\"travelers\":2,\"rooms\":1,\"guests\":2,\"checkin_date\":\"2026-06-01\",\"checkout_date\":\"2026-06-08\",\"total_cost\":5000,\"booking_timestamp\":\"2026-05-27 12:00:54\"}', 'pending', 'pending', NULL, NULL, '', '2026-05-27 12:00:54', '2026-05-27 12:16:14'),
(3, 2, 1, 'Hotel booking - Bali', 'hotel', NULL, '2026-06-06', '2026-06-11', 2, 8000.00, '{\"booking_type\":\"hotel\",\"destination_id\":1,\"destination_name\":\"Bali\",\"flight_id\":0,\"hotel_id\":1,\"travelers\":2,\"rooms\":1,\"guests\":2,\"checkin_date\":\"2026-06-06\",\"checkout_date\":\"2026-06-11\",\"total_cost\":8000,\"booking_timestamp\":\"2026-05-27 12:00:54\"}', 'confirmed', 'pending', NULL, NULL, 'hii', '2026-05-27 12:00:54', '2026-05-27 12:15:41'),
(4, 2, 5, 'Trip to Santorini', 'package', NULL, '2026-05-27', '2026-06-03', 2, 38000.00, '{\"hotel_id\":100,\"flight_id\":200,\"hotel_name\":\"Budget Inn\",\"airline\":\"IndiGo\",\"hotel_total\":28000,\"flight_total\":10000,\"booking_ref\":\"TMD08033AA20260527\",\"departure_from\":\"\"}', 'confirmed', 'paid', '', '', NULL, '2026-05-27 12:01:12', '2026-05-27 12:14:57');

-- --------------------------------------------------------

--
-- Table structure for table `booking_payments`
--

CREATE TABLE `booking_payments` (
  `id` int(11) NOT NULL,
  `booking_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `receipt_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contributors`
--

CREATE TABLE `contributors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL COMMENT 'Full Name',
  `username` varchar(100) NOT NULL COMMENT 'Unique Username',
  `email` varchar(255) NOT NULL COMMENT 'Unique Email Address',
  `password` varchar(255) NOT NULL COMMENT 'Hashed Password',
  `mobile` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other','Prefer not to say') DEFAULT 'Prefer not to say',
  `address_line_1` varchar(255) DEFAULT NULL,
  `address_line_2` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state_region` varchar(100) DEFAULT NULL,
  `postal_code` varchar(50) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `social_link` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contributors`
--

INSERT INTO `contributors` (`id`, `name`, `username`, `email`, `password`, `mobile`, `dob`, `gender`, `address_line_1`, `address_line_2`, `city`, `state_region`, `postal_code`, `country`, `social_link`, `bio`, `created_at`) VALUES
(1, 'ranajit barik', 'ranajitbarik00', 'ranajitbarik00@gmail.com', '$2y$10$drogL8ws8MT.v5XCvTVIhulWk20S9dXt5C/6Z3qXtdRYIwh5gcCPu', '1234567890', NULL, 'Prefer not to say', '', '', '', '', '', '', '', '', '2026-04-08 18:23:03'),
(2, 'ranajit barik', 'ranajitbarik0', 'ranajitbarik0@gmail.com', '$2y$10$3ZHTLyOeij7I6cRTcTXoN.KzzPS2SY50HYisVstoqnNVzqJAEXv8q', '1234567890', '2026-04-09', 'Male', 'madhuban ,chakdaha', '', 'bishnupur', 'west bangal', '722122', 'indian', '', '', '2026-04-08 18:25:52');

-- --------------------------------------------------------

--
-- Table structure for table `contributor_destinations`
--

CREATE TABLE `contributor_destinations` (
  `id` int(11) NOT NULL,
  `contributor_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contributor_destinations`
--

INSERT INTO `contributor_destinations` (`id`, `contributor_id`, `destination_id`, `status`, `admin_notes`, `reviewed_by`, `submitted_at`, `reviewed_at`) VALUES
(1, 2, 12, 'pending', NULL, NULL, '2026-04-11 15:40:16', NULL),
(2, 2, 13, 'pending', NULL, NULL, '2026-04-14 03:29:01', NULL),
(4, 2, 14, 'pending', NULL, NULL, '2026-04-14 08:41:41', NULL),
(5, 2, 15, 'pending', NULL, NULL, '2026-04-14 08:43:41', NULL),
(6, 2, 16, 'pending', NULL, NULL, '2026-04-14 08:45:13', NULL),
(7, 2, 17, 'pending', NULL, NULL, '2026-04-14 08:46:24', NULL),
(8, 2, 18, 'pending', NULL, NULL, '2026-04-14 08:46:48', NULL),
(9, 2, 19, 'pending', NULL, NULL, '2026-04-14 08:46:57', NULL),
(10, 2, 20, 'pending', NULL, NULL, '2026-04-14 08:50:37', NULL),
(11, 2, 21, 'pending', NULL, NULL, '2026-04-14 09:03:57', NULL),
(12, 2, 22, 'pending', NULL, NULL, '2026-04-14 09:09:31', NULL),
(13, 2, 23, 'pending', NULL, NULL, '2026-04-14 09:12:18', NULL),
(14, 2, 24, 'pending', NULL, NULL, '2026-04-27 15:28:12', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `contributor_earnings`
--

CREATE TABLE `contributor_earnings` (
  `id` int(11) NOT NULL,
  `contributor_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL COMMENT 'contributor_destinations.id',
  `booking_id` int(11) DEFAULT NULL COMMENT 'FK to bookings table if applicable',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Location latitude',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Location longitude',
  `budget` decimal(10,2) NOT NULL,
  `best_season` varchar(100) DEFAULT NULL,
  `map_link` varchar(255) DEFAULT NULL,
  `attractions` text DEFAULT NULL COMMENT 'List of tourist attractions',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `season` varchar(50) DEFAULT NULL COMMENT 'Best season to visit',
  `people` text DEFAULT NULL COMMENT 'JSON array of recommended group sizes',
  `tips` text DEFAULT NULL COMMENT 'JSON array of travel tips',
  `cuisines` text DEFAULT NULL COMMENT 'JSON array of local cuisines',
  `cuisine_images` text DEFAULT NULL COMMENT 'JSON object of cuisine images',
  `language` text DEFAULT NULL COMMENT 'JSON array of local languages',
  `profile_pic` varchar(255) DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Structured image data (from File1)' CHECK (json_valid(`images`)),
  `image_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Structured image data (from File2)' CHECK (json_valid(`image_urls`)),
  `state_code` varchar(10) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'India',
  `region` varchar(100) DEFAULT NULL,
  `submitted_by_type` enum('admin','contributor') DEFAULT 'admin',
  `submitted_by_id` int(11) DEFAULT NULL,
  `submission_status` enum('pending','approved','rejected') DEFAULT 'approved',
  `contributor_id` int(11) DEFAULT NULL COMMENT 'contributor who submitted this destination',
  `commission_rate` decimal(5,2) DEFAULT 5.00 COMMENT 'commission % per booking'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `type`, `description`, `location`, `latitude`, `longitude`, `budget`, `best_season`, `map_link`, `attractions`, `created_at`, `updated_at`, `season`, `people`, `tips`, `cuisines`, `cuisine_images`, `language`, `profile_pic`, `images`, `image_urls`, `state_code`, `country`, `region`, `submitted_by_type`, `submitted_by_id`, `submission_status`, `contributor_id`, `commission_rate`) VALUES
(7, 'Agra', 'city', 'Agra is a historical city in Uttar Pradesh, India, renowned for its Mughal-era architecture, particularly the Taj Mahal. It is a major tourist destination, attracting millions due to its rich history and iconic landmarks. Besides the Taj Mahal, Agra is also home to Agra Fort and Fatehpur Sikri, all UNESCO World Heritage Sites.', 'Uttar Pradesh, India', 0.00000000, 0.00000000, 800.00, NULL, 'https://www.google.com/maps/place/Agra,+Uttar+Pradesh/@27.1761263,77.9800127,12z/data=!4m6!3m5!1s0x39740d857c2f41d9:0x784aef38a9523b42!8m2!3d27.1766701!4d78.0080745!16zL20vMDF6eHg5?entry=ttu&g_ep=EgoyMDI2MDIxOC4wIKXMDSoASAFQAw%3D%3D', '[\"Taj Mahal\",\"Agra Fort\",\"Imtad-ud-Daula\"]', '2025-08-10 14:46:33', '2026-04-08 18:19:59', 'winter,summer,spring,autumn,monsoon', '[\"1\",\"2\",\"3-5\"]', '[]', '[]', '{\"Biryani\":\"690b7fcbb5263_Biryani.jpg\"}', '[]', NULL, '[\"69d6702f6e04d_agra.jpg\"]', '[\"68c59b22a1f03_agra.jpg\"]', NULL, 'India', NULL, 'admin', NULL, 'approved', NULL, 5.00),
(8, 'Mumbai', 'city', 'Mumbai, the capital of Maharashtra, is a bustling metropolis that never sleeps. Known as the City of Dreams, it is the financial, commercial, and entertainment hub of India. Home to Bollywood, the world\\\'s largest film industry, Mumbai offers a unique blend of colonial architecture, modern skyscrapers, and vibrant street life. The iconic Gateway of India, Marine Drive, and the ancient Elephanta Caves are must-visit attractions. The city\\\'s spirit is embodied by the dabbawalas (lunchbox carriers) and the colorful Ganesh Chaturthi festival. Despite its fast pace, Mumbai retains a heartwarming charm with its beaches, street food stalls serving vada pav and pav bhaji, and the historic Chhatrapati Shivaji Terminus, a UNESCO World Heritage Site. Whether you\\\'re exploring the art galleries of Kala Ghoda or walking along the promenade at Girgaon Chowpatty, Mumbai promises an unforgettable urban adventure.', 'Maharashtra, India', 19.07600000, 72.87770000, 2500.00, 'October to February', 'https://www.google.com/maps/place/Mumbai,+Maharashtra/@19.07609,72.877426,12z/data=!3m1!4b1!4m6!3m5!1s0x3be7c6306644edc1:0x5da4ed8f8d648c69!8m2!3d19.0759837!4d72.8776559!16zL20vMDR2bXA?entry=ttu', '[\"Gateway of India\",\"Marine Drive\",\"Elephanta Caves\",\"Chhatrapati Shivaji Terminus\",\"Juhu Beach\"]', '2026-05-15 06:11:36', '2026-05-16 12:15:10', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[]', '[\"Butter Chicken\"]', '[]', '[]', NULL, '[\"mumbai_gateway.jpg\",\"mumbai_marine_drive.jpg\",\"mumbai_elephanta.jpg\",\"6a085fce20a5a_mumbai_marine_drive.jpg\"]', '[\"mumbai_skyline.jpg\",\"mumbai_juhu_beach.jpg\"]', 'MH', 'India', 'West', 'admin', NULL, 'approved', NULL, 5.00),
(9, 'Bangalore', 'city', 'Bangalore, officially known as Bengaluru, is the capital of Karnataka and the Silicon Valley of India. This vibrant city seamlessly blends its rich cultural heritage with a modern, tech-driven lifestyle. Known for its pleasant climate throughout the year, Bangalore is dotted with lush green parks, historic temples, and bustling IT parks. The magnificent Bangalore Palace, the serene Lalbagh Botanical Garden, and the vibrant commercial street of MG Road are major draws. As India\'s pub capital, the city offers a thriving nightlife with countless microbreweries and trendy cafes. The Vidhana Soudha, an imposing legislative building, showcases Dravidian architecture. Food lovers can explore diverse cuisines, from traditional South Indian filter coffee and masala dosa to global fusion dishes. Whether you\'re a tech professional, a history buff, or a nature lover, Bangalore\'s cosmopolitan energy and greenery make it a delightful destination year-round.', 'Karnataka, India', 12.97160000, 77.59460000, 2200.00, 'September to February', 'https://www.google.com/maps/place/Bangalore,+Karnataka/@12.9715987,77.5945627,12z/data=!3m1!4b1!4m6!3m5!1s0x3bae1670c9b44e6d:0xf8dfc3e8517e4fe0!8m2!3d12.9715987!4d77.5945627!16zL20vMDljN3c?entry=ttu', '[\"Bangalore Palace\",\"Lalbagh Botanical Garden\",\"Vidhana Soudha\",\"Cubbon Park\",\"Wonderla Amusement Park\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn,monsoon', '[\"1\",\"2\",\"3-5\",\"6-10\"]', '[\"Use metro to avoid traffic\",\"Try filter coffee at a local darshini\",\"Weekends are best for pub hopping in Indiranagar\"]', '[\"Masala Dosa\",\"Idli-Sambar\",\"Filter Coffee\",\"Bisi Bele Bath\"]', '{\"Masala Dosa\":\"bangalore_masala_dosa.jpg\",\"Filter Coffee\":\"bangalore_filter_coffee.jpg\",\"Bisi Bele Bath\":\"bangalore_bisi_bele_bath.jpg\"}', '[\"Kannada\",\"English\",\"Hindi\",\"Tamil\",\"Telugu\"]', NULL, '[\"bangalore_palace.jpg\",\"bangalore_lalbagh.jpg\",\"bangalore_vidhana_soudha.jpg\"]', '[\"bangalore_ub_city.jpg\",\"bangalore_church_street.jpg\"]', 'KA', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(10, 'Kolkata', 'city', 'Kolkata, the capital of West Bengal, is known as the City of Joy. This cultural and intellectual capital of India exudes old-world charm with its colonial-era architecture, bustling markets, and artistic fervor. Home to the iconic Howrah Bridge, the majestic Victoria Memorial, and the sacred Dakshineswar Kali Temple, Kolkata offers a rich tapestry of history and spirituality. The city is famous for its love of literature, art, and cinema, being the birthplace of Nobel laureate Rabindranath Tagore. Street food is legendary here, with phuchka, kathi rolls, and mishti doi delighting every palate. The vibrant Durga Puja festival transforms the city into a grand art exhibition. A ride in the historic yellow taxis or a walk through the College Street book market feels like stepping back in time. Kolkata\'s warmth, intellectual buzz, and timeless charm make it a must-visit destination for those seeking authentic Indian culture.', 'West Bengal, India', 22.57260000, 88.36390000, 1800.00, 'October to March', 'https://www.google.com/maps/place/Kolkata,+West+Bengal/@22.572646,88.363895,12z/data=!3m1!4b1!4m6!3m5!1s0x3a0277b5ce54fec7:0x77a2b8b6df671a56!8m2!3d22.572646!4d88.363895!16zL20vMDN5cWo?entry=ttu', '[\"Victoria Memorial\",\"Howrah Bridge\",\"Dakshineswar Kali Temple\",\"Belur Math\",\"Indian Museum\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Take a hand-pulled rickshaw ride\",\"Try phuchka from a street vendor\",\"Visit College Street for rare books\"]', '[\"Phuchka\",\"Kathi Roll\",\"Mishti Doi\",\"Rosogolla\",\"Kosha Mangsho\"]', '{\"Phuchka\":\"kolkata_phuchka.jpg\",\"Kathi Roll\":\"kolkata_kathi_roll.jpg\",\"Mishti Doi\":\"kolkata_mishti_doi.jpg\"}', '[\"Bengali\",\"Hindi\",\"English\"]', NULL, '[\"kolkata_victoria.jpg\",\"kolkata_howrah_bridge.jpg\",\"kolkata_dakshineswar.jpg\"]', '[\"kolkata_college_street.jpg\",\"kolkata_princep_ghat.jpg\"]', 'WB', 'India', 'East', 'admin', NULL, 'approved', NULL, 5.00),
(11, 'Chennai', 'city', 'Chennai, the capital of Tamil Nadu, is a major cultural and economic hub of South India. Formerly known as Madras, this coastal city is famous for its pristine beaches, ancient temples, and classical arts. The Marina Beach, one of the longest urban beaches in the world, is a popular spot for locals and tourists alike. Chennai is the gateway to South Indian temple architecture, with the Kapaleeshwarar Temple and Parthasarathy Temple showcasing Dravidian grandeur. The city is also the heart of the Tamil film industry, Kollywood. Food lovers can indulge in authentic Chettinad cuisine, crispy dosas, and filter coffee. The historic Fort St. George, the San Thome Basilica, and the bustling T. Nagar market add to the city\'s diverse appeal. Chennai\'s Carnatic music and Bharatanatyam dance performances draw enthusiasts from around the globe. With its blend of tradition and modernity, Chennai offers a warm, enriching experience for every traveler.', 'Tamil Nadu, India', 13.08270000, 80.27070000, 2000.00, 'November to February', 'https://www.google.com/maps/place/Chennai,+Tamil+Nadu/@13.0826802,80.2707184,12z/data=!3m1!4b1!4m6!3m5!1s0x3a5265ea4f7d3361:0x6e61a70b6863d799!8m2!3d13.0826802!4d80.2707184!16zL20vMDFmaW0?entry=ttu', '[\"Marina Beach\",\"Kapaleeshwarar Temple\",\"Fort St. George\",\"San Thome Basilica\",\"Government Museum\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring', '[\"1\",\"2\",\"3-5\",\"6-10\"]', '[\"Use metro train for easy commute\",\"Try authentic filter coffee at a local shop\",\"Visit temples early morning for less crowd\"]', '[\"Masala Dosa\",\"Idli\",\"Sambar\",\"Filter Coffee\",\"Chettinad Chicken\"]', '{\"Masala Dosa\":\"chennai_masala_dosa.jpg\",\"Filter Coffee\":\"chennai_filter_coffee.jpg\",\"Chettinad Chicken\":\"chennai_chettinad_chicken.jpg\"}', '[\"Tamil\",\"English\",\"Telugu\"]', NULL, '[\"chennai_marina_beach.jpg\",\"chennai_kapaleeshwarar.jpg\",\"chennai_fort_st_george.jpg\"]', '[\"chennai_egmore_museum.jpg\",\"chennai_besant_nagar.jpg\"]', 'TN', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(12, 'Jaipur', 'city', 'Jaipur, the capital of Rajasthan, is famously known as the Pink City. This enchanting city is a royal gem of India, renowned for its majestic forts, vibrant bazaars, and rich cultural heritage. Founded in 1727 by Maharaja Sawai Jai Singh II, Jaipur is a UNESCO World Heritage Site and a key part of India\'s Golden Triangle tourist circuit. The magnificent Amer Fort, the astronomical wonder Jantar Mantar, and the stunning Hawa Mahal (Palace of Winds) are architectural marvels. The City Palace still houses the royal family and offers a glimpse into regal opulence. Jaipur\'s bustling markets, like Johari Bazaar and Bapu Bazaar, are perfect for buying jewelry, textiles, and handicrafts. The city is also famous for its elephant festivals, traditional Rajasthani cuisine including dal baati churma, and vibrant folk music and dance performances. With its warm hospitality, rich history, and colorful charm, Jaipur transports visitors to an era of maharajas and grandeur.', 'Rajasthan, India', 26.91240000, 75.78730000, 2300.00, 'October to March', 'https://www.google.com/maps/place/Jaipur,+Rajasthan/@26.9124336,75.7872709,12z/data=!3m1!4b1!4m6!3m5!1s0x396c4adf4c57e281:0xce1c63a0cf22e09!8m2!3d26.9124336!4d75.7872709!16zL20vMDNkZ2I?entry=ttu', '[\"Amer Fort\",\"Hawa Mahal\",\"City Palace\",\"Jantar Mantar\",\"Nahargarh Fort\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Hire a guide for Amer Fort\",\"Bargain in local markets\",\"Try authentic dal baati churma\"]', '[\"Dal Baati Churma\",\"Laal Maas\",\"Gatte Ki Sabzi\",\"Pyaaz Kachori\"]', '{\"Dal Baati Churma\":\"jaipur_dal_baati.jpg\",\"Laal Maas\":\"jaipur_laal_maas.jpg\",\"Pyaaz Kachori\":\"jaipur_pyaaz_kachori.jpg\"}', '[\"Rajasthani\",\"Hindi\",\"English\"]', NULL, '[\"jaipur_amer_fort.jpg\",\"jaipur_hawa_mahal.jpg\",\"jaipur_city_palace.jpg\"]', '[\"jaipur_jal_mahal.jpg\",\"jaipur_jantar_mantar.jpg\"]', 'RJ', 'India', 'North', 'admin', NULL, 'approved', NULL, 5.00),
(13, 'Mawlynnong', 'village', 'Mawlynnong, located in the East Khasi Hills of Meghalaya, is famously known as Asia\'s cleanest village. This picturesque village offers a pristine environment with neatly swept paths, bamboo dustbins, and well-maintained gardens. The village is renowned for its living root bridges, a natural wonder created by training rubber tree roots across streams. The most famous is the double-decker living root bridge in nearby Nongriat. Mawlynnong also offers breathtaking views of the Bangladesh plains from a skywalk constructed entirely of bamboo. The Khasi tribe here maintains a matrilineal society, adding cultural intrigue. Visitors can enjoy orchards filled with betel nuts, local handicrafts, and warm hospitality. The village promotes eco-tourism with homestays that offer authentic Khasi cuisine like jadoh (rice with meat) and tungrymbai (fermented soybean). Mawlynnong is a testament to how community effort can create a sustainable, beautiful living space, making it a must-visit for nature and culture enthusiasts.', 'Meghalaya, India', 25.21000000, 91.92000000, 1200.00, 'October to April', 'https://www.google.com/maps/place/Mawlynnong,+Meghalaya/@25.21009,91.92006,15z/data=!3m1!4b1!4m6!3m5!1s0x3751cbbd9ea2f183:0xfc1be46ce3413591!8m2!3d25.21009!4d91.92006!16zL20vMGZka2pm?entry=ttu', '[\"Living Root Bridges\",\"Skywalk\",\"Mawlynnong Waterfall\",\"Church of Epiphany\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Stay in a local homestay\",\"Carry insect repellent\",\"Respect local customs and cleanliness\"]', '[\"Jadoh\",\"Tungrymbai\",\"Dohneiiong\",\"Pukhlein\"]', '{\"Jadoh\":\"mawlynnong_jadoh.jpg\",\"Tungrymbai\":\"mawlynnong_tungrymbai.jpg\",\"Dohneiiong\":\"mawlynnong_dohneiiong.jpg\"}', '[\"Khasi\",\"English\"]', NULL, '[\"mawlynnong_village.jpg\",\"mawlynnong_root_bridge.jpg\",\"mawlynnong_skywalk.jpg\"]', '[\"mawlynnong_clean_streets.jpg\",\"mawlynnong_waterfall.jpg\"]', 'ML', 'India', 'Northeast', 'admin', NULL, 'approved', NULL, 5.00),
(14, 'Khimsar', 'village', 'Khimsar is a charming village in the Nagaur district of Rajasthan, known for its majestic sand dunes and the magnificent Khimsar Fort. This rustic desert village offers an authentic rural Rajasthan experience away from the crowded tourist circuits. The 16th-century Khimsar Fort, now a heritage hotel, stands as a testament to Rajput valor and architecture. Visitors can explore the surrounding Thar Desert on camel safaris, witnessing breathtaking sunsets over golden dunes. The village also features the unique Karani Mata Temple, famously known as the \"rat temple,\" though the more famous one is in Bikaner. Khimsar is surrounded by scrubland and small lakes that attract migratory birds during winter. Local crafts include pottery, leatherwork, and traditional puppets. The village\'s cuisine features hearty Rajasthani dishes like ker sangri and bajre ki roti. Khimsar\'s rustic charm, combined with desert adventures and heritage stays, offers a peaceful escape into Rajasthan\'s timeless landscape, perfect for those seeking solitude and cultural immersion.', 'Rajasthan, India', 26.97000000, 73.40000000, 1500.00, 'October to February', 'https://www.google.com/maps/place/Khimsar,+Rajasthan/@26.97062,73.39965,14z/data=!3m1!4b1!4m6!3m5!1s0x396ad87c3b539745:0x3f7a1f021f53c4e2!8m2!3d26.97062!4d73.39965!16zL20vMGNmbXNi?entry=ttu', '[\"Khimsar Fort\",\"Sand Dunes\",\"Camel Safari\",\"Birds Sanctuary\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Book a camel safari at sunset\",\"Stay overnight at Khimsar Fort\",\"Try local ker sangri dish\"]', '[\"Ker Sangri\",\"Bajre Ki Roti\",\"Lapsi\",\"Chaach\"]', '{\"Ker Sangri\":\"khimsar_ker_sangri.jpg\",\"Bajre Ki Roti\":\"khimsar_bajra_roti.jpg\",\"Lapsi\":\"khimsar_lapsi.jpg\"}', '[\"Rajasthani\",\"Hindi\"]', NULL, '[\"khimsar_fort.jpg\",\"khimsar_sand_dunes.jpg\",\"khimsar_camel_safari.jpg\"]', '[\"khimsar_village_life.jpg\",\"khimsar_sunset.jpg\"]', 'RJ', 'India', 'North', 'admin', NULL, 'approved', NULL, 5.00),
(15, 'Hodka', 'village', 'Hodka is a traditional village in the Kutch district of Gujarat, famous for its authentic Bhungas (mud huts) and rich handicraft traditions. This desert village offers an immersive experience into the lifestyle of the Rabari and Jat communities. The round, thatch-roofed Bhungas are architectural marvels with intricate mirror work and wall paintings that keep interiors cool in summer and warm in winter. Hodka is a hub for traditional crafts including embroidery, bandhani tie-dye, leatherwork, and pottery. The village is located near the White Rann of Kutch, a vast salt desert that looks magical under a full moon. Local women wearing colorful traditional attire create stunning visual contrasts against the arid landscape. Visitors can participate in folk music and dance performances around bonfires. The cuisine features regional specialties like khad (lamb cooked underground) and bajra roti with garlic chutney. Hodka\'s community-based tourism model ensures that your visit directly supports local artisans, making it a meaningful and culturally rich destination.', 'Gujarat, India', 23.75000000, 69.52000000, 1300.00, 'October to February', 'https://www.google.com/maps/place/Hodka,+Gujarat/@23.74164,69.51946,14z/data=!3m1!4b1!4m6!3m5!1s0x3950b7a8180a27b7:0x97738adab227371c!8m2!3d23.74164!4d69.51946!16s%2Fg%2F11f5m3n9w_?entry=ttu', '[\"Traditional Bhungas\",\"Handicraft Workshop\",\"Rann of Kutch\",\"Folk Dance Performance\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter', '[\"2\",\"3-5\"]', '[\"Attend a handicraft workshop\",\"Stay in a traditional Bhunga\",\"Visit during Rann Utsav festival\"]', '[\"Khad\",\"Dabeli\",\"Kutchhi Dabeli\",\"Bajra Roti\"]', '{\"Khad\":\"hodka_khad.jpg\",\"Dabeli\":\"hodka_dabeli.jpg\",\"Bajra Roti\":\"hodka_bajra_roti.jpg\"}', '[\"Kutchhi\",\"Gujarati\",\"Hindi\"]', NULL, '[\"hodka_bhunga.jpg\",\"hodka_embroidery.jpg\",\"hodka_rann.jpg\"]', '[\"hodka_village_scene.jpg\",\"hodka_pottery.jpg\"]', 'GJ', 'India', 'West', 'admin', NULL, 'approved', NULL, 5.00),
(16, 'Malana', 'village', 'Malana is an ancient, isolated village in the Kullu district of Himachal Pradesh, often called the \"Little Greece\" of India. Nestled at 2,652 meters in the Parvati Valley, this village has preserved one of the world\'s oldest democracies and distinct cultural traditions for over 4,000 years. The inhabitants, known as Kanets, believe they are descendants of Alexander the Great\'s army. Malana has its own language (Kanashi), legal system (Malana Panchayat), and strict rules forbidding outsiders from touching villagers or their belongings. The village is famous for the Malana cream, a strain of hashish, though it\'s legally restricted. Trekkers are drawn to the challenging but rewarding trek through dense forests and waterfalls leading to this hidden gem. The ancient Jamdagni Rishi temple and Renuka Devi temple are architecturally unique with wooden carvings. The views of the surrounding Deo Tibba and Chanderkhani peaks are breathtaking. Visitors must respect local customs, avoid photography near temples, and maintain a respectful distance. Malana offers a fascinating glimpse into a self-sufficient, isolated Himalayan culture.', 'Himachal Pradesh, India', 32.06000000, 77.27000000, 1400.00, 'May to October', 'https://www.google.com/maps/place/Malana,+Himachal+Pradesh/@32.06469,77.26434,15z/data=!3m1!4b1!4m6!3m5!1s0x3905d18afadff8e7:0xd4a1b9ff81c4ff4!8m2!3d32.06469!4d77.26434!16zL20vMGNqX2Jt?entry=ttu', '[\"Jamdagni Rishi Temple\",\"Malana Village Tour\",\"Parvati Valley View\",\"Waterfall Trek\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'summer,spring,autumn', '[\"1\",\"2\"]', '[\"Respect local customs: no touching\",\"Avoid photography near temples\",\"Hire a local guide for trek\"]', '[\"Siddu\",\"Dham\",\"Madra\",\"Thenthuk\"]', '{\"Siddu\":\"malana_siddu.jpg\",\"Dham\":\"malana_dham.jpg\",\"Madra\":\"malana_madra.jpg\"}', '[\"Kanashi\",\"Hindi\"]', NULL, '[\"malana_village.jpg\",\"malana_temples.jpg\",\"malana_valley.jpg\"]', '[\"malana_trek_path.jpg\",\"malana_falls.jpg\"]', 'HP', 'India', 'North', 'admin', NULL, 'approved', NULL, 5.00),
(17, 'Pelling', 'village', 'Pelling is a beautiful village in the Gyalshing district of Sikkim, offering stunning panoramic views of the Kanchenjunga range. Perched at 2,150 meters, this serene Himalayan village is a hidden gem for nature lovers and adventure seekers. Pelling is famous for the 17th-century Pemayangtse Monastery, one of the oldest and most important monasteries in Sikkim, featuring ancient murals and statues. The Rabdentse Ruins, the former capital of the Kingdom of Sikkim, offer a historical walk through forested trails. The Khecheopalri Lake, considered sacred by both Buddhists and Hindus, is surrounded by pristine forests and is believed to fulfill wishes. The impressive Sanga Choeling Monastery, accessible via a short trek, provides stunning valley views. Adventure activities include mountain biking, trekking to Kanchenjunga base camp, and bird watching. Local cuisine includes momos, thukpa, and the famous Sikkimese fermented dish, gundruk. Pelling also boasts the highest waterfall in Sikkim, the Kanchenjunga Falls. With its peaceful monasteries, breathtaking mountain views, and warm hospitality, Pelling is a perfect retreat for spiritual and nature tourism.', 'Sikkim, India', 27.30000000, 88.23000000, 1600.00, 'October to June', 'https://www.google.com/maps/place/Pelling,+Sikkim/@27.29971,88.23003,15z/data=!3m1!4b1!4m6!3m5!1s0x39e924cc990dc1d7:0xb1821a51c13b978f!8m2!3d27.29971!4d88.23003!16zL20vMGNqel9q?entry=ttu', '[\"Pemayangtse Monastery\",\"Khecheopalri Lake\",\"Rabdentse Ruins\",\"Kanchenjunga Falls\",\"Sanga Choeling Monastery\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,summer,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Start early for clear mountain views\",\"Carry warm clothes even in summer\",\"Try local fermented dishes\"]', '[\"Momos\",\"Thukpa\",\"Gundruk\",\"Saelroti\"]', '{\"Momos\":\"pelling_momos.jpg\",\"Thukpa\":\"pelling_thukpa.jpg\",\"Gundruk\":\"pelling_gundruk.jpg\"}', '[\"Nepali\",\"Sikkimese\",\"English\",\"Hindi\"]', NULL, '[\"pelling_monastery.jpg\",\"pelling_lake.jpg\",\"pelling_kanchenjunga.jpg\"]', '[\"pelling_waterfall.jpg\",\"pelling_sunrise.jpg\"]', 'SK', 'India', 'Northeast', 'admin', NULL, 'approved', NULL, 5.00),
(18, 'Hampi', 'historical', 'Hampi, located in Karnataka, is a UNESCO World Heritage Site that was once the glorious capital of the Vijayanagara Empire. Spread across a surreal landscape of boulder-strewn hills, this ancient city is home to over 500 magnificent ruins spanning 26 square kilometers. The Virupaksha Temple, still in active worship, features intricate carvings and a towering gopuram. The Vittala Temple with its iconic stone chariot and musical pillars showcases exceptional Dravidian architecture. The Royal Enclosure includes the massive Mahanavami Dibba platform and the King\'s Balance where kings were weighed against gold and gems. The Elephant Stables, Lotus Mahal, and the underground Shiva temple are architectural marvels. The Tungabhadra River flowing through the ruins adds to the ethereal beauty. Hampi\'s boulder hills are perfect for climbing at sunrise. The local cuisine includes the traditional Karnataka meal served on a banana leaf. Hampi\'s spiritual aura, architectural brilliance, and laid-back hippie culture on nearby Hippie Island make it a unique destination for history buffs, trekkers, and backpackers alike.', 'Karnataka, India', 15.33500000, 76.46000000, 1000.00, 'October to February', 'https://www.google.com/maps/place/Hampi,+Karnataka/@15.33501,76.46002,14z/data=!3m1!4b1!4m6!3m5!1s0x3bb74e7d977c17e9:0x6f08e6e3a8ffb74f!8m2!3d15.33501!4d76.46002!16zL20vMDJqMzI?entry=ttu', '[\"Virupaksha Temple\",\"Vittala Temple with Stone Chariot\",\"Hampi Bazaar\",\"Royal Enclosure\",\"Elephant Stables\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Rent a bicycle or scooter to explore ruins\",\"Watch sunset from Matanga Hill\",\"Hire a guide for historical context\"]', '[\"Bisi Bele Bath\",\"Jolada Roti\",\"Shenga Chutney\",\"Obattu\"]', '{\"Bisi Bele Bath\":\"hampi_bisi_bele_bath.jpg\",\"Jolada Roti\":\"hampi_jolada_roti.jpg\",\"Obattu\":\"hampi_obattu.jpg\"}', '[\"Kannada\",\"English\",\"Hindi\"]', NULL, '[\"hampi_virupaksha.jpg\",\"hampi_vittala.jpg\",\"hampi_stone_chariot.jpg\"]', '[\"hampi_boulders.jpg\",\"hampi_tungabhadra.jpg\"]', 'KA', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(19, 'Gwalior', 'historical', 'Gwalior, a historic city in Madhya Pradesh, is renowned for its imposing hilltop fort often called the \"Gibraltar of India.\" The Gwalior Fort, one of the largest fort complexes in the country, has witnessed over a thousand years of history and houses two magnificent palaces, Man Mandir and Gujari Mahal. The fort walls display ancient blue-tiled mosaics and carvings of Jain Tirthankaras, with the colossal 57-foot statue of Adinath carved into the rock face. The Sas Bahu Temples, despite the unusual name, are intricately carved sandstone temples dedicated to Vishnu. The Jai Vilas Palace, still home to the Scindia royal family, features a museum with a silver train that served as a dinner wagon and the world\'s largest chandeliers. The Sun Temple, modeled after Konark, and the historic Tansen\'s Tomb, where the legendary musician is buried, add cultural depth. Gwalior is also the birthplace of the great musician Tansen, and the annual Tansen Music Festival draws classical music lovers from across India. The city\'s street food, especially kachoris and bedai, is legendary.', 'Madhya Pradesh, India', 26.22000000, 78.18000000, 1200.00, 'October to March', 'https://www.google.com/maps/place/Gwalior,+Madhya+Pradesh/@26.21829,78.17899,12z/data=!3m1!4b1!4m6!3m5!1s0x397c43bf023fb625:0xbe04c20d6efb7908!8m2!3d26.218287!4d78.182831!16zL20vMDF3MDY?entry=ttu', '[\"Gwalior Fort\",\"Jai Vilas Palace\",\"Sas Bahu Temples\",\"Tansen Tomb\",\"Sun Temple\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\",\"6-10\"]', '[\"Visit Gwalior Fort by ropeway\",\"Attend Tansen Music Festival in December\",\"Try local bedai and kachori breakfast\"]', '[\"Bedai\",\"Kachori\",\"Poha\",\"Chaat\",\"Bada\"]', '{\"Bedai\":\"gwalior_bedai.jpg\",\"Kachori\":\"gwalior_kachori.jpg\",\"Poha\":\"gwalior_poha.jpg\"}', '[\"Hindi\",\"English\"]', NULL, '[\"gwalior_fort.jpg\",\"gwalior_jai_vilas.jpg\",\"gwalior_sas_bahu.jpg\"]', '[\"gwalior_tansen_tomb.jpg\",\"gwalior_sun_temple.jpg\"]', 'MP', 'India', 'Central', 'admin', NULL, 'approved', NULL, 5.00),
(20, 'Mysore', 'historical', 'Mysore, the cultural capital of Karnataka, is famous for its majestic Mysore Palace, one of India\'s most spectacular royal residences. This city of palaces, gardens, and sandalwood is steeped in history and tradition. The Mysore Palace, illuminated by 97,000 bulbs during Dasara, showcases a blend of Indo-Saracenic, Hindu, Muslim, Rajput, and Gothic architectural styles. The Chamundeshwari Temple, perched atop Chamundi Hill, offers panoramic city views and features a massive Nandi bull statue. The Jaganmohan Palace, now an art gallery, houses priceless paintings and artifacts. The St. Philomena\'s Church, one of Asia\'s largest churches, boasts stunning neo-Gothic architecture. The Brindavan Gardens with its musical fountain show is a delightful evening attraction. Mysore is synonymous with sandalwood products, silk sarees, and the traditional Mysore Pak sweet. The city\'s Dasara festival is a 10-day extravaganza featuring a grand procession of decorated elephants, cultural performances, and illuminated palaces. The nearby Srirangapatna, the capital of Hyder Ali and Tipu Sultan, adds historical depth to any visit. Mysore\'s clean, green streets and relaxed vibe make it a perfect heritage destination.', 'Karnataka, India', 12.29580000, 76.63940000, 1500.00, 'October to February', 'https://www.google.com/maps/place/Mysore,+Karnataka/@12.295811,76.639381,12z/data=!3m1!4b1!4m6!3m5!1s0x3baf6b45f0058deb:0xefb1bbebac90afb8!8m2!3d12.2958104!4d76.6393807!16zL20vMDR4Mm0?entry=ttu', '[\"Mysore Palace\",\"Chamundeshwari Temple\",\"Brindavan Gardens\",\"St. Philomena\'s Church\",\"Mysore Zoo\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn,monsoon', '[\"1\",\"2\",\"3-5\"]', '[\"Visit Mysore Palace on Sundays for illumination\",\"Buy authentic Mysore silk at government showroom\",\"Try Mysore Pak from original Guru Sweets\"]', '[\"Mysore Pak\",\"Masala Dosa\",\"Bisi Bele Bath\",\"Chiroti\"]', '{\"Mysore Pak\":\"mysore_mysore_pak.jpg\",\"Masala Dosa\":\"mysore_masala_dosa.jpg\",\"Chiroti\":\"mysore_chiroti.jpg\"}', '[\"Kannada\",\"English\",\"Tamil\",\"Hindi\"]', NULL, '[\"mysore_palace.jpg\",\"mysore_chamundi_hill.jpg\",\"mysore_brindavan.jpg\"]', '[\"mysore_church.jpg\",\"mysore_dasara.jpg\"]', 'KA', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(21, 'Khajuraho', 'historical', 'Khajuraho, a small town in Madhya Pradesh, is world-famous for its stunning group of Hindu and Jain temples renowned for their erotic sculptures. These UNESCO World Heritage Site temples were built by the Chandela dynasty between 950 and 1050 AD, with only 22 of the original 85 surviving today. The Western Group, including the magnificent Kandariya Mahadev Temple, features intricate carvings depicting gods, goddesses, musicians, warriors, and yes, sensuous figures representing tantric practices and the celebration of life. The temples are masterpieces of Nagara-style architecture with soaring shikharas (spires) and elaborate mandapas (halls). The Eastern Group includes the Jain temples at Parsvanath and Adinath, offering a more peaceful experience. The Southern Group, including the Dulhadev and Chaturbhuj temples, are equally impressive but less visited. The Light and Sound Show at the Western Group brings history to life. The town also hosts an annual Khajuraho Dance Festival featuring classical Indian dance forms. Beyond temples, visitors can enjoy boating in Beni Sagar Lake, visit the Raneh Falls canyon, or explore the Panna National Park nearby. Khajuraho remains a testament to medieval India\'s artistic brilliance and spiritual openness.', 'Madhya Pradesh, India', 24.85000000, 79.93000000, 1300.00, 'October to March', 'https://www.google.com/maps/place/Khajuraho,+Madhya+Pradesh/@24.85092,79.93222,13z/data=!3m1!4b1!4m6!3m5!1s0x39800ae45dfa4329:0x3b88345b3258c85f!8m2!3d24.85092!4d79.93222!16zL20vMGQ5ZDA?entry=ttu', '[\"Kandariya Mahadev Temple\",\"Lakshmana Temple\",\"Viswanath Temple\",\"Parsvanath Temple (Jain)\",\"Raneh Falls\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Hire a guide for historical context\",\"Attend the sound and light show\",\"Visit sunrise for best photography\"]', '[\"Biryani\",\"Palak Puri\",\"Khajur Pak\",\"Lapsi\"]', '{\"Biryani\":\"khajuraho_biryani.jpg\",\"Palak Puri\":\"khajuraho_palak_puri.jpg\",\"Lapsi\":\"khajuraho_lapsi.jpg\"}', '[\"Hindi\",\"English\",\"Bundeli\"]', NULL, '[\"khajuraho_kandariya.jpg\",\"khajuraho_lakshmana.jpg\",\"khajuraho_erotic.jpg\"]', '[\"khajuraho_raneh_falls.jpg\",\"khajuraho_sunset.jpg\"]', 'MP', 'India', 'Central', 'admin', NULL, 'approved', NULL, 5.00),
(22, 'Badami', 'historical', 'Badami, located in Karnataka\'s Bagalkot district, was the capital of the Chalukya dynasty and is famous for its stunning rock-cut cave temples. Four magnificent caves, carved out of the red sandstone cliffs overlooking the man-made Agastya Lake, showcase Dravidian architecture at its finest. Cave 1 is dedicated to Shiva with a magnificent 18-armed Nataraja (dancing Shiva), Cave 2 honors Vishnu with his Varaha and Trivikrama avatars, Cave 3, the largest, features exquisite carvings of Vishnu riding Garuda, and Cave 4 is a Jain cave with carvings of Mahavira and Tirthankaras. Above the caves, the Badami Fort offers panoramic views and contains ancient inscriptions. The Bhutanatha Temple complex on the lake\'s edge is perfect for sunset views and photography. The Archaeological Museum houses Lajja Gauri, a unique fertility cult sculpture. Nearby attractions include the 5th-century Aihole, known as the \"Cradle of Indian Temple Architecture,\" and Pattadakal, a UNESCO World Heritage Site with intricately carved temples blending northern and southern styles. Badami\'s red sandstone landscape, ancient history, and relative quiet compared to larger sites make it a paradise for history and architecture enthusiasts.', 'Karnataka, India', 15.91480000, 75.67600000, 1100.00, 'October to February', 'https://www.google.com/maps/place/Badami,+Karnataka/@15.91485,75.67601,15z/data=!3m1!4b1!4m6!3m5!1s0x3bb7c3b75d218e9d:0xada48726c9a5c428!8m2!3d15.91485!4d75.67601!16zL20vMDIyeFdr?entry=ttu', '[\"Badami Cave Temples\",\"Agastya Lake\",\"Badami Fort\",\"Bhutanatha Temple\",\"Archaeological Museum\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Visit early morning to avoid heat\",\"Combine with Aihole and Pattadakal\",\"Climb fort for sunset views\"]', '[\"Jolada Rotti\",\"Shenga Chutney\",\"Bisi Bele Bath\",\"Huggi\"]', '{\"Jolada Rotti\":\"badami_jolada_rotti.jpg\",\"Shenga Chutney\":\"badami_shenga_chutney.jpg\",\"Bisi Bele Bath\":\"badami_bisi_bele_bath.jpg\"}', '[\"Kannada\",\"English\",\"Hindi\"]', NULL, '[\"badami_caves.jpg\",\"badami_lake.jpg\",\"badami_fort.jpg\"]', '[\"badami_temple.jpg\",\"badami_sunset.jpg\"]', 'KA', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(23, 'Goa', 'beach', 'Goa, India\'s smallest state by area, is the country\'s ultimate beach destination known for its golden sands, vibrant nightlife, and Portuguese heritage. Divided into North and South Goa, the state offers completely different vibes. North Goa, including Baga, Calangute, and Anjuna beaches, is famous for bustling shacks, trance parties, flea markets, and water sports. South Goa, with Palolem, Benaulim, and Colva beaches, offers serene palm-fringed shores and luxury resorts. Beyond beaches, Old Goa boasts magnificent churches including the Basilica of Bom Jesus, a UNESCO World Heritage Site housing the remains of St. Francis Xavier. The Dudhsagar Falls, cascading 310 meters among lush forests, is a spectacular natural attraction. Goa\'s unique cuisine blends Konkani and Portuguese influences, with fish curry rice, vindaloo, and bebinca being must-tries. The state\'s laid-back susegad attitude, beach shacks serving fresh seafood, dolphin-spotting cruises, spice plantations, and water sports like parasailing and scuba diving make Goa a year-round favorite for domestic and international tourists alike. Whether you seek party or peace, Goa delivers.', 'Goa, India', 15.29930000, 74.12400000, 2800.00, 'November to February', 'https://www.google.com/maps/place/Goa/@15.299326,74.123996,12z/data=!3m1!4b1!4m6!3m5!1s0x3bbfba106336b741:0xeaf887ff62f34092!8m2!3d15.2993265!4d74.123996!16zL20vMDNiNWY?entry=ttu', '[\"Baga Beach\",\"Basilica of Bom Jesus\",\"Dudhsagar Falls\",\"Anjuna Flea Market\",\"Palolem Beach\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring', '[\"1\",\"2\",\"3-5\",\"6-10\"]', '[\"Rent a scooter to explore beaches\",\"Try seafood at beach shacks\",\"Book dolphin trip early morning\"]', '[\"Fish Curry Rice\",\"Prawn Vindaloo\",\"Crab Xec Xec\",\"Bebinca\",\"Feni\"]', '{\"Fish Curry Rice\":\"goa_fish_curry_rice.jpg\",\"Prawn Vindaloo\":\"goa_vindaloo.jpg\",\"Bebinca\":\"goa_bebinca.jpg\"}', '[\"Konkani\",\"Marathi\",\"English\",\"Hindi\",\"Portuguese\"]', NULL, '[\"goa_baga_beach.jpg\",\"goa_basilica.jpg\",\"goa_dudhsagar.jpg\"]', '[\"goa_palolem.jpg\",\"goa_anjuna_market.jpg\"]', 'GA', 'India', 'West', 'admin', NULL, 'approved', NULL, 5.00),
(24, 'Gokarna', 'beach', 'Gokarna, a small temple town in Karnataka, has emerged as a serene alternative to Goa\'s crowded beaches. Known for its pristine, unspoiled coastline, Gokarna offers a perfect blend of spirituality and beach relaxation. The town is famous for the Mahabaleshwar Temple, one of India\'s seven most important Hindu pilgrimage sites housing a revered Atmalinga. The main beaches include Kudle, Om Beach (shaped like the Hindu Om symbol), Half Moon Beach, Paradise Beach, and Gokarna Beach. Om Beach is the most popular, offering water sports, beach shacks, and boat rides. The secluded Paradise and Half Moon beaches, accessible only by boat or a scenic 45-minute trek, offer ultimate tranquility. The 5-kilometer beach trek from Gokarna Beach to Paradise Beach is a rewarding experience with stunning coastal views. Unlike Goa\'s party scene, Gokarna maintains a laid-back, bohemian vibe with yoga retreats, meditation centers, and vegetarian cafes. The cliffs overlooking the Arabian Sea offer spectacular sunset views. Gokarna\'s unique character comes from its combination of sacred rituals and beach hippie culture, making it perfect for those seeking peace, spirituality, and natural beauty.', 'Karnataka, India', 14.55000000, 74.32000000, 1600.00, 'October to March', 'https://www.google.com/maps/place/Gokarna,+Karnataka/@14.55,74.31668,14z/data=!3m1!4b1!4m6!3m5!1s0x3bbe6b1032c00a6b:0x981bbcb1577c130d!8m2!3d14.55!4d74.31668!16zL20vMDhiXzJt?entry=ttu', '[\"Om Beach\",\"Mahabaleshwar Temple\",\"Kudle Beach\",\"Half Moon Beach\",\"Paradise Beach\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring', '[\"1\",\"2\",\"3-5\"]', '[\"Do the beach trek from Gokarna to Paradise\",\"Try Italian food at Namaste Cafe\",\"Respect temple dress code\"]', '[\"Gobi Manchurian\",\"Pizza\",\"Hummus\",\"Seafood\",\"Coconut Ice Cream\"]', '{\"Seafood Platter\":\"gokarna_seafood.jpg\",\"Coconut Ice Cream\":\"gokarna_coconut_icecream.jpg\",\"Hummus Platter\":\"gokarna_hummus.jpg\"}', '[\"Kannada\",\"Hindi\",\"English\",\"Konkani\"]', NULL, '[\"gokarna_om_beach.jpg\",\"gokarna_mahabaleshwar.jpg\",\"gokarna_kudle_beach.jpg\"]', '[\"gokarna_paradise_beach.jpg\",\"gokarna_sunset.jpg\"]', 'KA', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(25, 'Varkala', 'beach', 'Varkala, a coastal town in Kerala, is famous for its unique cliff beach and natural springs believed to have medicinal properties. The Varkala Beach, also known as Papanasham Beach (Destroyer of Sins), is the only place in southern Kerala where cliffs are adjacent to the Arabian Sea. This geological wonder features laterite cliffs offering stunning views of the turquoise waters below. The cliff top is lined with shops, restaurants, and resorts, creating a relaxed yet vibrant atmosphere. The 2,000-year-old Janardanaswamy Temple, dedicated to Vishnu, is an important pilgrimage site with a beautiful tank. The Sivagiri Mutt, founded by social reformer Sree Narayana Guru, is the site of an annual pilgrimage in December. The Varkala Tunnel, a British-era engineering marvel, and the secluded Black Sand Beach are lesser-known attractions. Unlike other crowded Kerala beaches, Varkala maintains a bohemian vibe attracting long-term travelers, yogis, and those seeking Ayurvedic treatments. The 15-meter-high red cliffs, natural springs, dolphin sightings, paragliding opportunities, and spectacular sunsets make Varkala a unique destination. The beach\'s name Papanasham reflects the belief that a dip here washes away sins, adding a spiritual dimension to its natural beauty.', 'Kerala, India', 8.73830000, 76.73050000, 1900.00, 'October to March', 'https://www.google.com/maps/place/Varkala,+Kerala/@8.738284,76.730502,14z/data=!3m1!4b1!4m6!3m5!1s0x3b05eb80faa6f91d:0xd1cef853062fc5c7!8m2!3d8.7378404!4d76.719811!16zL20vMDVibWdm?entry=ttu', '[\"Varkala Cliff Beach\",\"Janardanaswamy Temple\",\"Sivagiri Mutt\",\"Varkala Tunnel\",\"Black Sand Beach\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring', '[\"1\",\"2\",\"3-5\"]', '[\"Watch sunset from cliff top\",\"Try Ayurvedic massage\",\"Visit during Arattu festival\"]', '[\"Kerala Sadya\",\"Fish Molee\",\"Karimeen Pollichathu\",\"Puttu with Kadala\"]', '{\"Kerala Sadya\":\"varkala_sadya.jpg\",\"Fish Molee\":\"varkala_fish_molee.jpg\",\"Karimeen\":\"varkala_karimeen.jpg\"}', '[\"Malayalam\",\"English\",\"Tamil\",\"Hindi\"]', NULL, '[\"varkala_cliff.jpg\",\"varkala_beach.jpg\",\"varkala_temple.jpg\"]', '[\"varkala_sunset.jpg\",\"varkala_black_sand.jpg\"]', 'KL', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(26, 'Puri', 'beach', 'Puri, located in Odisha, is one of the four holiest pilgrimage sites (Char Dham) in Hinduism, famous for the Jagannath Temple and its golden beach. The Puri Beach, with its golden sands and gentle waves, stretches along the Bay of Bengal and is famous for the annual Rath Yatra (Chariot Festival) where giant chariots carrying Lord Jagannath are pulled by millions of devotees. The Jagannath Temple, a 12th-century architectural marvel, is known for its unique kitchen that is the world\'s largest, feeding thousands daily with mahaprasad cooked in earthen pots. The beach is popular for sunrise views, camel rides, horse riding, and water sports like jet skiing and parasailing. The nearby Chilika Lake, Asia\'s largest brackish water lagoon, is a birdwatcher\'s paradise and home to Irrawaddy dolphins. The Konark Sun Temple, a UNESCO World Heritage Site shaped as a giant chariot with 24 wheels, is just a 35-kilometer drive away. Puri\'s beach is also known for its sand artists, who create intricate sculptures on the shore. The local cuisine features delicious seafood and the famous abadha (temple food). Puri uniquely combines deep spirituality, rich culture, and beach relaxation, offering a different coastal experience from Goa or Kerala.', 'Odisha, India', 19.80400000, 85.82600000, 1400.00, 'October to February', 'https://www.google.com/maps/place/Puri,+Odisha/@19.80416,85.82622,13z/data=!3m1!4b1!4m6!3m5!1s0x3a19f6e372ef101b:0x645ef3c6775eb159!8m2!3d19.80416!4d85.82622!16zL20vMDVhdnN4?entry=ttu', '[\"Jagannath Temple\",\"Puri Beach\",\"Chilika Lake\",\"Konark Sun Temple\",\"Rath Yatra\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring', '[\"1\",\"2\",\"3-5\",\"6-10\"]', '[\"Attend Rath Yatra in June-July\",\"Try temple mahaprasad\",\"Visit Konark early morning\"]', '[\"Mahaprasad\",\"Pakhala\",\"Dahibara Aludum\",\"Chenna Poda\"]', '{\"Mahaprasad\":\"puri_mahaprasad.jpg\",\"Chenna Poda\":\"puri_chenna_poda.jpg\",\"Dahibara\":\"puri_dahibara.jpg\"}', '[\"Odia\",\"Hindi\",\"English\"]', NULL, '[\"puri_jagannath.jpg\",\"puri_beach.jpg\",\"puri_chilika.jpg\"]', '[\"puri_konark.jpg\",\"puri_sunrise.jpg\"]', 'OR', 'India', 'East', 'admin', NULL, 'approved', NULL, 5.00),
(27, 'Kovalam', 'beach', 'Kovalam, a coastal town in Kerala, was India\'s first beach destination to gain international fame, known for its crescent-shaped coastline and shallow waters. The three adjacent beaches—Lighthouse, Hawah, and Samudra—form a stunning natural bay. Lighthouse Beach, named after the 35-meter-tall red-and-white lighthouse offering panoramic views, is the most popular with its gentle waves perfect for swimming. Hawah Beach (also known as Eve\'s Beach) is famous for its golden sand and evening breeze. The rocky promontories separating the beaches provide beautiful walking paths and photography spots. Kovalam rose to prominence in the 1970s as a hippie destination and remains popular for its Ayurvedic treatments, yoga centers, and seafood restaurants. The Vizhinjam Fishing Harbor nearby offers a glimpse into local fishing life and fresh seafood catches. The 8th-century Vizhinjam Rock-cut Temple and the Halcyon Castle add historical interest. The beach offers various water sports including surfing, catamaran rides, and speed boating. Kovalam maintains a perfect balance between development and natural beauty, with resorts and homestays tucked into palm-covered hills. Unlike Goa\'s party scene, Kovalam offers a more sophisticated, relaxed beach holiday with excellent Ayurveda and wellness options.', 'Kerala, India', 8.40200000, 76.97800000, 2100.00, 'September to March', 'https://www.google.com/maps/place/Kovalam,+Kerala/@8.40202,76.97817,14z/data=!3m1!4b1!4m6!3m5!1s0x3b05ed758b1d9bf5:0xc3a4024a5a133694!8m2!3d8.40202!4d76.97817!16zL20vMDQxN2Q3?entry=ttu', '[\"Lighthouse Beach\",\"Hawah Beach\",\"Vizhinjam Lighthouse\",\"Vizhinjam Fishing Harbor\",\"Halcyon Castle\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Climb lighthouse for sunset views\",\"Try Ayurvedic massage on beach\",\"Eat fresh seafood at harbor\"]', '[\"Kerala Fish Curry\",\"Karimeen Fry\",\"Prawn Curry\",\"Kappa and Meen Curry\"]', '{\"Kerala Fish Curry\":\"kovalam_fish_curry.jpg\",\"Karimeen Fry\":\"kovalam_karimeen.jpg\",\"Prawn Curry\":\"kovalam_prawn_curry.jpg\"}', '[\"Malayalam\",\"English\",\"Tamil\",\"Hindi\"]', NULL, '[\"kovalam_lighthouse_beach.jpg\",\"kovalam_lighthouse.jpg\",\"kovalam_hawah_beach.jpg\"]', '[\"kovalam_harbor.jpg\",\"kovalam_sunset.jpg\"]', 'KL', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(28, 'Manali', 'mountains', 'Manali, nestled in the Beas River Valley of Himachal Pradesh, is a breathtaking hill station surrounded by snow-capped peaks and deodar forests. This picturesque town is the gateway to adventure sports and spiritual retreats in the Indian Himalayas. Solang Valley, just 13 kilometers away, offers paragliding, zorbing, and skiing during winter. The Rohtang Pass, at 3,978 meters, provides stunning views of glaciers and peaks, though access is restricted. The ancient Hadimba Devi Temple, built in 1553 with distinctive tiered wooden architecture, is set amidst a cedar forest. The Tibetan monasteries, including the Gadhan Thekchhokling Gompa, reflect Manali\'s strong Buddhist influence. The Vashisht Hot Springs, believed to have healing properties, offer relaxing baths. Old Manali\'s bohemian vibe features cozy cafes, live music, and art galleries. The nearby villages of Naggar (with its castle and art gallery) and Jana (with a waterfall) make excellent day trips. Manali is also the starting point for treks to Hampta Pass, Bhrigu Lake, and the remote Pin Parvati Valley. The Beas River offers white-water rafting opportunities. Manali\'s apple orchards, pine forests, and the friendly local cuisine including siddu and dham create a magical mountain experience for all seasons.', 'Himachal Pradesh, India', 32.23960000, 77.18870000, 2400.00, 'December to February (snow), March to June (pleasant)', 'https://www.google.com/maps/place/Manali,+Himachal+Pradesh/@32.23967,77.18867,14z/data=!3m1!4b1!4m6!3m5!1s0x39046f34624505b9:0x67ae35e6578d1dc!8m2!3d32.23967!4d77.18867!16zL20vMDJqbWg?entry=ttu', '[\"Solang Valley\",\"Hadimba Temple\",\"Rohtang Pass\",\"Vashisht Hot Springs\",\"Old Manali\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,summer,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Check Rohtang Pass permit online\",\"Pack warm clothes even in summer\",\"Try local siddu and cider\"]', '[\"Siddu\",\"Dham\",\"Tudkiya Bhath\",\"Momos\",\"Apple Cider\"]', '{\"Siddu\":\"manali_siddu.jpg\",\"Dham\":\"manali_dham.jpg\",\"Momos\":\"manali_momos.jpg\"}', '[\"Hindi\",\"Pahari\",\"English\"]', NULL, '[\"manali_solang.jpg\",\"manali_hadimba.jpg\",\"manali_rohtang.jpg\"]', '[\"manali_old_manali.jpg\",\"manali_beas_river.jpg\"]', 'HP', 'India', 'North', 'admin', NULL, 'approved', NULL, 5.00),
(29, 'Darjeeling', 'mountains', 'Darjeeling, the \"Queen of the Hills\" in West Bengal, is famous for its tea plantations, the Darjeeling Himalayan Railway (Toy Train), and stunning views of Kanchenjunga, the world\'s third-highest peak. This former British hill station at 2,042 meters offers a unique blend of Himalayan beauty, colonial architecture, and Tibetan-influenced culture. The Darjeeling Himalayan Railway, a UNESCO World Heritage Site, offers a charming 8-kilometer ride from Darjeeling to Ghum, passing through mountains and forests. The Tiger Hill sunrise, where the first rays of sun illuminate the Kanchenjunga massif, is a magical experience. The Padmaja Naidu Himalayan Zoological Park houses rare species like snow leopards, red pandas, and Tibetan wolves. The Japanese Peace Pagoda, Tea Estate tours, and the Himalayan Mountaineering Institute (founded by Tenzing Norgay) are key attractions. The Batasia Loop, where the toy train circles a beautifully landscaped garden with a war memorial, offers panoramic mountain views. The local cuisine includes authentic Tibetan momos, thukpa, and the unique Darjeeling tea. The region\'s vibrant Buddhist monasteries, including the Ghoom Monastery and Yiga Choeling Monastery, add spiritual depth. Darjeeling\'s misty mornings, rhododendron forests, and the famous Darjeeling tea experience make it an unforgettable Himalayan destination.', 'West Bengal, India', 27.04100000, 88.26630000, 2300.00, 'October to December, March to May', 'https://www.google.com/maps/place/Darjeeling,+West+Bengal/@27.04106,88.26633,14z/data=!3m1!4b1!4m6!3m5!1s0x39e42b9ef0bf0821:0xc4f066a8491b63c1!8m2!3d27.04104!4d88.26633!16zL20vMDFsYzQ?entry=ttu', '[\"Tiger Hill\",\"Darjeeling Toy Train\",\"Batasia Loop\",\"Tea Estates\",\"Padmaja Naidu Zoo\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Book toy train tickets early\",\"Visit Tiger Hill before 4 AM\",\"Buy tea from government-certified shops\"]', '[\"Momos\",\"Thukpa\",\"Aloo Dum\",\"Sel Roti\",\"Darjeeling Tea\"]', '{\"Momos\":\"darjeeling_momos.jpg\",\"Thukpa\":\"darjeeling_thukpa.jpg\",\"Darjeeling Tea\":\"darjeeling_tea.jpg\"}', '[\"Nepali\",\"Bengali\",\"Hindi\",\"English\"]', NULL, '[\"darjeeling_tiger_hill.jpg\",\"darjeeling_toy_train.jpg\",\"darjeeling_tea_garden.jpg\"]', '[\"darjeeling_batasia.jpg\",\"darjeeling_kanchenjunga.jpg\"]', 'WB', 'India', 'East', 'admin', NULL, 'approved', NULL, 5.00);
INSERT INTO `destinations` (`id`, `name`, `type`, `description`, `location`, `latitude`, `longitude`, `budget`, `best_season`, `map_link`, `attractions`, `created_at`, `updated_at`, `season`, `people`, `tips`, `cuisines`, `cuisine_images`, `language`, `profile_pic`, `images`, `image_urls`, `state_code`, `country`, `region`, `submitted_by_type`, `submitted_by_id`, `submission_status`, `contributor_id`, `commission_rate`) VALUES
(30, 'Munnar', 'mountains', 'Munnar, located in Kerala\'s Western Ghats, is a breathtaking hill station known for its endless tea plantations, rolling green hills, and pristine valleys. Situated at 1,600 meters, Munnar was the summer resort of the British colonial government and still retains its old-world charm. The region\'s tea estates, including the famous Kolukkumalai Tea Estate (the world\'s highest at 2,500 meters), offer tours and tastings of some of the finest tea. The Eravikulam National Park, a UNESCO World Heritage Site, is home to the endangered Nilgiri Tahr (mountain goat) and the spectacular Neelakurinji flowers that bloom once every 12 years. The Mattupetty Dam and Lake offer boating with stunning mountain backdrops. The Anamudi Peak (2,695 meters), South India\'s highest peak, is a challenging trek through shola forests and grasslands. The Tea Museum showcases the history of tea-making. The Echo Point, Top Station (offering views of Theni valley), and Nyayamakad Waterfall are scenic highlights. Munnar\'s spice plantations produce cardamom, pepper, and cinnamon. The cool climate, misty mountains, winding roads through tea gardens, and fresh mountain air make Munnar a perfect retreat for nature lovers, honeymooners, and those seeking peace. The local cuisine features Kerala\'s flavors with a mountain twist.', 'Kerala, India', 10.09000000, 77.06000000, 2200.00, 'September to November, January to May', 'https://www.google.com/maps/place/Munnar,+Kerala/@10.08957,77.05978,13z/data=!3m1!4b1!4m6!3m5!1s0x3b0798a496aacbcf:0xbd5cbbc4a5113fd!8m2!3d10.08957!4d77.05978!16zL20vMDFwZzU?entry=ttu', '[\"Tea Plantations\",\"Eravikulam National Park\",\"Mattupetty Dam\",\"Kolukkumalai Tea Estate\",\"Tea Museum\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,summer,spring,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Take sunrise trek to Kolukkumalai\",\"Visit Eravikulam early morning\",\"Try fresh tea at plantation\"]', '[\"Kerala Parotta\",\"Kerala Sadya\",\"Puttu with Kadala\",\"Munnar Tea\",\"Spice Tea\"]', '{\"Kerala Sadya\":\"munnar_sadya.jpg\",\"Puttu\":\"munnar_puttu.jpg\",\"Munnar Tea\":\"munnar_tea.jpg\"}', '[\"Malayalam\",\"English\",\"Tamil\",\"Hindi\"]', NULL, '[\"munnar_tea_garden.jpg\",\"munnar_eravikulam.jpg\",\"munnar_mattupetty.jpg\"]', '[\"munnar_kolukkumalai.jpg\",\"munnar_anaimudi.jpg\"]', 'KL', 'India', 'South', 'admin', NULL, 'approved', NULL, 5.00),
(31, 'Leh', 'mountains', 'Leh, the capital of Ladakh, is a high-altitude desert paradise nestled in the Indian Himalayas at 3,524 meters. Known as the Land of High Passes, Leh offers dramatic landscapes of barren mountains, deep gorges, and turquoise rivers. The city is dominated by the 17th-century Leh Palace, a nine-story structure overlooking the town, modeled after Tibet\'s Potala Palace. The Shanti Stupa, perched on a hilltop, offers panoramic views of the Indus Valley and sunsets behind the mountains. The magnetic hill, where vehicles appear to defy gravity, and the confluence of the Indus and Zanskar rivers at Sangam are fascinating geological attractions. The nearby monasteries of Thiksey (resembling a mini-Potala), Hemis (Ladakh\'s largest), and Diskit (with a giant Maitreya Buddha statue) showcase Tibetan Buddhist culture. The Nubra Valley, accessible via the world\'s highest motorable pass Khardung La (5,359 meters), features sand dunes and Bactrian camel rides. Pangong Tso, the stunning blue saltwater lake across the border, is a 140-kilometer drive through rugged terrain. Adventure seekers enjoy river rafting on the Indus, trekking to Stok Kangri, and motorbiking on the world\'s highest roads. Leh\'s unique culture, arid beauty, and Tibetan cuisine make it a once-in-a-lifetime destination.', 'Ladakh, India', 34.15260000, 77.57710000, 3000.00, 'June to September', 'https://www.google.com/maps/place/Leh,+Ladakh/@34.152586,77.577063,13z/data=!3m1!4b1!4m6!3m5!1s0x38fd6fa1908a0ca9:0x23dcdb57bfb40b40!8m2!3d34.152586!4d77.577063!16zL20vMDF6OWhr?entry=ttu', '[\"Leh Palace\",\"Shanti Stupa\",\"Pangong Tso\",\"Nubra Valley\",\"Thiksey Monastery\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'summer', '[\"1\",\"2\",\"3-5\"]', '[\"Acclimatize for 24-48 hours\",\"Carry sunscreen and lip balm\",\"Drink plenty of water to avoid AMS\"]', '[\"Thukpa\",\"Momos\",\"Skyu\",\"Chhurpi Soup\",\"Butter Tea\"]', '{\"Thukpa\":\"leh_thukpa.jpg\",\"Momos\":\"leh_momos.jpg\",\"Butter Tea\":\"leh_butter_tea.jpg\"}', '[\"Ladakhi\",\"Hindi\",\"English\",\"Urdu\"]', NULL, '[\"leh_palace.jpg\",\"leh_pangong.jpg\",\"leh_shanti_stupa.jpg\"]', '[\"leh_nubra_valley.jpg\",\"leh_thiksey.jpg\"]', 'LA', 'India', 'North', 'admin', NULL, 'approved', NULL, 5.00),
(32, 'Shillong', 'mountains', 'Shillong, the capital of Meghalaya, is known as the \"Scotland of the East\" for its rolling green hills, pine forests, and colonial-era architecture. Located at 1,525 meters, this charming hill station is the cultural hub of Northeast India, renowned for its music scene, pleasant weather, and vibrant Khasi culture. The beautiful Ward\'s Lake, surrounded by gardens and a wooden bridge, offers boating and peaceful walks. The Don Bosco Museum, one of Northeast India\'s largest museums, showcases the region\'s tribal heritage. The Shillong Peak at 1,966 meters provides panoramic views of the entire city and the Bangladesh plains on clear days. The Elephant Falls, a three-tiered waterfall amidst ferns and orchids, and the spectacular 350-meter-high Nohkalikai Falls, India\'s tallest plunge waterfall, are natural highlights. The living root bridges of neighboring villages and the crystal-clear Umiam Lake (Barapani), an artificial reservoir 17 kilometers from the city, offer water sports and picnics. Shillong\'s churches, including the Cathedral of Mary Help of Christians, reflect its Christian majority. The city\'s jazz and blues festivals, rock music scene, cafes serving delicious Khasi cuisine (including jadoh and tungrymbai), and the friendly locals make Shillong a unique hill station experience far removed from the typical Himalayan destinations.', 'Meghalaya, India', 25.57880000, 91.89330000, 1900.00, 'October to May', 'https://www.google.com/maps/place/Shillong,+Meghalaya/@25.578774,91.8933,13z/data=!3m1!4b1!4m6!3m5!1s0x37575ca664f5c95b:0x46264772f35923c1!8m2!3d25.5787735!4d91.8932544!16zL20vMDQyeGhk?entry=ttu', '[\"Ward\'s Lake\",\"Elephant Falls\",\"Don Bosco Museum\",\"Shillong Peak\",\"Umiam Lake\"]', '2026-05-15 06:11:36', '2026-05-15 06:11:36', 'winter,spring,summer,autumn', '[\"1\",\"2\",\"3-5\"]', '[\"Visit during Autumn for clear views\",\"Try authentic Khasi jadoh\",\"Explore music scene at local pubs\"]', '[\"Jadoh\",\"Tungrymbai\",\"Dohneiiong\",\"Pumaloi\",\"Momos\"]', '{\"Jadoh\":\"shillong_jadoh.jpg\",\"Tungrymbai\":\"shillong_tungrymbai.jpg\",\"Dohneiiong\":\"shillong_dohneiiong.jpg\"}', '[\"Khasi\",\"English\",\"Hindi\"]', NULL, '[\"shillong_wards_lake.jpg\",\"shillong_elephant_falls.jpg\",\"shillong_peak.jpg\"]', '[\"shillong_umiam_lake.jpg\",\"shillong_church.jpg\"]', 'ML', 'India', 'Northeast', 'admin', NULL, 'approved', NULL, 5.00);

-- --------------------------------------------------------

--
-- Table structure for table `destination_cities`
--

CREATE TABLE `destination_cities` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `city_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `map_link` varchar(500) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT 0.00,
  `image_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`image_urls`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_log`
--

CREATE TABLE `email_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT 0,
  `email_address` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `sent_status` tinyint(1) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `email_log`
--

INSERT INTO `email_log` (`id`, `user_id`, `email_address`, `subject`, `message`, `sent_status`, `error_message`, `sent_at`) VALUES
(1, 8, 'ranajitbarik2005@gmail.com', 'Account Notification', 'ggg', 0, 'SMTP Error', '2026-04-02 02:53:10'),
(2, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 0, 'SMTP Error', '2026-04-02 02:55:35'),
(3, 8, 'ranajitbarik2005@gmail.com', 'Removed from Website', 'I have removed', 0, 'SMTP Error', '2026-04-02 10:10:53'),
(4, 1, 'ranajitbarik071@gmail.com', 'Account Notification', 'stuff', 0, 'SMTP Error', '2026-04-14 09:38:17'),
(5, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 0, 'SMTP Error', '2026-04-14 09:38:32'),
(6, 1, 'ranajitbarik071@gmail.com', 'Account Notification', 'fedf', 0, 'SMTP Error', '2026-04-17 03:40:27'),
(7, 1, 'ranajitbarik071@gmail.com', 'Account Notification', 'fedf', 0, 'SMTP Error', '2026-04-17 03:41:18'),
(8, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 0, 'SMTP Error', '2026-04-17 03:41:51'),
(9, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 0, 'SMTP Error', '2026-04-17 04:07:01'),
(10, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 1, '', '2026-04-17 04:12:12'),
(11, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 1, '', '2026-04-17 04:12:32'),
(12, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 1, '', '2026-04-17 04:12:47'),
(13, 0, 'ranajitbarik85@gmail.com', 'Test Email from TripMate', 'This is a test email to verify SMTP configuration is working.', 1, '', '2026-04-17 04:12:57'),
(14, 1, 'ranajitbarik071@gmail.com', 'Account Notification', 'jvhgf', 1, '', '2026-04-27 15:13:13');

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

-- --------------------------------------------------------

--
-- Table structure for table `favorites`
--

CREATE TABLE `favorites` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `favorites`
--

INSERT INTO `favorites` (`id`, `user_id`, `destination_id`, `created_at`) VALUES
(1, 5, 7, '2026-02-22 10:02:10');

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `departure_city` varchar(150) NOT NULL,
  `airline` varchar(150) NOT NULL,
  `flight_type` varchar(50) DEFAULT 'medium',
  `price_per_person` decimal(10,2) DEFAULT 0.00,
  `duration_hours` decimal(5,2) DEFAULT NULL,
  `stops` int(11) DEFAULT 0,
  `departure_time` varchar(50) DEFAULT NULL,
  `arrival_time` varchar(50) DEFAULT NULL,
  `flight_class` varchar(50) DEFAULT 'Economy',
  `baggage_allowance` varchar(100) DEFAULT NULL,
  `refundable` tinyint(1) DEFAULT 0,
  `meal_included` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `hotel_name` varchar(200) NOT NULL,
  `hotel_type` varchar(50) DEFAULT 'medium',
  `price_per_night` decimal(10,2) DEFAULT 0.00,
  `hotel_rating` decimal(3,1) DEFAULT 0.0,
  `description` text DEFAULT NULL,
  `amenities` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `contact_number` varchar(50) DEFAULT NULL,
  `check_in_time` varchar(20) DEFAULT '12:00',
  `check_out_time` varchar(20) DEFAULT '11:00',
  `free_cancellation` tinyint(1) DEFAULT 1,
  `breakfast_included` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `itineraries`
--

CREATE TABLE `itineraries` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `travel_style` enum('adventure','relaxation','cultural','luxury','budget') DEFAULT 'adventure',
  `preferences` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'User preferences: activities, cuisine, pace' CHECK (json_valid(`preferences`)),
  `generated_by_ai` tinyint(1) DEFAULT 1,
  `status` enum('draft','published','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `itineraries`
--

INSERT INTO `itineraries` (`id`, `user_id`, `destination_id`, `title`, `start_date`, `end_date`, `budget`, `travel_style`, `preferences`, `generated_by_ai`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:29:56', '2026-02-18 12:29:56'),
(2, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:30:00', '2026-02-18 12:30:00'),
(3, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:30:01', '2026-02-18 12:30:01'),
(4, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:30:02', '2026-02-18 12:30:02'),
(5, 5, 2, 'Trip to Paris', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:30:12', '2026-02-18 12:30:12'),
(6, 5, 2, 'Trip to Paris', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:30:14', '2026-02-18 12:30:14'),
(7, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:05', '2026-02-18 12:38:05'),
(8, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:07', '2026-02-18 12:38:07'),
(9, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:08', '2026-02-18 12:38:08'),
(10, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:08', '2026-02-18 12:38:08'),
(11, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:09', '2026-02-18 12:38:09'),
(12, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:09', '2026-02-18 12:38:09'),
(13, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 12:38:09', '2026-02-18 12:38:09'),
(14, 5, 2, 'Trip to Paris', '2026-02-25', '2026-03-04', 5000.00, 'budget', '[]', 1, 'draft', '2026-02-21 13:14:45', '2026-02-21 13:14:45'),
(15, 5, 2, 'Trip to Paris', '2026-02-25', '2026-03-04', 5000.00, 'budget', '[]', 1, 'draft', '2026-02-21 13:14:47', '2026-02-21 13:14:47'),
(16, 5, 2, 'Trip to Paris', '2026-02-25', '2026-03-04', 5000.00, 'budget', '[]', 1, 'draft', '2026-02-21 13:14:48', '2026-02-21 13:14:48');

-- --------------------------------------------------------

--
-- Table structure for table `itinerary_days`
--

CREATE TABLE `itinerary_days` (
  `id` int(11) NOT NULL,
  `itinerary_id` int(11) NOT NULL,
  `day_number` int(11) NOT NULL,
  `date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `activities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Array of activities for the day' CHECK (json_valid(`activities`)),
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `page_time_tracking`
--

CREATE TABLE `page_time_tracking` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `page_name` varchar(255) NOT NULL DEFAULT 'unknown',
  `page_url` text DEFAULT NULL,
  `time_spent` int(11) NOT NULL DEFAULT 0,
  `click_count` int(11) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(128) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `page_time_tracking`
--

INSERT INTO `page_time_tracking` (`id`, `user_id`, `page_name`, `page_url`, `time_spent`, `click_count`, `ip_address`, `user_agent`, `session_id`, `visit_date`, `created_at`) VALUES
(1, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/page_time_analytics.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', 'maoicoi4ijntmma5ou375s80d4', '2026-04-17', '2026-04-17 04:22:44'),
(2, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_09_04_26/tripmate/admin/page_time_analytics.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:23:02'),
(3, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/page_time_analytics.php', 36, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:23:20'),
(4, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/page_time_analytics.php', 77, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:24:37'),
(5, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_09_04_26/tripmate/admin/page_time_analytics.php', 120, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:25:02'),
(6, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/page_time_analytics.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:25:07'),
(7, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/page_time_analytics.php', 8, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:25:16'),
(8, 0, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_09_04_26/tripmate/admin/page_time_analytics.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:25:32'),
(9, 0, 'admin_dasbord', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:25:46'),
(10, 0, 'admin_dasbord', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/admin_dasbord.php', 16, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:26:02'),
(11, 0, 'admin_dasbord', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/admin/admin_dasbord.php', 8, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:26:10'),
(12, 0, 'index', 'http://localhost/mejor_project/updated_upto_11_04_26/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:26:15'),
(13, 2, 'page_time_analytics', 'http://localhost/mejor_project/updated_upto_09_04_26/tripmate/admin/page_time_analytics.php', 1483, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', NULL, '2026-04-17', '2026-04-17 04:50:15'),
(14, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:01:57'),
(15, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:04:22'),
(16, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 242, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:08:24'),
(17, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 254, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:08:36'),
(18, 0, 'index', 'http://10.48.57.202/mejor_project/%20tripmate_final_project%20/tripmate/main/', 6, 3, '10.48.57.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 18:14:52'),
(19, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 3, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:20:16'),
(20, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:20:18'),
(21, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 18:20:34'),
(22, 0, 'index', 'http://10.48.57.202/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 1, 1, '10.48.57.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 18:37:47'),
(23, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 9, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:01:37'),
(24, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:02:45'),
(25, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:03:10'),
(26, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:03:15'),
(27, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:03:45'),
(28, 0, 'index', 'http://10.48.57.202/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '10.48.57.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:03:46'),
(29, 0, 'index', 'http://10.48.57.202/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 35, 1, '10.48.57.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:04:18'),
(30, 0, 'index', 'http://10.48.57.202/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 0, '10.48.57.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:04:21'),
(31, 0, 'index', 'http://10.48.57.202/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 1, 1, '10.48.57.199', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:04:22'),
(32, 8, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 31, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:15:57'),
(33, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:16:02'),
(34, 8, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 34, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:16:31'),
(35, 8, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 55, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:17:27'),
(36, 8, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:18:26'),
(37, 8, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 34, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-24', '2026-04-24 19:19:00'),
(38, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 513, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:24:36'),
(39, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:25:06'),
(40, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 314, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:30:20'),
(41, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:30:50'),
(42, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 391, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:37:21'),
(43, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-24', '2026-04-24 19:37:51'),
(44, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 6, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 14:25:27'),
(45, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 25, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 14:59:11'),
(46, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 30, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 14:59:17'),
(47, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 14:59:20'),
(48, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 6, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:00:41'),
(49, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:00:47'),
(50, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:00:53'),
(51, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 9, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:01:00'),
(52, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:01:02'),
(53, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:02:01'),
(54, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:03:04'),
(55, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:03:54'),
(56, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:07:14'),
(57, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 90, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:08:44'),
(58, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:09:14'),
(59, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:09:44'),
(60, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 22, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:10:06'),
(61, 0, 'add_destination_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/add_destination_on_admin.php', 12, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:10:19'),
(62, 0, 'manage_hotels', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_hotels.php?destination_id=23', 7, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:10:26'),
(63, 0, 'user_present_chack_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_present_chack_on_admin.php', 19, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:10:46'),
(64, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php?user_id=2&email=ranajitbarik2005%40gmail.com&subject=Removed%20from%20Website&sender_name=TRIPMATE%20ADMIN', 15, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:11:01'),
(65, 0, 'user_present_chack_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_present_chack_on_admin.php', 9, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:11:10'),
(66, 0, 'user_join_analysis_on_ADMIN', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_join_analysis_on_ADMIN.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:11:40'),
(67, 0, 'user_join_analysis_on_ADMIN', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_join_analysis_on_ADMIN.php', 47, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:12:27'),
(68, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 30, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:12:57'),
(69, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 9, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:13:06'),
(70, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 30, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:13:44'),
(71, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 241, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:17:45'),
(72, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:18:15'),
(73, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 7, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:18:22'),
(74, 0, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_ip_tracking_on_admin.php', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:18:24'),
(75, 0, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_ip_tracking_on_admin.php?show_all=1', 30, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:18:54'),
(76, 0, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_ip_tracking_on_admin.php?show_all=1', 60, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:19:54'),
(77, 0, 'page_time_analytics', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/page_time_analytics.php', 30, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:21:03'),
(78, 0, 'page_time_analytics', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/page_time_analytics.php', 14, 12, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:21:17'),
(79, 0, 'page_time_analytics', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/page_time_analytics.php', 16, 6, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:21:34'),
(80, 0, 'page_time_analytics', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/page_time_analytics.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:22:04'),
(81, 0, 'page_time_analytics', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/page_time_analytics.php', 38, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:22:42'),
(82, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 16, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:22:58'),
(83, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 10, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:09'),
(84, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:14'),
(85, 0, 'weather_dashboard', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/weather_dashboard.php', 19, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:33'),
(86, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 13, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:46'),
(87, 0, 'add_destination_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/add_destination_on_admin.php', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:50'),
(88, 0, 'user_present_chack_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_present_chack_on_admin.php', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:52'),
(89, 0, 'user_join_analysis_on_ADMIN', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_join_analysis_on_ADMIN.php', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:55'),
(90, 0, 'send_user_email', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/send_user_email.php', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:56'),
(91, 0, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_ip_tracking_on_admin.php', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:23:59'),
(92, 0, 'page_time_analytics', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/page_time_analytics.php', 4, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:24:04'),
(93, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:24:34'),
(94, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:25:00'),
(95, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:25:04'),
(96, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 276, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:29:39'),
(97, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 9, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:29:49'),
(98, 0, 'view_contributor_submission', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/view_contributor_submission.php?id=24', 12, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:30:02'),
(99, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 7, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:30:09'),
(100, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 30, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:30:40'),
(101, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 5, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:30:46'),
(102, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:31:10'),
(103, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:31:13'),
(104, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 9, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:31:19'),
(105, 2, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:31:40'),
(106, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 7, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:32:31'),
(107, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:32:57'),
(108, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 8, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-27', '2026-04-27 15:33:39'),
(109, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 8, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 12:53:21'),
(110, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 12:53:25'),
(111, 8, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-30', '2026-04-30 12:59:35'),
(112, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-04-30', '2026-04-30 12:59:45'),
(113, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 13:33:08'),
(114, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 13:33:38'),
(115, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 766, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 13:46:25'),
(116, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 13:46:27'),
(117, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 3, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-04-30', '2026-04-30 13:46:45'),
(118, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:34:03'),
(119, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 53, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:34:56'),
(120, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:35:50'),
(121, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 90, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:37:20'),
(122, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 13, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:37:33'),
(123, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:37:35'),
(124, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:38:06'),
(125, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:39:06'),
(126, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 11, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:39:17'),
(127, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:41:58'),
(128, 5, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 31, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:42:37'),
(129, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 7, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:45:51'),
(130, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 10, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:47:49'),
(131, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:48:19'),
(132, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 33, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:48:52'),
(133, 0, 'manage_contributor_submissions', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/manage_contributor_submissions.php', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:48:56'),
(134, 0, 'weather_dashboard', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/weather_dashboard.php', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:48:59'),
(135, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 4, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:49:04'),
(136, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:49:34'),
(137, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 15, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:49:49'),
(138, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:50:53'),
(139, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 5, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 07:51:30'),
(140, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:04:49'),
(141, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:05:52'),
(142, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:06:23'),
(143, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:07:23'),
(144, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:08:23'),
(145, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 90, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:09:53'),
(146, 0, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/bookings.php', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:09:54'),
(147, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 6, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:10:00'),
(148, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', NULL, '2026-05-10', '2026-05-10 08:10:02'),
(149, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 35, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 15:57:26'),
(150, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 44, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 15:57:35'),
(151, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 5, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 15:58:04'),
(152, 1, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 2, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 15:58:06'),
(153, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:00:10'),
(154, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 120, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:02:10'),
(155, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:03:10'),
(156, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:03:40'),
(157, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 111, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:05:31'),
(158, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:06:01'),
(159, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 374, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:12:15'),
(160, 1, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:12:45'),
(161, 1, 'user_join_analysis_on_ADMIN', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_join_analysis_on_ADMIN.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:15:46'),
(162, 1, 'user_join_analysis_on_ADMIN', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/admin/user_join_analysis_on_ADMIN.php', 30, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:16:16'),
(163, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:16:20'),
(164, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:16:29'),
(165, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/index.html', 2, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-10', '2026-05-10 16:17:10'),
(166, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-20', '2026-05-20 09:30:24'),
(167, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 8, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 17:59:05'),
(168, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 17:59:36'),
(169, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:00:36');
INSERT INTO `page_time_tracking` (`id`, `user_id`, `page_name`, `page_url`, `time_spent`, `click_count`, `ip_address`, `user_agent`, `session_id`, `visit_date`, `created_at`) VALUES
(170, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 6, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:16:45'),
(171, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:16:52'),
(172, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 6, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:19:21'),
(173, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=5', 5, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:39:24'),
(174, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:40:12'),
(175, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:40:17'),
(176, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:41:11'),
(177, 0, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=5', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 18:42:34'),
(178, 0, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=5', 150, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 18:46:43'),
(179, 0, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=5', 3, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 18:46:45'),
(180, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:49:15'),
(181, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:51:20'),
(182, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 210, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:54:50'),
(183, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 18:55:20'),
(184, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 30, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 19:06:05'),
(185, 0, 'add_destination_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/add_destination_on_admin.php', 19, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 19:06:24'),
(186, 0, 'edit_destination', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/edit_destination.php?id=23', 26, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 19:06:50'),
(187, 0, 'add_destination_on_admin', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/add_destination_on_admin.php', 17, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 19:07:07'),
(188, 0, 'manage_hotels', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/manage_hotels.php?destination_id=23', 45, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-22', '2026-05-22 19:07:53'),
(189, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1052, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:12:52'),
(190, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1054, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:12:54'),
(191, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:13:36'),
(192, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 300, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:18:36'),
(193, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:19:06'),
(194, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:19:36'),
(195, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 271, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:24:07'),
(196, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:24:37'),
(197, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:25:30'),
(198, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 15, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:25:55'),
(199, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:42:23'),
(200, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 19, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:43:02'),
(201, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 10, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:43:13'),
(202, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 19, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:44:48'),
(203, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:44:54'),
(204, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:45:27'),
(205, 3, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 29, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:45:57'),
(206, 3, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=2', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:46:09'),
(207, 3, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=2', 36, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:46:42'),
(208, 5, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 3, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:47:07'),
(209, 5, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 14, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:47:18'),
(210, 5, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 21, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:48:32'),
(211, 5, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/bookings.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:49:03'),
(212, 5, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/bookings.php', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:50:03'),
(213, 5, 'bookings', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/bookings.php', 14, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:50:17'),
(214, 5, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 2, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:50:19'),
(215, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:50:22'),
(216, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 30, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:51:06'),
(217, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 102, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:52:49'),
(218, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:52:50'),
(219, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:54:39'),
(220, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 3, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:56:46'),
(221, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:56:49'),
(222, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 19:59:06'),
(223, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 90, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:00:36'),
(224, 0, 'admin_dasbord', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/admin/admin_dasbord.php', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:00:40'),
(225, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:01:10'),
(226, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:04:28'),
(227, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:09:45'),
(228, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:10:14'),
(229, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 26, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:12:59'),
(230, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 34, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:13:07'),
(231, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=1', 10, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-22', '2026-05-22 20:13:17'),
(232, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/#', 30, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 18:55:14'),
(233, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/#', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 18:56:14'),
(234, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/#', 882, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:10:55'),
(235, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/#', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:10:59'),
(236, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:11:01'),
(237, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:11:45'),
(238, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 120, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:13:45'),
(239, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:14:45'),
(240, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:15:15'),
(241, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 581, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:24:56'),
(242, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:24:57'),
(243, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:25:58'),
(244, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 390, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:32:28'),
(245, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:32:58'),
(246, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 90, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:34:28'),
(247, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:34:58'),
(248, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 283, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:39:40'),
(249, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 5, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:39:45'),
(250, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 5, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-23', '2026-05-23 19:39:50'),
(251, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 19:59:28'),
(252, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 5, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 19:59:57'),
(253, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:00:37'),
(254, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:01:37'),
(255, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 695, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:13:11'),
(256, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 862, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:27:34'),
(257, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 657, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:38:31'),
(258, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 391, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:45:02'),
(259, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:45:04'),
(260, 6, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 5, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:45:16'),
(261, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-23', '2026-05-23 20:45:18'),
(262, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/', 18, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:25:40'),
(263, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:25:47'),
(264, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:26:05'),
(265, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/user/destination_details.php?id=5', 31, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:26:58'),
(266, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:45:07'),
(267, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:45:10'),
(268, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:54:13'),
(269, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 3, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:54:37'),
(270, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 21, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:54:55'),
(271, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 18:55:43'),
(272, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/', 4, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:02:01'),
(273, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:02:03'),
(274, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:02:04'),
(275, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:02:06'),
(276, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 16, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:03:15'),
(277, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:04:08'),
(278, 4, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/', 251, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:08:19'),
(279, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/user/destination_details.php?id=6', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:08:33'),
(280, 4, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/user/destination_details.php?id=6', 3, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 19:16:12'),
(281, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(24.5.26)/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', NULL, '2026-05-24', '2026-05-24 20:39:57'),
(282, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 6, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:41:42'),
(283, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:44:28'),
(284, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:44:41'),
(285, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 1, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:45:07'),
(286, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:45:11'),
(287, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:11'),
(288, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 83, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:28'),
(289, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:30'),
(290, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:34'),
(291, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:36'),
(292, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 9, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:38'),
(293, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 303, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:40'),
(294, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:40'),
(295, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:46:45'),
(296, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:11'),
(297, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 5, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:17'),
(298, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:18'),
(299, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 161, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:18'),
(300, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:19'),
(301, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 173, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:19'),
(302, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:21'),
(303, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project%20/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:47:43'),
(304, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:48:27'),
(305, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=6', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:48:39'),
(306, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:49:19'),
(307, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=6', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:49:29'),
(308, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:49:56'),
(309, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 03:50:10'),
(310, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 31, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-25', '2026-05-25 10:05:12'),
(311, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:45:07'),
(312, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 85, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:46:31'),
(313, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=5', 7, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:47:26'),
(314, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:49:31'),
(315, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 2, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:49:46'),
(316, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 25, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:50:09'),
(317, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=6', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:50:23'),
(318, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:52:02'),
(319, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/user/destination_details.php?id=1', 3, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:52:14'),
(320, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/user/destination_details.php?id=5', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:52:17'),
(321, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:55:21'),
(322, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:56:21'),
(323, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:57:21'),
(324, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:57:51'),
(325, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 31, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:57:57'),
(326, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:58:21'),
(327, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 78, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 05:59:38'),
(328, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 42, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:00:21'),
(329, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 300, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:05:21'),
(330, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:06:21'),
(331, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 722, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:18:23'),
(332, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 58, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:19:21'),
(333, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 470, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:27:11'),
(334, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 1832, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:28:29'),
(335, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 47, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:29:16'),
(336, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 549, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:38:25'),
(337, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/index.html', 53, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:39:18'),
(338, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 727, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:39:18'),
(339, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my%20copy/tripmate/main/index.html', 2, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 06:39:20'),
(340, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 3, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:03:13');
INSERT INTO `page_time_tracking` (`id`, `user_id`, `page_name`, `page_url`, `time_spent`, `click_count`, `ip_address`, `user_agent`, `session_id`, `visit_date`, `created_at`) VALUES
(341, 0, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:03:15'),
(342, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:05:16'),
(343, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:05:38'),
(344, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/main/index.html', 12, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:05:47'),
(345, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 6, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:06:03'),
(346, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:29:02'),
(347, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:29:03'),
(348, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:29:07'),
(349, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 17, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:29:21'),
(350, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my(25.5.26)%20copy/tripmate/user/destination_details.php?id=5', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:29:23'),
(351, 2, 'index', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/main/', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:40:52'),
(352, 2, 'destination_details', 'http://localhost/mejor_project/%20tripmate_final_project_my/tripmate/user/destination_details.php?id=5', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:41:07'),
(353, 2, 'index', 'http://localhost/mejor_project/tripmate_combo/tripmate/main/', 3, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:55:17'),
(354, 2, 'destination_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/user/destination_details.php?id=5', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:55:27'),
(355, 2, 'destination_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/user/destination_details.php?id=5', 53, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:56:19'),
(356, 2, 'destination_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/user/destination_details.php?id=5', 4, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 11:56:24'),
(357, 2, 'index', 'http://localhost/mejor_project/tripmate_combo/tripmate/main/index.html', 4, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:01:51'),
(358, 2, 'index', 'http://localhost/mejor_project/tripmate_combo/tripmate/main/index.html', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:02:43'),
(359, 2, 'admin_dasbord', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/admin_dasbord.php', 7, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:03:55'),
(360, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 14, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:04:09'),
(361, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 28, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:04:22'),
(362, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 30, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:04:25'),
(363, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 25, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:04:50'),
(364, 2, 'admin_dasbord', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/admin_dasbord.php', 63, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:04:50'),
(365, 2, 'admin_dasbord', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/admin_dasbord.php', 1, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:04:51'),
(366, 2, 'admin_dasbord', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/admin_dasbord.php', 11, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:07:17'),
(367, 2, 'add_destination_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/add_destination_on_admin.php', 14, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:07:31'),
(368, 2, 'user_present_chack_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/user_present_chack_on_admin.php', 5, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:07:37'),
(369, 2, 'send_user_email', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/send_user_email.php', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:07:38'),
(370, 2, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/user_ip_tracking_on_admin.php', 15, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:07:53'),
(371, 2, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/user_ip_tracking_on_admin.php?show_all=1', 1, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:07:54'),
(372, 2, 'user_ip_tracking_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/user_ip_tracking_on_admin.php?show_all=0', 25, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:08:19'),
(373, 2, 'page_time_analytics', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/page_time_analytics.php', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:08:23'),
(374, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 5, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:08:28'),
(375, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=4', 24, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:14:57'),
(376, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=4', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:14:58'),
(377, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 18, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:15:16'),
(378, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=3', 12, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:15:29'),
(379, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=3', 12, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:15:41'),
(380, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=3', 13, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:15:54'),
(381, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=3', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:16:02'),
(382, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 8, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:16:09'),
(383, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=2', 4, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:16:14'),
(384, 2, 'booking_details', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/booking_details.php?id=2', 2, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:16:16'),
(385, 2, 'bookings', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/bookings.php', 10, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:16:25'),
(386, 2, 'admin_dasbord', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/admin_dasbord.php', 17, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:16:42'),
(387, 2, 'add_destination_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/add_destination_on_admin.php', 30, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:17:12'),
(388, 2, 'add_destination_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/add_destination_on_admin.php', 35, 11, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:17:47'),
(389, 2, 'add_destination_on_admin', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/add_destination_on_admin.php', 4, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:17:50'),
(390, 2, 'manage_hotels', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/manage_hotels.php?destination_id=25', 30, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:18:20'),
(391, 2, 'manage_hotels', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/manage_hotels.php?destination_id=25', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:19:21'),
(392, 2, 'manage_hotels', 'http://localhost/mejor_project/tripmate_combo/tripmate/admin/manage_hotels.php?destination_id=25', 60, 0, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', NULL, '2026-05-27', '2026-05-27 12:20:21');

-- --------------------------------------------------------

--
-- Table structure for table `price_alerts`
--

CREATE TABLE `price_alerts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `alert_type` enum('flight','hotel','both') DEFAULT 'both',
  `destination_id` int(11) DEFAULT NULL,
  `travel_dates_from` date DEFAULT NULL,
  `travel_dates_to` date DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL COMMENT 'Alert when price drops below this',
  `is_active` tinyint(1) DEFAULT 1,
  `alert_frequency` enum('realtime','daily','weekly') DEFAULT 'daily',
  `notification_method` enum('email','in_app','sms') DEFAULT 'email',
  `price_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Historical price data for analysis' CHECK (json_valid(`price_history`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_history`
--

CREATE TABLE `price_history` (
  `id` int(11) NOT NULL,
  `travel_type` enum('flight','hotel') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `price_trends`
--

CREATE TABLE `price_trends` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `travel_type` enum('flight','hotel') NOT NULL,
  `date` date NOT NULL,
  `average_price` decimal(10,2) NOT NULL,
  `price_change_percent` decimal(5,2) DEFAULT NULL,
  `trend_direction` enum('up','down','stable') DEFAULT 'stable',
  `best_booking_window_days` int(11) DEFAULT NULL COMMENT 'Optimal days before travel',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL COMMENT '1-5 stars',
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL COMMENT 'Review images/photos' CHECK (json_valid(`images`)),
  `images_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images_json`)),
  `review_type` enum('accommodation','restaurant','attraction','general') DEFAULT 'general',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_hash` varchar(255) DEFAULT NULL COMMENT 'Blockchain hash',
  `blockchain_transaction_id` varchar(255) DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `unhelpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `search_history`
--

CREATE TABLE `search_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `search_term` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL,
  `people_count` int(11) DEFAULT NULL,
  `search_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `seasonal_pricing`
--

CREATE TABLE `seasonal_pricing` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `item_type` enum('hotel','flight') NOT NULL,
  `item_id` int(11) NOT NULL,
  `season_name` varchar(50) NOT NULL,
  `start_month` int(11) NOT NULL COMMENT '1-12 for month number',
  `end_month` int(11) NOT NULL COMMENT '1-12 for month number',
  `price_multiplier` decimal(3,2) NOT NULL DEFAULT 1.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `seasonal_pricing`
--

INSERT INTO `seasonal_pricing` (`id`, `destination_id`, `item_type`, `item_id`, `season_name`, `start_month`, `end_month`, `price_multiplier`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 7, 'hotel', 1, 'Peak Winter', 11, 2, 1.40, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(2, 7, 'hotel', 2, 'Peak Winter', 11, 2, 1.35, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(3, 7, 'hotel', 3, 'Peak Winter', 11, 2, 1.50, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(4, 7, 'hotel', 4, 'Peak Winter', 11, 2, 1.60, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(5, 7, 'hotel', 1, 'Summer', 3, 6, 0.80, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(6, 7, 'hotel', 2, 'Summer', 3, 6, 0.75, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(7, 7, 'hotel', 3, 'Summer', 3, 6, 0.85, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(8, 7, 'hotel', 4, 'Summer', 3, 6, 0.90, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(9, 7, 'hotel', 1, 'Monsoon', 7, 10, 0.90, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(10, 7, 'hotel', 2, 'Monsoon', 7, 10, 0.85, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(11, 7, 'hotel', 3, 'Monsoon', 7, 10, 0.95, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(12, 7, 'hotel', 4, 'Monsoon', 7, 10, 1.00, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(13, 7, 'flight', 1, 'Peak Winter', 11, 2, 1.30, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(14, 7, 'flight', 2, 'Peak Winter', 11, 2, 1.25, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(15, 7, 'flight', 3, 'Peak Winter', 11, 2, 1.20, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(16, 7, 'flight', 4, 'Peak Winter', 11, 2, 1.35, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(17, 7, 'flight', 5, 'Peak Winter', 11, 2, 1.40, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(18, 7, 'flight', 6, 'Peak Winter', 11, 2, 1.50, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(19, 7, 'flight', 7, 'Peak Winter', 11, 2, 1.45, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(20, 7, 'flight', 1, 'Summer', 3, 6, 0.85, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(21, 7, 'flight', 2, 'Summer', 3, 6, 0.80, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(22, 7, 'flight', 3, 'Summer', 3, 6, 0.75, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(23, 7, 'flight', 4, 'Summer', 3, 6, 0.90, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(24, 7, 'flight', 5, 'Summer', 3, 6, 0.95, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(25, 7, 'flight', 6, 'Summer', 3, 6, 1.00, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53'),
(26, 7, 'flight', 7, 'Summer', 3, 6, 1.05, 1, '2026-03-11 11:06:53', '2026-03-11 11:06:53');

-- --------------------------------------------------------

--
-- Table structure for table `tourist_spots`
--

CREATE TABLE `tourist_spots` (
  `id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `type` varchar(100) DEFAULT 'General',
  `description` text DEFAULT NULL,
  `timing` varchar(200) DEFAULT NULL,
  `entry_fee` decimal(10,2) DEFAULT 0.00,
  `image_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`image_urls`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `travel_packages`
--

CREATE TABLE `travel_packages` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `flight_id` int(11) DEFAULT NULL,
  `package_name` varchar(255) NOT NULL,
  `package_type` enum('low','medium','high') NOT NULL,
  `total_price_per_person` decimal(10,2) NOT NULL,
  `duration_days` int(11) NOT NULL,
  `inclusions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`inclusions`)),
  `highlights` text DEFAULT NULL,
  `package_image` varchar(500) DEFAULT NULL,
  `available_from` date DEFAULT NULL,
  `available_to` date DEFAULT NULL,
  `max_travelers` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `travel_packages`
--

INSERT INTO `travel_packages` (`id`, `destination_id`, `hotel_id`, `flight_id`, `package_name`, `package_type`, `total_price_per_person`, `duration_days`, `inclusions`, `highlights`, `package_image`, `available_from`, `available_to`, `max_travelers`, `created_at`, `updated_at`) VALUES
(1, 7, 1, 1, 'Agra Express - Budget Special', 'low', 4500.00, 3, '{\"accommodation\": \"3 nights in Budget Hotel\", \"flights\": \"Round trip economy class\", \"meals\": \"Breakfast only\", \"transfers\": \"Airport transfers not included\", \"sightseeing\": \"Taj Mahal visit (ticket not included)\"}', 'Perfect for budget travelers! Visit the iconic Taj Mahal and Agra Fort with comfortable budget accommodation. Includes round-trip flights from Mumbai.', '/uploads/packages/agra-budget-1.jpg', '2025-01-01', '2025-12-31', 8, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(2, 7, 2, 2, 'Delhi to Agra Weekend Getaway', 'low', 3200.00, 2, '{\"accommodation\": \"2 nights in Budget Hotel\", \"flights\": \"Round trip economy class from Delhi\", \"meals\": \"Breakfast included\", \"transfers\": \"Airport transfers included\", \"sightseeing\": \"Taj Mahal sunrise visit\"}', 'Quick weekend escape from Delhi! Includes flights, comfortable stay, and sunrise visit to the Taj Mahal.', '/uploads/packages/agra-budget-2.jpg', '2025-01-01', '2025-12-31', 10, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(3, 7, 3, 4, 'Royal Agra Experience - Medium', 'medium', 12500.00, 4, '{\"accommodation\": \"4 nights in Premium Hotel\", \"flights\": \"Round trip premium economy\", \"meals\": \"Breakfast and dinner included\", \"transfers\": \"Private airport transfers\", \"sightseeing\": \"Taj Mahal, Agra Fort, Fatehpur Sikri with guide\", \"extras\": \"Sunset view of Taj Mahal from Mehtab Bagh\"}', 'Experience the royal heritage of Agra in comfort. Visit all major monuments with expert guides and enjoy premium accommodation.', '/uploads/packages/agra-medium-1.jpg', '2025-01-01', '2025-12-31', 6, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(4, 7, 3, 5, 'Delhi-Agra Heritage Tour', 'medium', 5800.00, 3, '{\"accommodation\": \"3 nights in Premium Hotel\", \"flights\": \"Round trip premium economy from Delhi\", \"meals\": \"Breakfast and one dinner\", \"transfers\": \"Private transfers\", \"sightseeing\": \"Taj Mahal, Agra Fort, Itimad-ud-Daulah\", \"extras\": \"Traditional Mughlai dinner\"}', 'Perfect heritage tour from Delhi. Includes comfortable stay and guided tours of Agra\'s finest monuments.', '/uploads/packages/agra-medium-2.jpg', '2025-01-01', '2025-12-31', 8, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(5, 7, 4, 6, 'Luxury Agra Escape', 'high', 28500.00, 5, '{\"accommodation\": \"5 nights in Luxury Boutique Hotel\", \"flights\": \"Round trip business class\", \"meals\": \"All meals included (breakfast, lunch, dinner)\", \"transfers\": \"Luxury private transfers with chauffeur\", \"sightseeing\": \"Private guided tours of all monuments\", \"extras\": \"Sunrise and sunset views of Taj Mahal, Spa treatment, Private dinner with view of Taj Mahal\"}', 'Ultimate luxury experience in Agra. Stay in a premium boutique hotel with stunning views, enjoy business class flights, and experience the Taj Mahal like never before.', '/uploads/packages/agra-high-1.jpg', '2025-01-01', '2025-12-31', 4, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(6, 7, 4, 7, 'Executive Agra Retreat', 'high', 18900.00, 4, '{\"accommodation\": \"4 nights in Luxury Boutique Hotel\", \"flights\": \"Round trip business class from Delhi\", \"meals\": \"Breakfast and gourmet dinners\", \"transfers\": \"Luxury private transfers\", \"sightseeing\": \"Private guided heritage tours\", \"extras\": \"Photography session at Taj Mahal, Cooking class, Elephant conservation visit\"}', 'Executive retreat combining luxury accommodation with authentic cultural experiences. Perfect for discerning travelers.', '/uploads/packages/agra-high-2.jpg', '2025-01-01', '2025-12-31', 4, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(7, 7, 4, 6, 'Winter Wonder - Taj Mahal Special', 'high', 32900.00, 5, '{\"accommodation\": \"5 nights in Luxury Hotel with winter upgrades\", \"flights\": \"Round trip business class\", \"meals\": \"All meals including special winter menu\", \"transfers\": \"Luxury private transfers\", \"sightseeing\": \"Private tours with expert historian\", \"extras\": \"Bonfire evening, Winter festival tickets, Spa sessions\"}', 'Special winter package! Experience Agra in the pleasant winter weather with luxury upgrades and special experiences.', '/uploads/packages/agra-winter-special.jpg', '2025-11-01', '2026-02-28', 4, '2026-03-11 12:48:07', '2026-03-11 12:48:07'),
(8, 7, 3, NULL, 'Agra Flexi Package - Hotel Only with Flight Option', 'medium', 6500.00, 3, '{\"accommodation\": \"3 nights in Premium Hotel\", \"meals\": \"Breakfast included\", \"transfers\": \"Airport transfers not included\", \"sightseeing\": \"Flexible sightseeing options\", \"note\": \"Flights can be added separately\"}', 'Flexible package where you choose your own flights. Includes premium accommodation and daily breakfast.', '/uploads/packages/agra-flexi.jpg', '2025-01-01', '2025-12-31', 10, '2026-03-11 12:48:07', '2026-03-11 12:48:07');

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
(2, 'ranajitbarik', 'ranajitbarik2005@gmail.com', '$2y$10$G2DtcH1L6jgOlOljzCy9kOo/iQOBsWQ1hrnGNf4ezFaG2FEYl/Dca', 'manual', '112086762010421219083', 'https://lh3.googleusercontent.com/a/ACg8ocJCNrffB3Yyf-OR2uyol6dUWmRhQF0nxxkaRP-R9Q84B5NVRgwz4Q=s96-c', '2026-04-17 04:27:17', 'normal'),
(4, 'ranajitbarik', 'ranajitbarik071@gmail.com', '$2y$10$2hm6JHwJB4cmdMC7D/F7zepkBNpgnO5Sc6W1GUHlfDNnORGn4Pysu', 'manual', '116866011450521034725', 'https://lh3.googleusercontent.com/a/ACg8ocKugT0laPsNBYBb7ERVVScJiQVtKguCIN9jfTW0j20kj41EGPQ=s96-c', '2026-05-20 09:32:44', 'normal'),
(5, 'ranajitbarik', 'ranajitbarik85@gmail.com', '$2y$10$APQhfgaPSDE1MFZaXZLrPeMlKVMLI3JiEV79ZDlTnT8fjM2jcdxa.', 'manual', '104047787347136703822', 'https://lh3.googleusercontent.com/a/ACg8ocKhpI0_rGk179QBR2DwnjCXaqT19FLFydUd-hdLDB7-UALIBD4=s96-c', '2026-05-22 19:46:38', 'normal'),
(6, 'user1', 'user1@gmail.com', '$2y$10$vXHRI8aSk7uM8bUZNr4VHOu1MLfBOnc9yrv/pAkmbtX550ln9P9jG', 'manual', NULL, NULL, '2026-05-23 19:59:51', 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `users_google`
--

CREATE TABLE `users_google` (
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

-- --------------------------------------------------------

--
-- Table structure for table `user_history`
--

CREATE TABLE `user_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('search','destination_view','favorite','login','booking') NOT NULL,
  `activity_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_history`
--

INSERT INTO `user_history` (`id`, `user_id`, `activity_type`, `activity_details`, `created_at`) VALUES
(1, 2, 'booking', '{\"booking_id\":\"TMD08033AA20260527\",\"action\":\"view\",\"timestamp\":\"2026-05-27 14:01:12\",\"ip\":\"::1\"}', '2026-05-27 12:01:12');

--
-- Triggers `user_history`
--
DELIMITER $$
CREATE TRIGGER `sync_favorites_delete` AFTER DELETE ON `user_history` FOR EACH ROW BEGIN
    IF OLD.activity_type = 'favorite' THEN
        DELETE FROM favorites WHERE user_id = OLD.user_id AND destination_id = OLD.activity_details;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `sync_favorites_insert` AFTER INSERT ON `user_history` FOR EACH ROW BEGIN
    IF NEW.activity_type = 'favorite' THEN
        INSERT IGNORE INTO favorites (user_id, destination_id, created_at) 
        VALUES (NEW.user_id, NEW.activity_details, NEW.created_at);
    END IF;
END
$$
DELIMITER ;

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
(1, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 13:50:12'),
(2, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 13:50:21'),
(3, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 13:50:29'),
(4, 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-31 13:12:35'),
(5, 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-31 13:59:10'),
(11, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 12:23:26'),
(12, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 02:14:19'),
(13, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 02:55:15'),
(14, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 03:44:10'),
(15, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 03:02:46'),
(17, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 07:56:55'),
(18, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 08:03:05'),
(20, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 08:10:36'),
(22, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 08:15:25'),
(23, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 08:15:58'),
(24, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 08:16:46'),
(25, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-04-02 08:19:34'),
(26, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 08:20:38'),
(27, 6, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 08:22:57'),
(28, 7, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 09:28:36'),
(29, 8, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 09:31:31'),
(30, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 09:32:02'),
(31, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 09:34:44'),
(32, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 10:03:22'),
(33, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 18:54:12'),
(34, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:01:51'),
(35, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:02:16'),
(36, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:08:22'),
(37, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:11:05'),
(38, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:28:20'),
(39, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:29:06'),
(40, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:35:43'),
(41, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:46:36'),
(42, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 19:55:24'),
(43, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-02 20:13:24'),
(44, 10, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-03 06:51:33'),
(45, 9, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-03 06:58:00'),
(46, 12, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-03 18:20:57'),
(52, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-05 15:01:56'),
(53, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-08 19:17:37'),
(55, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-08 19:38:04'),
(57, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-08 19:57:48'),
(59, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.3.1 Safari/605.1.15', '2026-04-09 09:14:56'),
(61, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-24 18:03:51'),
(62, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-24 18:20:29'),
(63, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-24 19:03:23'),
(64, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-27 15:01:46'),
(65, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-27 15:02:22'),
(68, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-04-30 12:59:44'),
(69, 1, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-04-30 13:47:44'),
(70, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-10 07:35:19'),
(71, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '2026-05-10 07:42:05'),
(72, 6, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-20 09:31:52'),
(73, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-22 18:17:12'),
(74, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-22 18:40:33'),
(75, 3, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-22 19:44:28'),
(76, 5, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-22 19:47:02'),
(77, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-22 20:04:24'),
(78, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-23 19:11:14'),
(79, 6, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 19:59:51'),
(80, 6, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-23 20:00:06'),
(81, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-24 18:26:01'),
(82, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-24 18:54:24'),
(83, 4, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.4 Safari/605.1.15', '2026-05-24 19:02:49'),
(84, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:42:05'),
(85, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:42:31'),
(86, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:44:36'),
(87, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:45:13'),
(88, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:46:09'),
(89, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:46:53'),
(90, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-25 03:47:53'),
(91, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 05:47:13'),
(92, 2, '::1', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36', '2026-05-27 11:05:09');

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

-- --------------------------------------------------------

--
-- Table structure for table `views`
--

CREATE TABLE `views` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `destination_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `website_analytics`
--

CREATE TABLE `website_analytics` (
  `id` int(11) NOT NULL,
  `page_name` varchar(255) NOT NULL,
  `page_type` enum('destination','blog','home','about','contact') NOT NULL,
  `views` int(11) DEFAULT 0,
  `clicks` int(11) DEFAULT 0,
  `date_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `website_analytics`
--

INSERT INTO `website_analytics` (`id`, `page_name`, `page_type`, `views`, `clicks`, `date_date`, `created_at`) VALUES
(1, 'Home Page', 'home', 1500, 300, '2025-11-17', '2025-11-17 02:14:20'),
(2, 'Destination List', 'destination', 800, 450, '2025-11-17', '2025-11-17 02:14:20'),
(3, 'Blog Page', 'blog', 600, 200, '2025-11-17', '2025-11-17 02:14:20'),
(4, 'About Us', 'about', 300, 50, '2025-11-17', '2025-11-17 02:14:20'),
(5, 'Contact', 'contact', 400, 100, '2025-11-17', '2025-11-17 02:14:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_suggestions`
--
ALTER TABLE `activity_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `itinerary_day_id` (`itinerary_day_id`);

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `admin_ips`
--
ALTER TABLE `admin_ips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `booking_id` (`booking_id`);

--
-- Indexes for table `contributors`
--
ALTER TABLE `contributors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email_unique` (`email`),
  ADD UNIQUE KEY `username_unique` (`username`);

--
-- Indexes for table `contributor_destinations`
--
ALTER TABLE `contributor_destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cd_contributor` (`contributor_id`),
  ADD KEY `idx_cd_destination` (`destination_id`);

--
-- Indexes for table `contributor_earnings`
--
ALTER TABLE `contributor_earnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `contributor_id` (`contributor_id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_budget` (`budget`),
  ADD KEY `idx_season` (`season`),
  ADD KEY `idx_submitted` (`submitted_by_type`,`submitted_by_id`,`submission_status`),
  ADD KEY `idx_submitted_by_type` (`submitted_by_type`),
  ADD KEY `idx_submitted_by_id` (`submitted_by_id`),
  ADD KEY `idx_submission_status` (`submission_status`);

--
-- Indexes for table `destination_cities`
--
ALTER TABLE `destination_cities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cities_dest` (`destination_id`);

--
-- Indexes for table `email_log`
--
ALTER TABLE `email_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `errors`
--
ALTER TABLE `errors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `favorites`
--
ALTER TABLE `favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `u_user_dest` (`user_id`,`destination_id`),
  ADD KEY `idx_destination` (`destination_id`);

--
-- Indexes for table `flights`
--
ALTER TABLE `flights`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_flights_city` (`city_id`),
  ADD KEY `idx_flights_dest` (`destination_id`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hotels_city` (`city_id`),
  ADD KEY `idx_hotels_dest` (`destination_id`);

--
-- Indexes for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `itinerary_days`
--
ALTER TABLE `itinerary_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `itinerary_id` (`itinerary_id`);

--
-- Indexes for table `page_time_tracking`
--
ALTER TABLE `page_time_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_visit_date` (`visit_date`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_page_name` (`page_name`);

--
-- Indexes for table `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  ADD PRIMARY KEY (`id`),
  ADD KEY `destination_id` (`destination_id`),
  ADD KEY `idx_spots_city` (`city_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users_google`
--
ALTER TABLE `users_google`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_users_google_email` (`email`);

--
-- Indexes for table `user_history`
--
ALTER TABLE `user_history`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_ips`
--
ALTER TABLE `admin_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `booking_payments`
--
ALTER TABLE `booking_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contributors`
--
ALTER TABLE `contributors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `contributor_destinations`
--
ALTER TABLE `contributor_destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `contributor_earnings`
--
ALTER TABLE `contributor_earnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `destination_cities`
--
ALTER TABLE `destination_cities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_log`
--
ALTER TABLE `email_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `page_time_tracking`
--
ALTER TABLE `page_time_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=393;

--
-- AUTO_INCREMENT for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users_google`
--
ALTER TABLE `users_google`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_history`
--
ALTER TABLE `user_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `booking_payments`
--
ALTER TABLE `booking_payments`
  ADD CONSTRAINT `booking_payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contributor_destinations`
--
ALTER TABLE `contributor_destinations`
  ADD CONSTRAINT `fk_cd_contributor` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cd_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `contributor_earnings`
--
ALTER TABLE `contributor_earnings`
  ADD CONSTRAINT `fk_ce_contributor` FOREIGN KEY (`contributor_id`) REFERENCES `contributors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `destination_cities`
--
ALTER TABLE `destination_cities`
  ADD CONSTRAINT `destination_cities_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `flights`
--
ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tourist_spots`
--
ALTER TABLE `tourist_spots`
  ADD CONSTRAINT `tourist_spots_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `destination_cities` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tourist_spots_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
