-- Create itineraries table
CREATE TABLE `itineraries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `travel_style` enum('adventure','relaxation','cultural','luxury','budget') DEFAULT 'adventure',
  `preferences` json DEFAULT NULL COMMENT 'User preferences: activities, cuisine, pace',
  `generated_by_ai` tinyint(1) DEFAULT 1,
  `status` enum('draft','published','completed') DEFAULT 'draft',
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create itinerary_days table (day-by-day breakdown)
CREATE TABLE `itinerary_days` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itinerary_id` int(11) NOT NULL,
  `day_number` int(11) NOT NULL,
  `date` date NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `activities` json DEFAULT NULL COMMENT 'Array of activities for the day',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`itinerary_id`) REFERENCES `itineraries`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create activity_suggestions table
CREATE TABLE `activity_suggestions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `itinerary_day_id` int(11) NOT NULL,
  `activity_name` varchar(255) NOT NULL,
  `activity_type` enum('sightseeing','adventure','food','wellness','cultural','shopping') DEFAULT 'sightseeing',
  `description` text DEFAULT NULL,
  `time_required` int(11) DEFAULT NULL COMMENT 'Time in minutes',
  `estimated_cost` decimal(10,2) DEFAULT NULL,
  `time_of_day` enum('morning','afternoon','evening','night') DEFAULT 'morning',
  `location` varchar(255) DEFAULT NULL,
  `coordinates` json DEFAULT NULL COMMENT 'Latitude and longitude',
  `rating` decimal(3,2) DEFAULT NULL,
  `priority` int(11) DEFAULT 0 COMMENT 'Higher priority = more recommended',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`itinerary_day_id`) REFERENCES `itinerary_days`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;