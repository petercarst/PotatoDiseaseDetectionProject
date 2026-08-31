-- uhAI Intelligence : Potato Leaf Disease Detection
-- Database schema

CREATE DATABASE IF NOT EXISTS potato_disease_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE potato_disease_db;

CREATE TABLE IF NOT EXISTS scans (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  original_name   VARCHAR(255) NOT NULL,
  stored_name     VARCHAR(255) NOT NULL,
  predicted_class VARCHAR(50)  NOT NULL,
  confidence      DECIMAL(6,3) NOT NULL,
  status          ENUM('healthy', 'diseased') NOT NULL DEFAULT 'diseased',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_created_at (created_at),
  INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
