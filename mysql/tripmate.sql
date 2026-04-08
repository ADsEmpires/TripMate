-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 07, 2026 at 08:01 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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
(1, 'admin@123', 'admin@gmail.com', '$2y$10$AlP0uyFkcON5ui8rCxzsLeaIrg.KCOjsZ8rB2sAnNayZg5QeehSIG', '2025-08-05 19:01:34', NULL),
(4, 'adm', 'adm@gmail.com', '$2y$10$zg7TP2EARQ4FjemjJLYbye8HitGUxfR6.rp0Pab4sQTH.jiVMsZG2', '2025-08-08 17:57:22', NULL),
(5, 'Arnab', 'adn@gmail.com', '$2y$10$rGcnFJVjy9LlCKcnbpt7FuEyvZSzqx0.eEZFhBLhV6Z1lGjzLD1aO', '2025-08-09 15:15:42', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blog_comments`
--

CREATE TABLE `blog_comments` (
  `id` int(11) NOT NULL,
  `blog_post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `blog_posts`
--

CREATE TABLE `blog_posts` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT 'travel',
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `likes_count` int(11) DEFAULT 0,
  `comments_count` int(11) DEFAULT 0,
  `status` enum('published','draft','archived') DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `blog_posts`
--

INSERT INTO `blog_posts` (`id`, `user_id`, `title`, `content`, `category`, `images`, `tags`, `likes_count`, `comments_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 5, 'Nice trip', 'Very good', 'food', NULL, '[\"#tasty\",\"#yummy\"]', 0, 0, 'published', '2026-04-07 16:06:16', '2026-04-07 16:06:16');

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
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL COMMENT 'Location latitude',
  `longitude` decimal(11,8) DEFAULT NULL COMMENT 'Location longitude',
  `budget` decimal(10,2) NOT NULL,
  `best_season` varchar(100) DEFAULT NULL,
  `image_urls` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`image_urls`)),
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
  `profile_pic` varchar(255) DEFAULT NULL
) ;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `type`, `description`, `location`, `latitude`, `longitude`, `budget`, `best_season`, `image_urls`, `map_link`, `attractions`, `created_at`, `updated_at`, `season`, `people`, `tips`, `cuisines`, `cuisine_images`, `language`, `profile_pic`) VALUES
(1, 'Bali', 'historical', 'A tropical paradise known for its stunning beaches, vibrant culture, and lush landscapes.', 'Bali, Indonesia', -8.40950000, 115.18890000, 100.00, 'April to October', '[\"uploads\\/68c592c8b9bac_bali.jpg\"]', 'https://www.google.com/maps/place/Bali', 'Uluwatu Temple, Kuta Beach, Tegallalang Rice Terraces', '2025-08-09 06:47:34', '2025-09-13 15:50:32', 'spring', '[\"1\"]', '[\"Tip 1\", \"Tip 2\"]', '[\"Cuisine 1\", \"Cuisine 2\"]', '{\"Cuisine 1\": \"image1.jpg\", \"Cuisine 2\": \"image2.jpg\"}', '[\"Lang 1\", \"Lang 2\"]', NULL),
(2, 'Paris', 'city', 'The capital of France, known for its art, fashion, and iconic landmarks like the Eiffel Tower.', 'Paris, France', 48.85660000, 2.35220000, 150.00, 'April to June', '[\"uploads\\/68c592b6df663_paris.jpg\"]', 'https://www.google.com/maps/place/Paris', '[\"Eiffel Tower\",\"Louvre Museum\",\"Notre-Dame Cathedral\"]', '2025-08-09 06:47:34', '2025-09-13 16:22:25', 'summer', '[\"1\"]', NULL, '\"\"', '[]', NULL, NULL),
(3, 'Kyoto', 'village', 'Famous for its classical Buddhist temples, as well as gardens, imperial palaces, Shinto shrines, and traditional wooden houses.', 'Kyoto, Japan', 35.01160000, 135.76810000, 120.00, 'March to May', '[\"uploads\\/68c592a2d0ac3_kyoto.jpeg\"]', 'https://www.google.com/maps/place/Kyoto', 'Kinkaku-ji, Fushimi Inari-taisha, Arashiyama Bamboo Grove', '2025-08-09 06:47:34', '2025-09-13 15:49:54', 'spring', '[\"1\"]', '[]', '[]', '{}', '[]', NULL),
(4, 'New York City', 'city', 'Known as THE BIG APPLE, NYC is famous for its skyline, Broadway shows, and diverse culture.', 'New York, USA', 40.71280000, -74.00600000, 200.00, 'April to June', '[\"uploads\\/68c5928b8b0d1_newyorkcity.jpeg\"]', 'https://www.google.com/maps/place/New+York', 'Statue of Liberty, Central Park, Times Square', '2025-08-09 06:47:34', '2025-09-13 15:49:31', 'winter', '[\"1\"]', '[]', '[]', '{}', '[]', NULL),
(5, 'Santorini', 'beach', 'A beautiful island in the Aegean Sea known for its stunning sunsets and whitewashed buildings.', 'Santorini, Greece', 36.39320000, 25.46150000, 180.00, 'May to October', '[\"68db8b7732616_santorini.jpg\"]', 'https://www.google.com/maps/place/Santorini', '[\"Oia, Akrotiri, Red Beach\"]', '2025-08-09 06:47:34', '2025-09-30 07:49:11', 'autumn', '[\"1\"]', '[]', '[\"Biryani\"]', '{\"Biryani\":\"68db8b7733750_Biryani.jpg\"}', '[]', NULL),
(6, 'Machu Picchu', 'historical', 'An ancient Incan city set high in the Andes Mountains in Peru, known for its archaeological significance.', 'Machu Picchu, Peru', -13.16310000, -72.54500000, 250.00, 'April to October', '[\"68db8b4586f5f_machu-picchu-facts-travel-information.webp\"]', 'https://www.google.com/maps/place/Machu+Picchu', '[\"Inca Trail, Sacred Valley, Huayna Picchu\"]', '2025-08-09 06:47:34', '2025-09-30 07:48:21', 'spring,autumn', '[\"3-5\",\"6-9\"]', '[]', '[\"Biryani\"]', '{\"Biryani\":\"68db8b458ab37_Biryani.jpg\"}', '[]', NULL),
(7, 'Agra', 'city', 'Agra is a historical city in Uttar Pradesh, India, renowned for its Mughal-era architecture, particularly the Taj Mahal. It is a major tourist destination, attracting millions due to its rich history and iconic landmarks. Besides the Taj Mahal, Agra is also home to Agra Fort and Fatehpur Sikri, all UNESCO World Heritage Sites.', 'Uttar Pradesh, India', 0.00000000, 0.00000000, 800.00, NULL, '[\"68c59b22a1f03_agra.jpg\"]', 'https://www.google.com/maps/place/Agra,+Uttar+Pradesh/@27.1761263,77.9800127,12z/data=!4m6!3m5!1s0x39740d857c2f41d9:0x784aef38a9523b42!8m2!3d27.1766701!4d78.0080745!16zL20vMDF6eHg5?entry=ttu&g_ep=EgoyMDI2MDIxOC4wIKXMDSoASAFQAw%3D%3D', '[\"Taj Mahal\",\"Agra Fort\",\"Imtad-ud-Daula\"]', '2025-08-10 14:46:33', '2026-02-24 15:55:30', 'winter,summer,spring,autumn,monsoon', '[\"1\",\"2\",\"3-5\"]', '[]', '[]', '{\"Biryani\":\"690b7fcbb5263_Biryani.jpg\"}', '[]', NULL);

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
(1, 5, 7, '2026-02-22 10:02:10'),
(2, 5, 2, '2026-04-05 13:41:39');

-- --------------------------------------------------------

--
-- Table structure for table `flights`
--

CREATE TABLE `flights` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `airline` varchar(100) NOT NULL,
  `flight_type` enum('low','medium','high') NOT NULL,
  `price_per_person` decimal(10,2) NOT NULL,
  `duration_hours` decimal(4,2) DEFAULT NULL,
  `stops` int(11) DEFAULT 0,
  `departure_time` varchar(50) DEFAULT NULL,
  `arrival_time` varchar(50) DEFAULT NULL,
  `flight_class` varchar(50) DEFAULT NULL,
  `baggage_allowance` varchar(100) DEFAULT NULL,
  `refundable` tinyint(1) DEFAULT 0,
  `meal_included` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `flights`
--

INSERT INTO `flights` (`id`, `destination_id`, `departure_city`, `airline`, `flight_type`, `price_per_person`, `duration_hours`, `stops`, `departure_time`, `arrival_time`, `flight_class`, `baggage_allowance`, `refundable`, `meal_included`, `created_at`, `updated_at`) VALUES
(1, 7, 'Mumbai', 'IndiGo', 'low', 7000.00, 2.50, 0, '12:00 PM', '02:05 PM', 'Economy', '15kg check-in + 7kg cabin', 0, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27'),
(2, 7, 'Delhi', 'SpiceJet', 'low', 1800.00, 3.00, 0, '2:00 PM', '5:00 PM', 'Economy', '15kg check-in + 7kg cabin', 0, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27'),
(3, 7, 'Bangalore', 'GoAir', 'low', 8000.00, 2.80, 0, '6:00 AM', '8:48 AM', 'Economy', '15kg check-in + 7kg cabin', 0, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27'),
(4, 7, 'Mumbai', 'Vistara', 'medium', 10000.00, 2.50, 0, '9:00 AM', '11:30 AM', 'Premium Economy', '25kg check-in + 7kg cabin', 1, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27'),
(5, 7, 'Delhi', 'Air India', 'medium', 3200.00, 3.00, 0, '4:00 PM', '7:00 PM', 'Premium Economy', '25kg check-in + 7kg cabin', 1, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27'),
(6, 7, 'Mumbai', 'Emirates', 'high', 15500.00, 2.20, 0, '8:00 AM', '10:12 AM', 'Business Class', '40kg check-in + 12kg cabin', 1, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27'),
(7, 7, 'Delhi', 'Singapore Airlines', 'high', 6200.00, 2.80, 0, '11:00 PM', '1:48 AM', 'Business Class', '40kg check-in + 12kg cabin', 1, 1, '2026-02-24 18:45:27', '2026-02-24 18:45:27');

-- --------------------------------------------------------

--
-- Table structure for table `hotels`
--

CREATE TABLE `hotels` (
  `id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `hotel_name` varchar(255) NOT NULL,
  `hotel_type` enum('low','medium','high') NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `hotel_rating` decimal(2,1) DEFAULT 0.0,
  `description` text DEFAULT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `image_url` varchar(500) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `check_in_time` time DEFAULT '12:00:00',
  `check_out_time` time DEFAULT '11:00:00',
  `free_cancellation` tinyint(1) DEFAULT 1,
  `breakfast_included` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hotels`
--

INSERT INTO `hotels` (`id`, `destination_id`, `hotel_name`, `hotel_type`, `price_per_night`, `hotel_rating`, `description`, `amenities`, `image_url`, `address`, `contact_number`, `check_in_time`, `check_out_time`, `free_cancellation`, `breakfast_included`, `created_at`, `updated_at`) VALUES
(1, 7, 'Hotel Olive Tree', 'low', 410.00, 4.5, 'Clean and comfortable dormitory-style accommodation with shared facilities', '[\"Free WiFi\", \"Shared Kitchen\", \"Lounge Area\", \"Lockers\"]', '/uploads/hotels/agra-low1.jpg', 'Phase2, Taj Nagari, Tajganj, Agra, Basai, 282004', '+91-9359104395', '12:00:00', '11:00:00', 1, 0, '2026-02-24 18:35:04', '2026-02-24 18:35:04'),
(2, 7, 'Hotel La Serene', 'low', 850.00, 4.8, 'Basic but clean rooms with private bathroom. Perfect for budget travelers', '[\"Free WiFi\", \"TV\", \"AC\", \"Attached Bathroom\"]', '/uploads/hotels/agra-low2.jpg', 'B/123, Fatehabad Rd, behind C.N.G Pump, Taj Nagri Phase 2, Tajganj, Agra, Basai, Uttar Pradesh 282004', '+91-7668129957', '12:00:00', '11:00:00', 1, 0, '2026-02-24 18:35:04', '2026-02-24 18:35:04'),
(3, 7, 'The Orchid Retreat', 'medium', 2950.00, 4.2, 'Modern hotel with comfortable rooms and excellent service', '[\"Free WiFi\", \"Swimming Pool\", \"Restaurant\", \"Room Service\", \"Gym\"]', '/uploads/hotels/agra-mid1.jpg', 'Plot No 28 Taj Nagri Phase 1 Taj East Gate Road, Shilpgram Rd, Agra, Uttar Pradesh 282006', '+91-8736960000', '12:00:00', '11:00:00', 1, 1, '2026-02-24 18:35:04', '2026-02-24 18:35:04'),
(4, 7, 'Aman Homestay, A Boutique Hotel', 'high', 6200.00, 4.8, '5-star luxury resort with world-class amenities and stunning views', '[\"Free WiFi\", \"Spa\", \"Multiple Restaurants\", \"Infinity Pool\", \"Private Beach\", \"Butler Service\", \"Gym\", \"Kids Club\"]', '/uploads/hotels/luxury1.jpg', 'Shilpgram Parking, P-18, MIG Colony, Shilpgram Rd, Taj Nagari Phase 1, Before, Agra, Uttar Pradesh, 282006', '+91-5622331234', '12:00:00', '11:00:00', 1, 1, '2026-02-24 18:35:04', '2026-02-24 18:35:04');

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
(1, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 17:59:56', '2026-02-18 17:59:56'),
(2, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:00:00', '2026-02-18 18:00:00'),
(3, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:00:01', '2026-02-18 18:00:01'),
(4, 5, 7, 'Trip to Agra', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:00:02', '2026-02-18 18:00:02'),
(5, 5, 2, 'Trip to Paris', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:00:12', '2026-02-18 18:00:12'),
(6, 5, 2, 'Trip to Paris', '2026-03-02', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:00:14', '2026-02-18 18:00:14'),
(7, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:05', '2026-02-18 18:08:05'),
(8, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:07', '2026-02-18 18:08:07'),
(9, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:08', '2026-02-18 18:08:08'),
(10, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:08', '2026-02-18 18:08:08'),
(11, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:09', '2026-02-18 18:08:09'),
(12, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:09', '2026-02-18 18:08:09'),
(13, 5, 2, 'Trip to Paris', '2026-03-04', '2026-03-18', 5000.00, 'luxury', '[]', 1, 'draft', '2026-02-18 18:08:09', '2026-02-18 18:08:09'),
(14, 5, 2, 'Trip to Paris', '2026-02-25', '2026-03-04', 5000.00, 'budget', '[]', 1, 'draft', '2026-02-21 18:44:45', '2026-02-21 18:44:45'),
(15, 5, 2, 'Trip to Paris', '2026-02-25', '2026-03-04', 5000.00, 'budget', '[]', 1, 'draft', '2026-02-21 18:44:47', '2026-02-21 18:44:47'),
(16, 5, 2, 'Trip to Paris', '2026-02-25', '2026-03-04', 5000.00, 'budget', '[]', 1, 'draft', '2026-02-21 18:44:48', '2026-02-21 18:44:48');

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

--
-- Dumping data for table `price_alerts`
--

INSERT INTO `price_alerts` (`id`, `user_id`, `alert_type`, `destination_id`, `travel_dates_from`, `travel_dates_to`, `max_price`, `is_active`, `alert_frequency`, `notification_method`, `price_history`, `created_at`, `updated_at`) VALUES
(1, 5, '', 2, '2026-03-01', '0000-00-00', 28000.00, 1, 'weekly', 'email', NULL, '2026-02-18 18:09:16', '2026-02-18 18:09:16'),
(2, 5, '', 3, '2026-02-27', '0000-00-00', 30000.00, 1, '', 'email', NULL, '2026-02-18 18:17:47', '2026-02-18 18:17:47'),
(3, 5, '', 5, '2026-02-20', '0000-00-00', 30000.00, 1, 'daily', 'email', NULL, '2026-02-18 18:21:17', '2026-02-18 18:21:17'),
(4, 5, '', 7, '2026-02-25', '0000-00-00', 25000.00, 1, '', 'email', NULL, '2026-02-22 03:29:23', '2026-02-22 03:29:23');

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
  `rating` int(11) NOT NULL CHECK (`rating` between 1 and 5),
  `title` varchar(255) DEFAULT NULL,
  `comment` text NOT NULL,
  `images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`images`)),
  `helpful_count` int(11) DEFAULT 0,
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
(1, 7, 'hotel', 1, 'Peak Winter', 11, 2, 1.40, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(2, 7, 'hotel', 2, 'Peak Winter', 11, 2, 1.35, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(3, 7, 'hotel', 3, 'Peak Winter', 11, 2, 1.50, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(4, 7, 'hotel', 4, 'Peak Winter', 11, 2, 1.60, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(5, 7, 'hotel', 1, 'Summer', 3, 6, 0.80, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(6, 7, 'hotel', 2, 'Summer', 3, 6, 0.75, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(7, 7, 'hotel', 3, 'Summer', 3, 6, 0.85, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(8, 7, 'hotel', 4, 'Summer', 3, 6, 0.90, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(9, 7, 'hotel', 1, 'Monsoon', 7, 10, 0.90, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(10, 7, 'hotel', 2, 'Monsoon', 7, 10, 0.85, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(11, 7, 'hotel', 3, 'Monsoon', 7, 10, 0.95, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(12, 7, 'hotel', 4, 'Monsoon', 7, 10, 1.00, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(13, 7, 'flight', 1, 'Peak Winter', 11, 2, 1.30, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(14, 7, 'flight', 2, 'Peak Winter', 11, 2, 1.25, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(15, 7, 'flight', 3, 'Peak Winter', 11, 2, 1.20, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(16, 7, 'flight', 4, 'Peak Winter', 11, 2, 1.35, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(17, 7, 'flight', 5, 'Peak Winter', 11, 2, 1.40, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(18, 7, 'flight', 6, 'Peak Winter', 11, 2, 1.50, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(19, 7, 'flight', 7, 'Peak Winter', 11, 2, 1.45, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(20, 7, 'flight', 1, 'Summer', 3, 6, 0.85, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(21, 7, 'flight', 2, 'Summer', 3, 6, 0.80, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(22, 7, 'flight', 3, 'Summer', 3, 6, 0.75, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(23, 7, 'flight', 4, 'Summer', 3, 6, 0.90, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(24, 7, 'flight', 5, 'Summer', 3, 6, 0.95, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(25, 7, 'flight', 6, 'Summer', 3, 6, 1.00, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53'),
(26, 7, 'flight', 7, 'Summer', 3, 6, 1.05, 1, '2026-03-11 16:36:53', '2026-03-11 16:36:53');

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
(1, 7, 1, 1, 'Agra Express - Budget Special', 'low', 4500.00, 3, '{\"accommodation\": \"3 nights in Budget Hotel\", \"flights\": \"Round trip economy class\", \"meals\": \"Breakfast only\", \"transfers\": \"Airport transfers not included\", \"sightseeing\": \"Taj Mahal visit (ticket not included)\"}', 'Perfect for budget travelers! Visit the iconic Taj Mahal and Agra Fort with comfortable budget accommodation. Includes round-trip flights from Mumbai.', '/uploads/packages/agra-budget-1.jpg', '2025-01-01', '2025-12-31', 8, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(2, 7, 2, 2, 'Delhi to Agra Weekend Getaway', 'low', 3200.00, 2, '{\"accommodation\": \"2 nights in Budget Hotel\", \"flights\": \"Round trip economy class from Delhi\", \"meals\": \"Breakfast included\", \"transfers\": \"Airport transfers included\", \"sightseeing\": \"Taj Mahal sunrise visit\"}', 'Quick weekend escape from Delhi! Includes flights, comfortable stay, and sunrise visit to the Taj Mahal.', '/uploads/packages/agra-budget-2.jpg', '2025-01-01', '2025-12-31', 10, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(3, 7, 3, 4, 'Royal Agra Experience - Medium', 'medium', 12500.00, 4, '{\"accommodation\": \"4 nights in Premium Hotel\", \"flights\": \"Round trip premium economy\", \"meals\": \"Breakfast and dinner included\", \"transfers\": \"Private airport transfers\", \"sightseeing\": \"Taj Mahal, Agra Fort, Fatehpur Sikri with guide\", \"extras\": \"Sunset view of Taj Mahal from Mehtab Bagh\"}', 'Experience the royal heritage of Agra in comfort. Visit all major monuments with expert guides and enjoy premium accommodation.', '/uploads/packages/agra-medium-1.jpg', '2025-01-01', '2025-12-31', 6, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(4, 7, 3, 5, 'Delhi-Agra Heritage Tour', 'medium', 5800.00, 3, '{\"accommodation\": \"3 nights in Premium Hotel\", \"flights\": \"Round trip premium economy from Delhi\", \"meals\": \"Breakfast and one dinner\", \"transfers\": \"Private transfers\", \"sightseeing\": \"Taj Mahal, Agra Fort, Itimad-ud-Daulah\", \"extras\": \"Traditional Mughlai dinner\"}', 'Perfect heritage tour from Delhi. Includes comfortable stay and guided tours of Agra\'s finest monuments.', '/uploads/packages/agra-medium-2.jpg', '2025-01-01', '2025-12-31', 8, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(5, 7, 4, 6, 'Luxury Agra Escape', 'high', 28500.00, 5, '{\"accommodation\": \"5 nights in Luxury Boutique Hotel\", \"flights\": \"Round trip business class\", \"meals\": \"All meals included (breakfast, lunch, dinner)\", \"transfers\": \"Luxury private transfers with chauffeur\", \"sightseeing\": \"Private guided tours of all monuments\", \"extras\": \"Sunrise and sunset views of Taj Mahal, Spa treatment, Private dinner with view of Taj Mahal\"}', 'Ultimate luxury experience in Agra. Stay in a premium boutique hotel with stunning views, enjoy business class flights, and experience the Taj Mahal like never before.', '/uploads/packages/agra-high-1.jpg', '2025-01-01', '2025-12-31', 4, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(6, 7, 4, 7, 'Executive Agra Retreat', 'high', 18900.00, 4, '{\"accommodation\": \"4 nights in Luxury Boutique Hotel\", \"flights\": \"Round trip business class from Delhi\", \"meals\": \"Breakfast and gourmet dinners\", \"transfers\": \"Luxury private transfers\", \"sightseeing\": \"Private guided heritage tours\", \"extras\": \"Photography session at Taj Mahal, Cooking class, Elephant conservation visit\"}', 'Executive retreat combining luxury accommodation with authentic cultural experiences. Perfect for discerning travelers.', '/uploads/packages/agra-high-2.jpg', '2025-01-01', '2025-12-31', 4, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(7, 7, 4, 6, 'Winter Wonder - Taj Mahal Special', 'high', 32900.00, 5, '{\"accommodation\": \"5 nights in Luxury Hotel with winter upgrades\", \"flights\": \"Round trip business class\", \"meals\": \"All meals including special winter menu\", \"transfers\": \"Luxury private transfers\", \"sightseeing\": \"Private tours with expert historian\", \"extras\": \"Bonfire evening, Winter festival tickets, Spa sessions\"}', 'Special winter package! Experience Agra in the pleasant winter weather with luxury upgrades and special experiences.', '/uploads/packages/agra-winter-special.jpg', '2025-11-01', '2026-02-28', 4, '2026-03-11 18:18:07', '2026-03-11 18:18:07'),
(8, 7, 3, NULL, 'Agra Flexi Package - Hotel Only with Flight Option', 'medium', 6500.00, 3, '{\"accommodation\": \"3 nights in Premium Hotel\", \"meals\": \"Breakfast included\", \"transfers\": \"Airport transfers not included\", \"sightseeing\": \"Flexible sightseeing options\", \"note\": \"Flights can be added separately\"}', 'Flexible package where you choose your own flights. Includes premium accommodation and daily breakfast.', '/uploads/packages/agra-flexi.jpg', '2025-01-01', '2025-12-31', 10, '2026-03-11 18:18:07', '2026-03-11 18:18:07');

-- --------------------------------------------------------

--
-- Table structure for table `upcoming_trips`
--

CREATE TABLE `upcoming_trips` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) DEFAULT NULL,
  `destination_name` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `travelers` int(11) DEFAULT 1,
  `budget` decimal(10,2) DEFAULT NULL,
  `status` enum('upcoming','ongoing','completed','cancelled') DEFAULT 'upcoming',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `upcoming_trips`
--

INSERT INTO `upcoming_trips` (`id`, `user_id`, `destination_id`, `destination_name`, `start_date`, `end_date`, `travelers`, `budget`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 5, 7, 'Agra', '2026-04-06', '0000-00-00', 2, 0.00, 'upcoming', '', '2026-04-05 10:10:07', '2026-04-05 10:10:07'),
(2, 5, 3, 'Kyoto', '2026-04-07', '0000-00-00', 2, 0.00, 'upcoming', '', '2026-04-07 16:05:17', '2026-04-07 16:05:17');

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
(1, 'user1234', 'user20@gmail.com', '$2y$10$5CZXHrFKjL43euzkhq99kuSOl9UQMGJ4/6taojQDzkwhaz3WHPJMW', 'manual', NULL, NULL, '2025-08-03 06:30:14', 'normal'),
(2, 'user1234', 'ranajitbarik200@gmail.com', '$2y$10$/zN1gsfaAdwsnAlW84ju/.1oYjiM4ER.xuG3rfKihcCctX141QGUK', 'manual', NULL, NULL, '2025-08-03 16:56:19', 'normal'),
(3, 'Arnab', 'arn@gmail.com', '$2y$10$mAIq.ITLVhXQXaV4lBoXuOp8CmMe8CX9EGuk1ZBUxaDGj88KSDENS', 'manual', NULL, NULL, '2025-08-06 17:51:50', 'normal'),
(4, 'ad', 'ad@gmail.com', '', 'manual', NULL, NULL, '2025-08-08 17:56:19', 'normal'),
(5, 'Arnab', 'adnew@gmail.com', '$2y$10$gDu27NoQjOaCxupLzjiDxOrMvh2TBtsEsd9EZQqj5daXExzOyt0Du', 'manual', NULL, 'uploads/profile_5_1755271907.jpg', '2025-08-09 16:36:51', 'normal');

-- --------------------------------------------------------

--
-- Table structure for table `user_history`
--

CREATE TABLE `user_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('search','destination_view','favorite','login','booking','trip_plan') NOT NULL,
  `activity_details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_history`
--

INSERT INTO `user_history` (`id`, `user_id`, `activity_type`, `activity_details`, `created_at`) VALUES
(2, 5, '', '{\"id\":5,\"name\":\"Santorini\",\"type\":\"beach\",\"location\":\"Santorini, Greece\"}', '2025-08-12 12:20:42'),
(4, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-08-12 12:21:32'),
(5, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-08-16 04:05:19'),
(6, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-08-16 04:15:28'),
(7, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-08-16 04:16:07'),
(8, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-08-21 15:49:44'),
(12, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 06:45:19'),
(13, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 06:48:53'),
(14, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 06:54:52'),
(16, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 07:11:33'),
(17, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-07 07:30:34'),
(18, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-07 07:32:32'),
(19, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-07 07:47:48'),
(20, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-07 07:59:41'),
(21, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-07 07:59:43'),
(22, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 08:04:59'),
(23, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 08:16:48'),
(24, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 08:20:24'),
(25, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 08:23:51'),
(26, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-07 08:26:59'),
(27, 5, '', '{\"id\":3,\"name\":\"Kyoto\",\"type\":\"village\",\"location\":\"Kyoto, Japan\"}', '2025-09-07 14:39:38'),
(28, 5, '', '{\"id\":4,\"name\":\"New York City\",\"type\":\"city\",\"location\":\"New York, USA\"}', '2025-09-09 18:35:41'),
(29, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 15:31:25'),
(30, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 15:39:14'),
(31, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 15:42:38'),
(32, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 15:45:25'),
(33, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 16:19:21'),
(34, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 16:19:32'),
(35, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-13 16:21:17'),
(36, 5, '', '{\"id\":7,\"name\":\"Agra\",\"type\":\"city\",\"location\":\"Uttar Pradesh, India\"}', '2025-09-14 06:41:38'),
(37, 5, '', '{\"id\":7,\"name\":\"Agra\",\"type\":\"city\",\"location\":\"Uttar Pradesh, India\"}', '2025-09-14 08:45:16'),
(38, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-09-17 09:11:09'),
(39, 5, '', '{\"id\":7,\"name\":\"Agra\",\"type\":\"city\",\"location\":\"Uttar Pradesh, India\"}', '2025-09-30 07:20:01'),
(40, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-10-24 02:45:15'),
(41, 5, '', '{\"id\":7,\"name\":\"Agra\",\"type\":\"city\",\"location\":\"Uttar Pradesh, India\"}', '2025-10-27 05:58:24'),
(42, 5, '', '{\"id\":1,\"name\":\"Bali\",\"type\":\"historical\",\"location\":\"Bali, Indonesia\"}', '2025-11-02 03:11:40'),
(43, 5, '', '{\"id\":2,\"name\":\"Paris\",\"type\":\"city\",\"location\":\"Paris, France\"}', '2025-11-02 06:54:28'),
(44, 5, '', '{\"id\":7,\"name\":\"Agra\",\"type\":\"city\",\"location\":\"Uttar Pradesh, India\"}', '2025-11-02 06:54:48'),
(45, 5, '', '{\"id\":7,\"name\":\"Agra\",\"type\":\"city\",\"location\":\"Uttar Pradesh, India\"}', '2025-11-02 07:51:12'),
(46, 5, '', '{\"id\":1,\"name\":\"Bali\",\"type\":\"historical\",\"location\":\"Bali, Indonesia\"}', '2026-02-21 17:22:43'),
(50, 5, 'favorite', '7', '2026-02-22 04:32:10'),
(51, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"start_date\":\"2026-02-28\",\"end_date\":\"2026-03-03\",\"travelers\":5,\"nights\":3,\"hotel_budget\":2000,\"flight_budget\":25000,\"user_name\":\"Arnab\"}', '2026-02-25 14:03:06'),
(52, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"start_date\":\"2026-02-28\",\"end_date\":\"2026-03-01\",\"travelers\":1,\"nights\":1,\"hotel_budget\":2000,\"flight_budget\":25000,\"user_name\":\"Arnab\"}', '2026-02-25 14:06:02'),
(53, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"start_date\":\"2026-02-27\",\"end_date\":\"2026-02-28\",\"travelers\":2,\"nights\":1,\"hotel_budget\":2000,\"flight_budget\":25000,\"user_name\":\"Arnab\"}', '2026-02-25 14:58:07'),
(54, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-02-27\",\"end_date\":\"2026-03-02\",\"travelers\":2,\"nights\":3,\"hotel_budget\":1000,\"flight_budget\":10500,\"user_name\":\"Arnab\"}', '2026-02-25 17:39:07'),
(55, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Mumbai\",\"start_date\":\"2026-02-28\",\"end_date\":\"2026-03-02\",\"travelers\":2,\"nights\":2,\"hotel_budget\":2700,\"flight_budget\":10000,\"user_name\":\"Arnab\"}', '2026-02-25 17:41:47'),
(56, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Mumbai\",\"start_date\":\"2026-02-27\",\"end_date\":\"2026-03-02\",\"travelers\":4,\"nights\":3,\"hotel_budget\":2600,\"flight_budget\":12500,\"user_name\":\"Arnab\"}', '2026-02-25 17:46:22'),
(57, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Mumbai\",\"start_date\":\"2026-03-01\",\"end_date\":\"2026-03-09\",\"travelers\":5,\"nights\":8,\"hotel_budget\":5000,\"flight_budget\":25000,\"user_name\":\"Arnab\"}', '2026-02-28 12:55:53'),
(58, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Mumbai\",\"start_date\":\"2026-03-01\",\"end_date\":\"2026-03-09\",\"travelers\":5,\"nights\":8,\"hotel_budget\":5000,\"flight_budget\":25000,\"user_name\":\"Arnab\"}', '2026-02-28 12:56:01'),
(59, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Mumbai\",\"start_date\":\"2026-03-01\",\"end_date\":\"2026-03-09\",\"travelers\":5,\"nights\":8,\"hotel_budget\":5000,\"flight_budget\":25000,\"user_name\":\"Arnab\"}', '2026-02-28 12:56:25'),
(60, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-03-12\",\"end_date\":\"2026-03-19\",\"travelers\":5,\"nights\":7,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[3],\"user_name\":\"Arnab\"}', '2026-03-06 16:11:44'),
(61, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-10-08\",\"end_date\":\"2026-10-15\",\"travelers\":5,\"nights\":7,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[10],\"user_name\":\"Arnab\"}', '2026-03-06 16:13:01'),
(62, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-03-13\",\"end_date\":\"2026-03-19\",\"travelers\":5,\"nights\":6,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[3],\"user_name\":\"Arnab\"}', '2026-03-06 16:13:37'),
(63, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-11-13\",\"end_date\":\"2026-11-19\",\"travelers\":5,\"nights\":6,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[11],\"user_name\":\"Arnab\"}', '2026-03-06 16:15:02'),
(64, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-09-13\",\"end_date\":\"2026-09-19\",\"travelers\":5,\"nights\":6,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[9],\"user_name\":\"Arnab\"}', '2026-03-06 16:15:45'),
(65, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-03-06\",\"end_date\":\"2026-03-13\",\"travelers\":2,\"nights\":7,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[3],\"user_name\":\"Arnab\"}', '2026-03-06 17:33:53'),
(66, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-09-06\",\"end_date\":\"2026-09-13\",\"travelers\":2,\"nights\":7,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[9],\"user_name\":\"Arnab\"}', '2026-03-06 17:34:45'),
(67, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-03-12\",\"end_date\":\"2026-03-19\",\"travelers\":2,\"nights\":7,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[3],\"user_name\":\"Arnab\"}', '2026-03-08 04:17:20'),
(68, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-03-12\",\"end_date\":\"2026-03-19\",\"travelers\":2,\"nights\":7,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[3],\"user_name\":\"Arnab\"}', '2026-03-08 04:20:54'),
(69, 5, '', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"departure_city\":\"Bangalore\",\"start_date\":\"2026-03-10\",\"end_date\":\"2026-03-13\",\"travelers\":2,\"nights\":3,\"hotel_budget\":5000,\"flight_budget\":25000,\"months_covered\":[3],\"user_name\":\"Arnab\"}', '2026-03-10 15:47:57'),
(70, 5, 'favorite', '2', '2026-04-05 08:11:39'),
(71, 5, 'trip_plan', '{\"destination_id\":7,\"destination_name\":\"Agra\",\"start_date\":\"2026-04-06\"}', '2026-04-05 10:10:07'),
(72, 5, 'trip_plan', '{\"destination_id\":3,\"destination_name\":\"Kyoto\",\"start_date\":\"2026-04-07\"}', '2026-04-07 16:05:17'),
(73, 5, 'search', 'agra', '2026-04-07 16:06:36');

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
(1, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 19:20:12'),
(2, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 19:20:21'),
(3, 108, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-30 19:20:29'),
(4, 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-31 18:42:35'),
(5, 109, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-31 19:29:10'),
(11, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 17:53:26'),
(12, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 07:44:19'),
(13, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 08:25:15'),
(14, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-17 09:14:10'),
(15, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '2026-02-11 08:32:46');

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
-- Table structure for table `user_search_history`
--

CREATE TABLE `user_search_history` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `search_query` varchar(255) NOT NULL,
  `search_type` varchar(50) DEFAULT NULL,
  `results_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_search_history`
--

INSERT INTO `user_search_history` (`id`, `user_id`, `search_query`, `search_type`, `results_count`, `created_at`) VALUES
(1, 5, 'agra', 'destination', 0, '2026-04-07 16:06:36');

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
(1, 'Home Page', 'home', 1500, 300, '2025-11-17', '2025-11-17 07:44:20'),
(2, 'Destination List', 'destination', 800, 450, '2025-11-17', '2025-11-17 07:44:20'),
(3, 'Blog Page', 'blog', 600, 200, '2025-11-17', '2025-11-17 07:44:20'),
(4, 'About Us', 'about', 300, 50, '2025-11-17', '2025-11-17 07:44:20'),
(5, 'Contact', 'contact', 400, 100, '2025-11-17', '2025-11-17 07:44:20');

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
-- Indexes for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_blog_post_id` (`blog_post_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `calendar_events`
--
ALTER TABLE `calendar_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_budget` (`budget`),
  ADD KEY `idx_season` (`season`);

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
  ADD KEY `idx_destination_type` (`destination_id`,`flight_type`),
  ADD KEY `idx_price` (`price_per_person`);

--
-- Indexes for table `hotels`
--
ALTER TABLE `hotels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destination_type` (`destination_id`,`hotel_type`),
  ADD KEY `idx_price` (`price_per_night`);

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
-- Indexes for table `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `destination_id` (`destination_id`);

--
-- Indexes for table `price_history`
--
ALTER TABLE `price_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reference_type` (`travel_type`,`reference_id`,`recorded_at`);

--
-- Indexes for table `price_trends`
--
ALTER TABLE `price_trends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dest_type_date` (`destination_id`,`travel_type`,`date`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_destination_id` (`destination_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `search_history`
--
ALTER TABLE `search_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `seasonal_pricing`
--
ALTER TABLE `seasonal_pricing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item` (`item_type`,`item_id`),
  ADD KEY `idx_destination` (`destination_id`),
  ADD KEY `idx_season` (`start_month`,`end_month`);

--
-- Indexes for table `travel_packages`
--
ALTER TABLE `travel_packages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `hotel_id` (`hotel_id`),
  ADD KEY `flight_id` (`flight_id`),
  ADD KEY `idx_destination` (`destination_id`),
  ADD KEY `idx_destination_type` (`destination_id`,`package_type`),
  ADD KEY `idx_price` (`total_price_per_person`),
  ADD KEY `idx_availability` (`available_from`,`available_to`);

--
-- Indexes for table `upcoming_trips`
--
ALTER TABLE `upcoming_trips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_start_date` (`start_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `fk_upcoming_trips_destination` (`destination_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_history`
--
ALTER TABLE `user_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`activity_type`,`created_at`);

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
-- Indexes for table `user_search_history`
--
ALTER TABLE `user_search_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_search_query` (`search_query`);

--
-- Indexes for table `views`
--
ALTER TABLE `views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_destination` (`destination_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- Indexes for table `website_analytics`
--
ALTER TABLE `website_analytics`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_suggestions`
--
ALTER TABLE `activity_suggestions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `blog_comments`
--
ALTER TABLE `blog_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `blog_posts`
--
ALTER TABLE `blog_posts`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `errors`
--
ALTER TABLE `errors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `favorites`
--
ALTER TABLE `favorites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `flights`
--
ALTER TABLE `flights`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `hotels`
--
ALTER TABLE `hotels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `itineraries`
--
ALTER TABLE `itineraries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `itinerary_days`
--
ALTER TABLE `itinerary_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_alerts`
--
ALTER TABLE `price_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `price_history`
--
ALTER TABLE `price_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `price_trends`
--
ALTER TABLE `price_trends`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `search_history`
--
ALTER TABLE `search_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `seasonal_pricing`
--
ALTER TABLE `seasonal_pricing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `travel_packages`
--
ALTER TABLE `travel_packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `upcoming_trips`
--
ALTER TABLE `upcoming_trips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `user_history`
--
ALTER TABLE `user_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;

--
-- AUTO_INCREMENT for table `user_ips`
--
ALTER TABLE `user_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `user_levels`
--
ALTER TABLE `user_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_search_history`
--
ALTER TABLE `user_search_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `views`
--
ALTER TABLE `views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `website_analytics`
--
ALTER TABLE `website_analytics`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_suggestions`
--
ALTER TABLE `activity_suggestions`
  ADD CONSTRAINT `activity_suggestions_ibfk_1` FOREIGN KEY (`itinerary_day_id`) REFERENCES `itinerary_days` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_comments`
--
ALTER TABLE `blog_comments`
  ADD CONSTRAINT `blog_comments_ibfk_1` FOREIGN KEY (`blog_post_id`) REFERENCES `blog_posts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `blog_comments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `blog_posts`
--
ALTER TABLE `blog_posts`
  ADD CONSTRAINT `blog_posts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `errors`
--
ALTER TABLE `errors`
  ADD CONSTRAINT `errors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `favorites`
--
ALTER TABLE `favorites`
  ADD CONSTRAINT `fk_favorites_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `flights`
--
ALTER TABLE `flights`
  ADD CONSTRAINT `flights_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `hotels`
--
ALTER TABLE `hotels`
  ADD CONSTRAINT `hotels_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itineraries`
--
ALTER TABLE `itineraries`
  ADD CONSTRAINT `itineraries_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `itineraries_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `itinerary_days`
--
ALTER TABLE `itinerary_days`
  ADD CONSTRAINT `itinerary_days_ibfk_1` FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `price_alerts`
--
ALTER TABLE `price_alerts`
  ADD CONSTRAINT `price_alerts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `price_alerts_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `price_trends`
--
ALTER TABLE `price_trends`
  ADD CONSTRAINT `price_trends_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `search_history`
--
ALTER TABLE `search_history`
  ADD CONSTRAINT `search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `seasonal_pricing`
--
ALTER TABLE `seasonal_pricing`
  ADD CONSTRAINT `seasonal_pricing_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `travel_packages`
--
ALTER TABLE `travel_packages`
  ADD CONSTRAINT `travel_packages_ibfk_1` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `travel_packages_ibfk_2` FOREIGN KEY (`hotel_id`) REFERENCES `hotels` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `travel_packages_ibfk_3` FOREIGN KEY (`flight_id`) REFERENCES `flights` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `upcoming_trips`
--
ALTER TABLE `upcoming_trips`
  ADD CONSTRAINT `fk_upcoming_trips_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_upcoming_trips_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_history`
--
ALTER TABLE `user_history`
  ADD CONSTRAINT `user_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_levels`
--
ALTER TABLE `user_levels`
  ADD CONSTRAINT `user_levels_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_search_history`
--
ALTER TABLE `user_search_history`
  ADD CONSTRAINT `user_search_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `views`
--
ALTER TABLE `views`
  ADD CONSTRAINT `fk_views_destination` FOREIGN KEY (`destination_id`) REFERENCES `destinations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_views_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
