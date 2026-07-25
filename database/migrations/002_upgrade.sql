-- Enterprise UI Additions
-- Run this to safely add new columns without destroying existing data

ALTER TABLE users 
ADD COLUMN IF NOT EXISTS profile_picture VARCHAR(500) NULL AFTER email;

ALTER TABLE universities 
ADD COLUMN IF NOT EXISTS logo_path VARCHAR(500) NULL AFTER city,
ADD COLUMN IF NOT EXISTS image_path VARCHAR(500) NULL AFTER logo_path;

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    role VARCHAR(50) NOT NULL,
    action TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
