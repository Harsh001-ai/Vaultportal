-- Secure Asset Collection & Delivery Portal Database Schema
-- Compatible with MySQL 8.0+ and MariaDB 10.4+

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `download_events`;
DROP TABLE IF EXISTS `export_items`;
DROP TABLE IF EXISTS `export_jobs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `activity_logs`;
DROP TABLE IF EXISTS `rate_limits`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `assets`;
DROP TABLE IF EXISTS `collections`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(191) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
  `status` ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
  `storage_quota_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 1073741824, -- 1 GB default
  `storage_used_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `last_login_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Collections Table
CREATE TABLE `collections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `user_id` INT NULL, -- NULL indicates global/admin-curated collection
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `is_global` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_collections_user` (`user_id`),
  INDEX `idx_collections_global` (`is_global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Assets Table
CREATE TABLE `assets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `uploader_id` INT NOT NULL,
  `collection_id` INT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `stored_filename` VARCHAR(255) NOT NULL,
  `storage_path` VARCHAR(500) NOT NULL,
  `mime_type` VARCHAR(150) NOT NULL,
  `file_extension` VARCHAR(20) NOT NULL,
  `file_size_bytes` BIGINT UNSIGNED NOT NULL,
  `sha256_checksum` VARCHAR(64) NOT NULL,
  `status` ENUM('ready', 'processing', 'quarantined', 'failed') NOT NULL DEFAULT 'ready',
  `quarantine_reason` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL,
  FOREIGN KEY (`uploader_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`collection_id`) REFERENCES `collections`(`id`) ON DELETE SET NULL,
  INDEX `idx_assets_uploader` (`uploader_id`),
  INDEX `idx_assets_collection` (`collection_id`),
  INDEX `idx_assets_status` (`status`),
  INDEX `idx_assets_created` (`created_at`),
  INDEX `idx_assets_extension` (`file_extension`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Export Jobs Table
CREATE TABLE `export_jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `uuid` VARCHAR(36) NOT NULL UNIQUE,
  `admin_id` INT NOT NULL,
  `export_name` VARCHAR(200) NOT NULL,
  `format` ENUM('original', 'zip_flat', 'zip_by_user', 'zip_by_type', 'zip_by_collection', 'manifest_json', 'manifest_csv', 'combined_zip') NOT NULL,
  `total_files` INT NOT NULL DEFAULT 0,
  `total_uncompressed_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `archive_size_bytes` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `archive_path` VARCHAR(500) NULL,
  `download_token` VARCHAR(64) UNIQUE NULL,
  `token_expires_at` DATETIME NULL,
  `status` ENUM('queued', 'preparing', 'packaging', 'ready', 'failed', 'expired') NOT NULL DEFAULT 'queued',
  `progress_percent` INT NOT NULL DEFAULT 0,
  `error_message` TEXT NULL,
  `export_notes` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` DATETIME NULL,
  FOREIGN KEY (`admin_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_exports_admin` (`admin_id`),
  INDEX `idx_exports_status` (`status`),
  INDEX `idx_exports_token` (`download_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Export Items Table (Item-Level Source User & Asset Attribution)
CREATE TABLE `export_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `export_job_id` INT NOT NULL,
  `asset_id` INT NOT NULL,
  `source_user_id` INT NOT NULL,
  `original_filename` VARCHAR(255) NOT NULL,
  `archive_path` VARCHAR(500) NOT NULL,
  `file_size_bytes` BIGINT UNSIGNED NOT NULL,
  `sha256_checksum` VARCHAR(64) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`export_job_id`) REFERENCES `export_jobs`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`source_user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_exp_items_job` (`export_job_id`),
  INDEX `idx_exp_items_asset` (`asset_id`),
  INDEX `idx_exp_items_user` (`source_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Download Events Table (Detailed Audit of Deliveries)
CREATE TABLE `download_events` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `export_job_id` INT NULL,
  `asset_id` INT NULL,
  `downloaded_by_id` INT NOT NULL,
  `downloaded_by_name` VARCHAR(150) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` TEXT NULL,
  `download_status` ENUM('success', 'failed') NOT NULL DEFAULT 'success',
  `error_message` TEXT NULL,
  `downloaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`export_job_id`) REFERENCES `export_jobs`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`asset_id`) REFERENCES `assets`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`downloaded_by_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT,
  INDEX `idx_dl_events_job` (`export_job_id`),
  INDEX `idx_dl_events_user` (`downloaded_by_id`),
  INDEX `idx_dl_events_time` (`downloaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Activity Logs Table
CREATE TABLE `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` VARCHAR(50) NULL,
  `details_json` LONGTEXT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  INDEX `idx_act_user` (`user_id`),
  INDEX `idx_act_action` (`action`),
  INDEX `idx_act_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Notifications Table
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `message` TEXT NOT NULL,
  `type` ENUM('info', 'success', 'warning', 'danger') NOT NULL DEFAULT 'info',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `link_url` VARCHAR(255) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  INDEX `idx_notif_user` (`user_id`, `is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Rate Limits Table
CREATE TABLE `rate_limits` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `rate_key` VARCHAR(191) NOT NULL UNIQUE,
  `hits` INT NOT NULL DEFAULT 1,
  `reset_at` INT NOT NULL,
  INDEX `idx_rate_reset` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. System Settings Table
CREATE TABLE `system_settings` (
  `setting_key` VARCHAR(100) PRIMARY KEY,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(255) NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'VaultPortal Enterprise', 'Portal Brand Name'),
('allowed_extensions', 'jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip', 'Comma-separated allowed file extensions'),
('max_upload_size_mb', '100', 'Maximum single file upload size in megabytes'),
('default_user_quota_mb', '1024', 'Default quota per user in megabytes (1GB)'),
('export_expiry_hours', '24', 'Hours before export downloads expire'),
('quarantine_enabled', '1', 'Heuristic malware & executable inspection enabled');
