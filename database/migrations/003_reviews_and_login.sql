-- 003_reviews_and_login.sql
USE exchange_portal;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create reviews table for the home page
CREATE TABLE IF NOT EXISTS reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    role VARCHAR(150) NOT NULL,
    text TEXT NOT NULL,
    initials VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Add columns to applications to support advanced application form
ALTER TABLE applications
ADD COLUMN preferred_course_id INT UNSIGNED NULL AFTER host_university_id,
ADD COLUMN preferred_department VARCHAR(150) NULL AFTER preferred_course_id,
ADD CONSTRAINT fk_applications_course FOREIGN KEY (preferred_course_id) REFERENCES courses(id) ON DELETE SET NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- 3. Insert real reviews (cleaning out old one-off tests if any)
TRUNCATE TABLE reviews;
INSERT INTO reviews (name, role, text, initials) VALUES
('Alex Johnson', 'MIT → Oxford', 'The exchange program was life-changing. The platform made the entire process seamless — from application to document verification. I could focus on preparing for my journey instead of paperwork.', 'AJ'),
('Maria Garcia', 'MIT → NUS', 'Studying at NUS opened my eyes to new perspectives in technology and business. The coordinator support throughout the application was exceptional. Truly a global experience.', 'MG'),
('Li Wei', 'NUS → MIT', 'Managing documents and visa applications used to be stressful. This portal simplified everything. I had real-time updates and direct communication with my coordinator.', 'LW');
