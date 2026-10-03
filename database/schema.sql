-- ApexSMM Database Schema (MySQL / MariaDB)
-- Production Ready Structure

SET FOREIGN_KEY_CHECKS = 0;

-- Drop pre-existing tables to guarantee schema compatibility
DROP TABLE IF EXISTS `rate_limits`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `ticket_messages`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `payment_gateways`;
DROP TABLE IF EXISTS `transactions`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `providers`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `settings`;

-- 1. Site Settings
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(60) NOT NULL UNIQUE,
    `email` VARCHAR(191) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `balance` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `spent` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `status` ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    `api_key` VARCHAR(64) NULL UNIQUE,
    `reset_token` VARCHAR(64) NULL,
    `reset_expires` DATETIME NULL,
    `failed_logins` INT NOT NULL DEFAULT 0,
    `lock_until` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(150) NOT NULL,
    `sort_order` INT NOT NULL DEFAULT 0,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cat_status` (`status`),
    INDEX `idx_cat_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Providers Table (External SMM APIs)
CREATE TABLE IF NOT EXISTS `providers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(120) NOT NULL,
    `api_url` VARCHAR(255) NOT NULL,
    `api_key` TEXT NOT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `balance` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
    `last_sync_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_providers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Services Table
CREATE TABLE IF NOT EXISTS `services` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `provider_id` INT NULL,
    `provider_service_id` VARCHAR(60) NULL,
    `name` VARCHAR(255) NOT NULL,
    `type` VARCHAR(50) NOT NULL DEFAULT 'Default',
    `rate` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
    `provider_rate` DECIMAL(12, 4) NOT NULL DEFAULT 0.0000,
    `min_quantity` INT NOT NULL DEFAULT 10,
    `max_quantity` INT NOT NULL DEFAULT 10000,
    `provider_min` INT NOT NULL DEFAULT 10,
    `provider_max` INT NOT NULL DEFAULT 10000,
    `refill` TINYINT(1) NOT NULL DEFAULT 0,
    `cancel` TINYINT(1) NOT NULL DEFAULT 0,
    `dripfeed` TINYINT(1) NOT NULL DEFAULT 0,
    `description` TEXT NULL,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `provider_status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_services_category` (`category_id`),
    INDEX `idx_services_provider` (`provider_id`),
    INDEX `idx_services_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `service_id` INT NOT NULL,
    `provider_id` INT NULL,
    `provider_order_id` VARCHAR(100) NULL,
    `link` TEXT NOT NULL,
    `quantity` INT NOT NULL,
    `charge` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `start_count` INT NOT NULL DEFAULT 0,
    `remains` INT NOT NULL DEFAULT 0,
    `status` ENUM('pending', 'processing', 'in_progress', 'completed', 'partial', 'cancelled', 'refunded', 'failed') NOT NULL DEFAULT 'pending',
    `refill_status` VARCHAR(50) NULL,
    `provider_response` TEXT NULL,
    `error_message` TEXT NULL,
    `runs` INT NOT NULL DEFAULT 0,
    `interval_minutes` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_orders_user` (`user_id`),
    INDEX `idx_orders_service` (`service_id`),
    INDEX `idx_orders_provider` (`provider_id`),
    INDEX `idx_orders_status` (`status`),
    INDEX `idx_orders_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Wallet & Transaction History
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `type` ENUM('deposit', 'order', 'refund', 'manual_credit', 'manual_debit') NOT NULL,
    `amount` DECIMAL(14, 4) NOT NULL,
    `balance_before` DECIMAL(14, 4) NOT NULL,
    `balance_after` DECIMAL(14, 4) NOT NULL,
    `reference_id` VARCHAR(100) NULL,
    `description` VARCHAR(255) NOT NULL,
    `admin_id` BIGINT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tx_user` (`user_id`),
    INDEX `idx_tx_type` (`type`),
    INDEX `idx_tx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Payment Gateways Table
CREATE TABLE IF NOT EXISTS `payment_gateways` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `min_amount` DECIMAL(10, 2) NOT NULL DEFAULT 5.00,
    `max_amount` DECIMAL(10, 2) NOT NULL DEFAULT 1000.00,
    `fee_percentage` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    `fee_fixed` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'inactive',
    `credentials` TEXT NULL,
    `instructions` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Payments / Invoices Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `gateway` VARCHAR(50) NOT NULL,
    `transaction_id` VARCHAR(150) NOT NULL UNIQUE,
    `amount` DECIMAL(14, 4) NOT NULL,
    `fee` DECIMAL(14, 4) NOT NULL DEFAULT 0.0000,
    `net_amount` DECIMAL(14, 4) NOT NULL,
    `currency` VARCHAR(10) NOT NULL DEFAULT 'USD',
    `status` ENUM('pending', 'completed', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
    `gateway_response` TEXT NULL,
    `proof_file` VARCHAR(255) NULL,
    `admin_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_payments_user` (`user_id`),
    INDEX `idx_payments_status` (`status`),
    INDEX `idx_payments_tx` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Support Tickets Table
CREATE TABLE IF NOT EXISTS `tickets` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `category` ENUM('order', 'payment', 'service', 'other', 'bug', 'request') NOT NULL DEFAULT 'order',
    `priority` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `status` ENUM('open', 'answered', 'customer_reply', 'closed') NOT NULL DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_tickets_user` (`user_id`),
    INDEX `idx_tickets_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Ticket Messages Table
CREATE TABLE IF NOT EXISTS `ticket_messages` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` BIGINT NOT NULL,
    `user_id` BIGINT NOT NULL,
    `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
    `message` TEXT NOT NULL,
    `attachment` VARCHAR(255) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tm_ticket` (`ticket_id`),
    INDEX `idx_tm_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NULL,
    `title` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('info', 'success', 'warning', 'danger', 'announcement') NOT NULL DEFAULT 'info',
    `link` VARCHAR(255) NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_notif_user` (`user_id`),
    INDEX `idx_notif_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` BIGINT NULL,
    `admin_id` BIGINT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NULL,
    `entity_id` VARCHAR(100) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(255) NULL,
    `details` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_admin` (`admin_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Rate Limiting Table
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `rate_key` VARCHAR(150) NOT NULL,
    `hits` INT NOT NULL DEFAULT 1,
    `reset_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_rate_key` (`rate_key`),
    INDEX `idx_rate_reset` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Default Settings (Configurable by admin via /admin/settings.php)
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'ApexSMM'),
('site_tagline', 'The Premier Social Media Marketing Growth Platform'),
('currency', 'USD'),
('currency_symbol', '$'),
('maintenance_mode', '0'),
('registration_enabled', '1'),
('contact_email', 'support@apexsmm.com'),
('timezone', 'UTC'),
('min_deposit', '5.00'),
('max_deposit', '1000.00'),
('announcement', 'Welcome to ApexSMM. All services operate with automated provider routing.'),
('terms_content', 'Standard terms of service apply to all social media marketing orders.'),
('faq_content', 'Frequently asked questions regarding order processing, refills, and support.');

