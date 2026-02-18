-- Create reviews table (blockchain-verified)
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `destination_id` int(11) NOT NULL,
  `rating` int(11) NOT NULL COMMENT '1-5 stars',
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `images` json DEFAULT NULL COMMENT 'Review images/photos',
  `review_type` enum('accommodation','restaurant','attraction','general') DEFAULT 'general',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_hash` varchar(255) DEFAULT NULL COMMENT 'Blockchain hash',
  `blockchain_transaction_id` varchar(255) DEFAULT NULL,
  `helpful_count` int(11) DEFAULT 0,
  `unhelpful_count` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`destination_id`) REFERENCES `destinations`(`id`) ON DELETE CASCADE,
  INDEX `idx_destination_rating` (`destination_id`, `rating`),
  INDEX `idx_verified` (`is_verified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create reward tokens table
CREATE TABLE `reward_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token_amount` decimal(18,2) NOT NULL,
  `token_type` enum('review_bonus','referral','booking','contribution') DEFAULT 'review_bonus',
  `earned_from_id` int(11) DEFAULT NULL COMMENT 'Related transaction ID',
  `is_claimed` tinyint(1) DEFAULT 0,
  `claimed_at` timestamp DEFAULT NULL,
  `expires_at` timestamp DEFAULT NULL COMMENT 'Token expiry date',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create token redemption table
CREATE TABLE `token_redemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `tokens_redeemed` decimal(18,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL COMMENT 'Actual discount value',
  `booking_id` int(11) DEFAULT NULL,
  `redemption_type` enum('booking_discount','product','upgrade') DEFAULT 'booking_discount',
  `redeemed_at` timestamp DEFAULT current_timestamp(),
  `PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create review verification log (blockchain)
CREATE TABLE `review_verification_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `review_id` int(11) NOT NULL,
  `verification_status` enum('pending','verified','rejected') DEFAULT 'pending',
  `blockchain_network` varchar(50) DEFAULT 'ethereum' COMMENT 'Which blockchain network',
  `contract_address` varchar(255) DEFAULT NULL,
  `smart_contract_hash` varchar(255) DEFAULT NULL,
  `verified_at` timestamp DEFAULT NULL,
  `verifier_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`review_id`) REFERENCES `reviews`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Create user reputation table
CREATE TABLE `user_reputation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `reputation_score` int(11) DEFAULT 0 COMMENT 'Points based on reviews',
  `total_reviews` int(11) DEFAULT 0,
  `verified_reviews` int(11) DEFAULT 0,
  `helpful_ratings` int(11) DEFAULT 0,
  `reliability_rating` decimal(3,2) DEFAULT NULL,
  `rank` enum('bronze','silver','gold','platinum','diamond') DEFAULT 'bronze',
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;