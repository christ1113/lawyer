-- Database schema for the lawyer project
-- Creates the database, users and case_stories table

CREATE DATABASE IF NOT EXISTS lawyerdb CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE lawyerdb;

-- users table
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) DEFAULT NULL,
  role VARCHAR(50) DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- case_stories table (used by backend.php)
CREATE TABLE IF NOT EXISTS case_stories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  client_name VARCHAR(255) DEFAULT NULL,
  lawyer VARCHAR(255) DEFAULT NULL,
  uploader VARCHAR(255) DEFAULT NULL,
  upload_date DATE DEFAULT NULL,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_case_stories_upload_date ON case_stories (upload_date);