-- Seed Default Payment Gateways (Inactive by default until admin enters real keys)
INSERT INTO `payment_gateways` (`id`, `code`, `name`, `min_amount`, `max_amount`, `fee_percentage`, `fee_fixed`, `status`, `credentials`, `instructions`) VALUES
(1, 'stripe', 'Stripe (Credit / Debit Cards)', 5.00, 2000.00, 2.90, 0.30, 'inactive', '{"publishable_key":"","secret_key":"","webhook_secret":""}', 'Pay securely using any major credit or debit card.'),
(2, 'paypal', 'PayPal Express Checkout', 10.00, 1500.00, 3.50, 0.49, 'inactive', '{"client_id":"","client_secret":"","mode":"sandbox"}', 'Instant wallet recharge through PayPal balance or cards.'),
(3, 'coinpayments', 'CoinPayments (Crypto: BTC, ETH, USDT)', 10.00, 5000.00, 1.00, 0.00, 'inactive', '{"merchant_id":"","public_key":"","private_key":"","ipn_secret":""}', 'Pay with Bitcoin, Ethereum, USDT TRC20, and major cryptocurrencies.'),
(4, 'bank_transfer', 'Manual / Bank Wire Transfer', 50.00, 10000.00, 0.00, 0.00, 'active', '{"bank_name":"JPMorgan Chase","account_name":"Apex Services LLC","account_number":"","routing_number":"","swift":""}', 'Direct bank transfer. After transfer, submit transaction ID and receipt screenshot for admin review.')
ON DUPLICATE KEY UPDATE name = VALUES(name), min_amount = VALUES(min_amount), max_amount = VALUES(max_amount);

