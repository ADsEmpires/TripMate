-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 14, 2025 at 02:02 PM
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
-- Table structure for table `destinations`
--

CREATE TABLE `destinations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `type` enum('beach','mountain','city','village','historical','religious') NOT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) NOT NULL,
  `latitude` varchar(255) DEFAULT NULL,
  `longitude` varchar(255) DEFAULT NULL,
  `budget` decimal(10,2) DEFAULT NULL COMMENT 'Average cost per person per day',
  `best_season` varchar(50) DEFAULT NULL,
  `image_urls` text DEFAULT NULL COMMENT 'JSON array of image URLs',
  `map_link` varchar(255) DEFAULT NULL,
  `attractions` text DEFAULT NULL COMMENT 'JSON array of attractions',
  `hotels` text DEFAULT NULL COMMENT 'JSON array of nearby hotels',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `season` varchar(50) DEFAULT NULL,
  `people` text DEFAULT NULL,
  `cuisines` text DEFAULT NULL,
  `cuisine_images` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `language` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `destinations`
--

INSERT INTO `destinations` (`id`, `name`, `type`, `description`, `location`, `latitude`, `longitude`, `budget`, `best_season`, `image_urls`, `map_link`, `attractions`, `hotels`, `created_at`, `updated_at`, `season`, `people`, `cuisines`, `cuisine_images`, `profile_pic`, `language`) VALUES
(1, 'Paris', 'city', 'The capital of France, known for its art, fashion...', 'Paris, France', '48.8566000', '2.3522000', 5000.00, 'April to June', '["uploads/68b826e2e0303_paris.jpg"]', 'https://www.google.com/maps/place/Paris', '["Eiffel Tower", "Louvre Museum", "Notre Dame Cathedral"]', '["Hotel de Crillon", "Le Meurice"]', '2025-09-05 12:17:34', '2025-09-13 21:55:25', 'summer', '["MULL"]', NULL, NULL, 'MULL', '["MULL"]'),
(2, 'Agra', 'historical', 'Uttar Pradesh, India', 'Agra, Uttar Pradesh, India', '0.0000000', '0.0000000', 800.00, NULL, '["uploads/68a6211075_agra.jpg"]', 'https://www.google.com/maps/place/Agra+Fort', '["Taj Mahal", "Agra Fort", "Fatehpur Sikri"]', '["MULL", "MULL", "MULL"]', '2025-09-05 20:18:33', '2025-09-14 12:11:00', 'winter,summer,spring,autumn', '["0", "MULL", "MULL"]', '["Biryani", "Butter Chicken", "Panner Tikka"]', '["uploads/68c7066790b59_biryani.jpg"]', 'MULL', '["MULL", "MULL", "MULL"]'),
(3, 'Bali', 'beach', 'A tropical paradise known for its stunning beaches...', 'Bali, Indonesia', '8.4095000', '115.1880000', 600.00, 'April to October', '["uploads/68b562fb321_bali.jpg"]', 'https://www.google.com/maps/place/Bali+Bali', '["Uluwatu Temple", "Kuta Beach", "Tegallalang Rice Terraces"]', '["Four Seasons Bali at Jimbaran Bay", "The Legian Bali"]', '2025-09-05 21:20:32', '2025-09-14 21:09:02', 'spring', '["0", "MULL", "MULL"]', '["Tepung", "Nasi Goreng"]', '["cuisine_1.jpg", "cuisine_2.jpg"]', 'MULL', '["Lang 1", "Lang 2"]'),
(4, 'Darjeeling Tea Gardens', 'mountain', 'temple king ', 'Darjeeling, West Bengal', '26.7119000', '15.7001000', 5000.00, NULL, '["uploads/68aeeb75c9390_688fbd477e712_Darjeeling Tea Gardens2.jpg"]', 'https://maps.app.goo.gl/MpEhSRdiWNFcKYSk9', NULL, NULL, '2025-08-27 11:26:45', '2025-08-27 11:26:45', 'winter', '["MULL"]', NULL, NULL, 'MULL', '["MULL"]'),
(5, 'taj mohol', 'historical', 'white temple', 'KOLKATA', NULL, NULL, 522.00, NULL, '["uploads/68b092fe62b27_profile_5_1755271907.jpg"]', 'https://maps.app.goo.gl/HCq1ACmUB2LXMLbb9', NULL, NULL, '2025-08-28 17:33:50', '2025-08-28 17:33:50', 'summer', '["MULL"]', NULL, NULL, 'MULL', '["MULL"]'),
(6, 'gdfgd', 'mountain', 'fdgs', 'hdfhg', NULL, NULL, 35435.00, NULL, '["uploads/68b0a1601a95a_banner_upscaled.jpg"]', 'https://maps.app.goo.gl/MpEhSRdiWNFcKYSk9', NULL, NULL, '2025-08-28 18:35:12', '2025-08-28 18:35:12', 'summer', '["MULL"]', NULL, NULL, 'MULL', '["MULL"]'),
(7, 'Kyoto', 'historical', 'Famous for its classical Buddhist temples, as well...', 'Kyoto, Japan', '35.0116000', '135.7680000', 120.00, 'March to May', '["uploads/68c553d21b2d3_kyoto.jpg"]', 'https://www.google.com/maps/place/Kyoto+Japan', '["Kinkakuji & Ginkakuji Temples", "Arashiyama Bamboo Grove"]', '["Hotel Granvia Kyoto"]', '2025-09-05 12:17:34', '2025-09-12 21:18:54', 'spring', '["0", "MULL", "MULL"]', '["MULL"]', '["MULL"]', 'MULL', '["MULL"]'),
(8, 'New York City', 'city', 'New York, NYC is famous for its skyl...', 'New York, NY', '40.7128000', '-74.0060000', 200.00, 'April to June', '["uploads/68b5d9299d63d_new_york_city.jpg"]', 'https://www.google.com/maps/place/New+York+City+New+York', '["Statue of Liberty", "Central Park", "Times Square"]', '["The Standard", "High Line", "The Ritz Carlton New York"]', '2025-09-05 12:17:34', '2025-09-13 21:18:51', 'winter', '["0", "MULL", "MULL"]', '["MULL"]', '["MULL"]', 'MULL', '["MULL"]'),
(9, 'Santorini', 'city', 'A beautiful island in the Aegean Sea, known for its...', 'Santorini, Greece', '36.3931000', '25.4615000', 180.00, 'May to October', '["uploads/68c5525b347f_santorini.jpg"]', 'https://www.google.com/maps/place/Santorini+Greece', '["Oia", "Fira", "Red Beach"]', '["Canaves Oia Hotel", "Grace Hotel Santorini"]', '2025-09-05 12:17:34', '2025-09-13 21:18:53', 'autumn', '["0", "MULL", "MULL"]', '["MULL"]', '["MULL"]', 'MULL', '["MULL"]'),
(10, 'Machu Picchu', 'historical', 'An ancient Incan city set high in the Andes Mount...', 'Machu Picchu, Peru', '-13.1631000', '-72.5450000', 250.00, 'April to October', '["uploads/68c541b12b9d_machu_picchu.jpg"]', 'https://www.google.com/maps/place/Machu+Picchu+Machu+Picchu+Peru', '["Inca Trail", "Sacred Valley", "Huayna Picchu"]', '["Salkantay Machu Picchu Pueblo", "Machu Picchu Sumaq Hotel"]', '2025-09-05 12:17:34', '2025-09-14 21:18:30', 'spring,autumn', '["0", "MULL", "MULL"]', '["Lomo Saltado", "Ceviche"]', '["MULL"]', 'MULL', '["Lang 1", "Lang 2"]');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `destinations`
--
ALTER TABLE `destinations`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `destinations`
--
ALTER TABLE `destinations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;