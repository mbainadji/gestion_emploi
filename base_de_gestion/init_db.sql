
-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `gestion`;
USE `gestion`;

-- Users table for gestion project
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add initial data
INSERT INTO `users` (username, password, role) VALUES ('admin_gestion', 'admin123', 'admin') ON DUPLICATE KEY UPDATE username=username;
