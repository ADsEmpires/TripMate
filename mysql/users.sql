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
(110, 'hello', 'hello@gmail.com', '$2y$10$aKHAGqZktW4nwiIyJOUIF.vzvaJeqGF3x55A9xfJgWjf.Ehh0ExlO', 'manual', NULL, NULL, '2025-09-02 17:34:08', 'normal');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
