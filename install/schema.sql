-- S2S Postback Testing Tool Database Schema

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Settings table
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `postback_base_url` text NOT NULL,
  `default_goal` varchar(64) DEFAULT NULL,
  `default_amount` decimal(10,2) DEFAULT NULL,
  `extra_params` text DEFAULT NULL COMMENT 'JSON or query string format',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Offers table
CREATE TABLE IF NOT EXISTS `offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `partner_url_template` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clicks table
CREATE TABLE IF NOT EXISTS `clicks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `transaction_id` varchar(128) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `ua` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_click` (`offer_id`, `transaction_id`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_ip` (`ip`),
  CONSTRAINT `fk_clicks_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversions table
CREATE TABLE IF NOT EXISTS `conversions` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) NOT NULL,
  `transaction_id` varchar(128) NOT NULL,
  `goal` varchar(64) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_conversion` (`offer_id`, `transaction_id`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_conversions_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Postbacks table
CREATE TABLE IF NOT EXISTS `postbacks` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `offer_id` int(11) DEFAULT NULL,
  `transaction_id` varchar(128) DEFAULT NULL,
  `url` text NOT NULL,
  `http_code` int(11) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `error` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_offer_id` (`offer_id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_http_code` (`http_code`),
  CONSTRAINT `fk_postbacks_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
COMMIT;