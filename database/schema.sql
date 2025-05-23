-- Set the storage engine to InnoDB for foreign key support
SET default_storage_engine=InnoDB;

-- -----------------------------------------------------
-- Table `players`
-- Stores information about chess players
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `players` (
  `player_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `fide_id` INT NULLABLE UNIQUE, -- Standard FIDE ID
  `federation_code` VARCHAR(10) NULLABLE, -- e.g., 'USA', 'RUS'
  `rating_standard` INT NULLABLE,
  `rating_rapid` INT NULLABLE,
  `rating_blitz` INT NULLABLE,
  `title` VARCHAR(10) NULLABLE, -- e.g., 'GM', 'IM', 'FM'
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Stores information about chess players';

-- -----------------------------------------------------
-- Table `users`
-- Stores information about application users
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `preferences` TEXT NULLABLE, -- For storing user-specific settings, perhaps as JSON
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB COMMENT='Stores information about application users';

-- -----------------------------------------------------
-- Table `games`
-- Stores information about chess games
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `games` (
  `game_id` INT AUTO_INCREMENT PRIMARY KEY,
  `white_player_id` INT NULLABLE,
  `black_player_id` INT NULLABLE,
  `white_rating` INT NULLABLE,
  `black_rating` INT NULLABLE,
  `result` VARCHAR(10) NOT NULL, -- e.g., '1-0', '0-1', '1/2-1/2', '*'
  `pgn_content` TEXT NOT NULL,
  `eco_code` VARCHAR(5) NULLABLE, -- e.g., 'C42'
  `event_name` VARCHAR(255) NULLABLE,
  `site_name` VARCHAR(255) NULLABLE,
  `game_date` DATE NULLABLE,
  `round_info` VARCHAR(50) NULLABLE,
  `user_id` INT NULLABLE, -- For games entered by specific users
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  FOREIGN KEY (`white_player_id`) REFERENCES `players`(`player_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`black_player_id`) REFERENCES `players`(`player_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,

  INDEX `idx_eco_code` (`eco_code`),
  INDEX `idx_event_name` (`event_name`),
  INDEX `idx_game_date` (`game_date`),
  INDEX `idx_white_player_id` (`white_player_id`),
  INDEX `idx_black_player_id` (`black_player_id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB COMMENT='Stores information about chess games';
