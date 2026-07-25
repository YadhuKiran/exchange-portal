-- Fix ALL user passwords to work with password123
-- Generated with PHP 8.2 password_hash('password123', PASSWORD_BCRYPT, ['cost' => 10])

UPDATE users SET password_hash = '$2y$10$QGRCheKq0WlPJa9F9T/6QuQ/f2CJMd8X73HrjUbcedGnSkchdWPTi';
