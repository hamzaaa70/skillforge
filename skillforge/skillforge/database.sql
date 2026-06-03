-- =============================================
--  SkillForge Database Setup
--  Run this in phpMyAdmin or MySQL CLI
--  Command: mysql -u root -p < database.sql
-- =============================================

CREATE DATABASE IF NOT EXISTS skillforge_db;
USE skillforge_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100)        NOT NULL,
    username    VARCHAR(50)         NOT NULL UNIQUE,
    email       VARCHAR(150)        NOT NULL UNIQUE,
    password    VARCHAR(255)        NOT NULL,       -- stored as bcrypt hash
    department  VARCHAR(100)        DEFAULT NULL,
    bio         TEXT                DEFAULT NULL,
    avatar_color VARCHAR(7)         DEFAULT '#4f46e5', -- hex color for avatar
    created_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Skills table  (each user can have many skills)
CREATE TABLE IF NOT EXISTS skills (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT                 NOT NULL,
    skill_name  VARCHAR(100)        NOT NULL,
    level       ENUM('Beginner','Intermediate','Advanced','Expert') DEFAULT 'Beginner',
    category    VARCHAR(50)         DEFAULT 'General',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Sample data (optional — for testing)
INSERT INTO users (full_name, username, email, password, department, bio, avatar_color)
VALUES (
    'Ali Hassan',
    'ali_dev',
    'ali@example.com',
    -- password is: Test@1234
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Computer Science',
    'BSCS 4th Semester student passionate about web development.',
    '#7c3aed'
);
