-- Create flights table
CREATE TABLE `flights` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destination_id` int(11) NOT NULL,
  `airline` varchar(100) NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `departure_date` date NOT NULL,
  `arrival_date` date NOT NULL,
  `duration_hours` int(11) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE,
  INDEX `idx_destination_date` (`destination_id`, `departure_date`),
  INDEX `idx_price_updated` (`price`, `updated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create hotel prices table
CREATE TABLE `hotel_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destination_id` int(11) NOT NULL,
  `hotel_name` varchar(255) NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `price_per_night` decimal(10,2) NOT NULL,
  `currency` varchar(3) DEFAULT 'USD',
  `rating` decimal(3,2) DEFAULT NULL,
  `availability` int(11) DEFAULT NULL COMMENT 'Number of rooms available',
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE,
  INDEX `idx_destination_date` (`destination_id`, `check_in_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create price alerts table
CREATE TABLE `price_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `alert_type` enum('flight','hotel','both') DEFAULT 'both',
  `destination_id` int(11) DEFAULT NULL,
  `travel_dates_from` date DEFAULT NULL,
  `travel_dates_to` date DEFAULT NULL,
  `max_price` decimal(10,2) DEFAULT NULL COMMENT 'Alert when price drops below this',
  `is_active` tinyint(1) DEFAULT 1,
  `alert_frequency` enum('realtime','daily','weekly') DEFAULT 'daily',
  `notification_method` enum('email','in_app','sms') DEFAULT 'email',
  `price_history` json DEFAULT NULL COMMENT 'Historical price data for analysis',
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create price trend analysis table
CREATE TABLE `price_trends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `destination_id` int(11) NOT NULL,
  `travel_type` enum('flight','hotel') NOT NULL,
  `date` date NOT NULL,
  `average_price` decimal(10,2) NOT NULL,
  `price_change_percent` decimal(5,2) DEFAULT NULL,
  `trend_direction` enum('up','down','stable') DEFAULT 'stable',
  `best_booking_window_days` int(11) DEFAULT NULL COMMENT 'Optimal days before travel',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_dest_type_date` (`destination_id`, `travel_type`, `date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create price history table
CREATE TABLE `price_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `travel_type` enum('flight','hotel') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `recorded_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  INDEX `idx_reference_type` (`travel_type`, `reference_id`, `recorded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;