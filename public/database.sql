-- ==========================================================
-- FR-KING AURA — Hosting Server MySQL Database Schema
-- Import this SQL file into your cPanel / phpMyAdmin Database
-- ==========================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------------------------------------
-- Table structure for `licenses`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `licenses` (
  `id` VARCHAR(64) NOT NULL,
  `license_key` VARCHAR(64) NOT NULL,
  `full_name` VARCHAR(128) NOT NULL,
  `phone` VARCHAR(64) DEFAULT '',
  `email` VARCHAR(128) DEFAULT '',
  `amount` VARCHAR(64) DEFAULT 'Paid',
  `status` ENUM('pending', 'approved', 'blocked') NOT NULL DEFAULT 'pending',
  `duration_label` VARCHAR(64) NOT NULL DEFAULT '1 Month',
  `expires_at` DATETIME DEFAULT NULL,
  `lifetime` TINYINT(1) NOT NULL DEFAULT 0,
  `max_devices` INT NOT NULL DEFAULT 1,
  `license_devices` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_license_key` (`license_key`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Pre-Approved VIP Licenses Seed Data
-- ----------------------------------------------------------
INSERT INTO `licenses` (`id`, `license_key`, `full_name`, `phone`, `email`, `amount`, `status`, `duration_label`, `expires_at`, `lifetime`, `max_devices`, `license_devices`, `created_at`, `updated_at`) VALUES
('lic_king_crown_001', 'FRK-VIP-LIFETIME-KING', 'Waqas King (VIP Master)', '+966554573612', 'codewithwaqas.dev@gmail.com', 'VIP Crown Sponsor', 'approved', 'Lifetime', NULL, 1, 10, '[]', NOW(), NOW()),
('lic_master_001', 'FRK-PRO-MASTER-KEY-2026', 'VIP Master Admin', '+966554573612', 'emailwaqas7@gmail.com', 'VIP Master Key', 'approved', 'Lifetime', NULL, 1, 10, '[]', NOW(), NOW()),
('lic_1year_crown_8820', 'FRK-CROWN-1YEAR-8820', 'Elite Crown Trader', '+966554573612', 'client@frkings.pro', '350 USD', 'approved', '1 Year', DATE_ADD(NOW(), INTERVAL 1 YEAR), 0, 5, '[]', NOW(), NOW()),
('lic_elite_6m_5531', 'FRK-ELITE-6M-5531', 'Sultan Elite Pro', '+966554573612', 'sultan.trader@gmail.com', '180 USD', 'approved', '6 Months', DATE_ADD(NOW(), INTERVAL 6 MONTH), 0, 3, '[]', NOW(), NOW()),
('lic_pro_3m_4419', 'FRK-PRO-3M-4419', 'Waqas Pro Trader', '+966554573612', 'codewithwaqas.dev@gmail.com', '100 USD', 'approved', '3 Months', DATE_ADD(NOW(), INTERVAL 3 MONTH), 0, 3, '[]', NOW(), NOW()),
('lic_gold_1m_7720', 'FRK-GOLD-1M-7720', 'Quotex Gold Trader', '+966554573612', 'gold.trader@gmail.com', '50 USD', 'approved', '1 Month', DATE_ADD(NOW(), INTERVAL 1 MONTH), 0, 2, '[]', NOW(), NOW()),
('lic_alpha_1m_9932', 'FRK-ALPHA-1M-9932', 'Alpha Scalper Max', '+966554573612', 'alpha.scalper@gmail.com', '50 USD', 'approved', '1 Month', DATE_ADD(NOW(), INTERVAL 1 MONTH), 0, 2, '[]', NOW(), NOW()),
('lic_trial_3days_01', 'FRK-TRIAL-3DAYS-01', 'VIP 3-Day Trial User', '+966554573612', 'trial.user@gmail.com', 'Free Trial', 'approved', '3 Days', DATE_ADD(NOW(), INTERVAL 3 DAY), 0, 1, '[]', NOW(), NOW())
ON DUPLICATE KEY UPDATE `status` = VALUES(`status`);

-- ----------------------------------------------------------
-- Table structure for `signals`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `signals` (
  `id` VARCHAR(64) NOT NULL,
  `pair` VARCHAR(32) NOT NULL,
  `market` VARCHAR(32) NOT NULL DEFAULT 'Binary Options',
  `timer` VARCHAR(16) NOT NULL DEFAULT '1M',
  `mode` VARCHAR(16) NOT NULL DEFAULT 'live',
  `direction` ENUM('BUY', 'SELL') NOT NULL,
  `entry` VARCHAR(32) NOT NULL,
  `expiry` VARCHAR(32) DEFAULT '',
  `accuracy` VARCHAR(16) DEFAULT '96%',
  `strength` VARCHAR(16) DEFAULT 'Ultra',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- Table structure for `app_config`
-- ----------------------------------------------------------
CREATE TABLE IF NOT EXISTS `app_config` (
  `setting_key` VARCHAR(64) NOT NULL,
  `setting_value` TEXT NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `app_config` (`setting_key`, `setting_value`) VALUES
('apiKey', 'FRK-PRO-MASTER-KEY-2026'),
('allowMasterApiKeyBypass', '1'),
('whatsappUrl', 'https://chat.whatsapp.com/G1yKmNVIntiLx7c9KX5zEu'),
('telegramUrl', 'https://t.me/frking00'),
('phone', '+966554573612'),
('developer', '@codetrader')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

SET FOREIGN_KEY_CHECKS = 1;