-- Seed Categories
INSERT IGNORE INTO `categories` (`id`, `name`, `sort_order`, `status`) VALUES
(1, 'Instagram Followers & Likes', 1, 'active'),
(2, 'YouTube Views, Watchtime & Subs', 2, 'active'),
(3, 'TikTok Followers, Likes & Shares', 3, 'active'),
(4, 'Telegram Channel Members & Views', 4, 'active'),
(5, 'X (Twitter) Followers & Retweets', 5, 'active'),
(6, 'Facebook Page Likes & Reactions', 6, 'active'),
(7, 'Spotify Streams & Monthly Listeners', 7, 'active'),
(8, 'Discord Members & Server Boosts', 8, 'active');

-- Seed Default Services
INSERT IGNORE INTO `services` (`id`, `category_id`, `name`, `type`, `rate`, `provider_rate`, `min_quantity`, `max_quantity`, `refill`, `cancel`, `dripfeed`, `description`, `status`) VALUES
(1, 1, 'Instagram Followers [HQ Real Profiles] [30 Days Refill]', 'Default', 1.8500, 1.2000, 50, 50000, 1, 1, 1, 'Guaranteed high-quality non-drop followers with 30-day auto-refill.', 'active'),
(2, 1, 'Instagram Likes [Real Active Users] [Instant Start]', 'Default', 0.6500, 0.3500, 20, 100000, 0, 1, 1, 'Ultra-fast likes from organic accounts.', 'active'),
(3, 1, 'Instagram Reels Views [High Retention + Reach Booster]', 'Default', 0.2000, 0.0900, 100, 1000000, 0, 1, 0, 'Explode your Reels into Explore algorithm.', 'active'),
(4, 2, 'YouTube Views [Monetizable Lifetime Guaranteed]', 'Default', 3.2000, 2.1000, 500, 500000, 1, 1, 1, 'High retention views completely safe for AdSense monetization.', 'active'),
(5, 2, 'YouTube Subscribers [Non-Drop Ultra High Quality]', 'Default', 18.5000, 12.0000, 20, 10000, 1, 1, 0, 'Authentic subscribers with high profile score.', 'active'),
(6, 3, 'TikTok Followers [For FYP Algorithm] [Instant Start]', 'Default', 2.4000, 1.5000, 50, 100000, 1, 1, 1, 'Trigger the FYP algorithm with high-engagement followers.', 'active'),
(7, 3, 'TikTok Likes [Real Active FYP Users]', 'Default', 0.7500, 0.4000, 20, 200000, 0, 1, 1, 'Instant start likes delivery within 60 seconds.', 'active'),
(8, 4, 'Telegram Channel Members [0% Drop Non-Drop]', 'Default', 1.9500, 1.1000, 50, 50000, 1, 1, 0, 'Real looking channel subscribers with active online history.', 'active'),
(9, 5, 'X (Twitter) Followers [Active Worldwide Profiles]', 'Default', 3.8000, 2.4000, 50, 25000, 1, 1, 1, 'Organic looking followers with avatars, bios, and tweet history.', 'active');

