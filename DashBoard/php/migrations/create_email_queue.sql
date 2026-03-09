-- Migration: create_email_queue.sql
-- Crée la table email_queue utilisée pour la file d'attente des emails

CREATE TABLE IF NOT EXISTS `email_queue` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `recipient` VARCHAR(255) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` LONGTEXT NOT NULL,
  `attachments` TEXT DEFAULT NULL,
  `status` ENUM('pending','processing','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` INT(11) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME DEFAULT NULL,
  `last_error` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_recipient` (`recipient`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
