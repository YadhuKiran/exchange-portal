-- GLOBAL STUDENT MOBILITY & EXCHANGE PORTAL — MVP Database
-- Import via phpMyAdmin or: mysql -u root < database/database.sql

CREATE DATABASE IF NOT EXISTS exchange_portal
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE exchange_portal;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS coordinators;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS universities;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- UNIVERSITIES
-- ============================================================
CREATE TABLE universities (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(255) NOT NULL,
  code          VARCHAR(20)  NOT NULL UNIQUE,
  country       VARCHAR(100) NOT NULL,
  city          VARCHAR(100) NOT NULL,
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- USERS (all roles authenticate here)
-- ============================================================
CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  role          ENUM('admin','coordinator','student') NOT NULL,
  email         VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  first_name    VARCHAR(100) NOT NULL,
  last_name     VARCHAR(100) NOT NULL,
  status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- STUDENTS
-- ============================================================
CREATE TABLE students (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL UNIQUE,
  student_number  VARCHAR(50)  NOT NULL UNIQUE,
  university_id   INT UNSIGNED NOT NULL,
  phone           VARCHAR(30)  NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_students_user       FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
  CONSTRAINT fk_students_university FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE RESTRICT,
  INDEX idx_students_university (university_id)
) ENGINE=InnoDB;

-- ============================================================
-- COORDINATORS
-- ============================================================
CREATE TABLE coordinators (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         INT UNSIGNED NOT NULL UNIQUE,
  university_id   INT UNSIGNED NOT NULL,
  department      VARCHAR(150) NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_coordinators_user       FOREIGN KEY (user_id)       REFERENCES users(id)       ON DELETE CASCADE,
  CONSTRAINT fk_coordinators_university FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- COURSES
-- ============================================================
CREATE TABLE courses (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  university_id   INT UNSIGNED NOT NULL,
  code            VARCHAR(50)  NOT NULL,
  title           VARCHAR(255) NOT NULL,
  credits         DECIMAL(4,1) NOT NULL DEFAULT 3.0,
  semester        VARCHAR(50)  NOT NULL,
  capacity        INT UNSIGNED NOT NULL DEFAULT 30,
  status          ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_courses_university FOREIGN KEY (university_id) REFERENCES universities(id) ON DELETE CASCADE,
  UNIQUE KEY uq_course_uni_code (university_id, code),
  INDEX idx_courses_semester (semester)
) ENGINE=InnoDB;

-- ============================================================
-- APPLICATIONS
-- ============================================================
CREATE TABLE applications (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id            INT UNSIGNED NOT NULL,
  home_university_id    INT UNSIGNED NOT NULL,
  host_university_id    INT UNSIGNED NOT NULL,
  semester              VARCHAR(50)  NOT NULL,
  status                ENUM('draft','submitted','under_review','approved','rejected') NOT NULL DEFAULT 'draft',
  personal_statement    TEXT         NULL,
  coordinator_notes     TEXT         NULL,
  submitted_at          DATETIME     NULL,
  created_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_applications_student FOREIGN KEY (student_id)         REFERENCES students(id)     ON DELETE CASCADE,
  CONSTRAINT fk_applications_home    FOREIGN KEY (home_university_id) REFERENCES universities(id) ON DELETE RESTRICT,
  CONSTRAINT fk_applications_host    FOREIGN KEY (host_university_id) REFERENCES universities(id) ON DELETE RESTRICT,
  INDEX idx_applications_status (status),
  INDEX idx_applications_student (student_id)
) ENGINE=InnoDB;

-- ============================================================
-- DOCUMENTS
-- ============================================================
CREATE TABLE documents (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  application_id  INT UNSIGNED NULL,
  title           VARCHAR(255) NOT NULL,
  file_name       VARCHAR(255) NOT NULL,
  file_path       VARCHAR(500) NOT NULL,
  file_size       INT UNSIGNED NOT NULL DEFAULT 0,
  status          ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  uploaded_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_documents_student     FOREIGN KEY (student_id)     REFERENCES students(id)     ON DELETE CASCADE,
  CONSTRAINT fk_documents_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
  INDEX idx_documents_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  title       VARCHAR(255) NOT NULL,
  message     TEXT         NOT NULL,
  is_read     TINYINT(1)   NOT NULL DEFAULT 0,
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- Password for all demo accounts: password123
-- ============================================================
SET @pwd = '$2y$10$QGRCheKq0WlPJa9F9T/6QuQ/f2CJMd8X73HrjUbcedGnSkchdWPTi';

INSERT INTO universities (name, code, country, city, status) VALUES
('Massachusetts Institute of Technology', 'MIT',  'United States', 'Cambridge',   'active'),
('University of Oxford',                   'OXF',  'United Kingdom','Oxford',      'active'),
('National University of Singapore',     'NUS',  'Singapore',     'Singapore',   'active'),
('University of Toronto',                'UOT',  'Canada',        'Toronto',     'active'),
('Technical University of Munich',         'TUM',  'Germany',       'Munich',      'active');

INSERT INTO users (role, email, password_hash, first_name, last_name, status) VALUES
('admin',       'admin@exchangeportal.com',       @pwd, 'Sarah',  'Mitchell',  'active'),
('coordinator', 'james.chen@mit.edu',             @pwd, 'James',  'Chen',      'active'),
('coordinator', 'emma.walsh@oxford.ac.uk',        @pwd, 'Emma',   'Walsh',     'active'),
('student',     'alex.johnson@student.mit.edu',   @pwd, 'Alex',   'Johnson',   'active'),
('student',     'maria.garcia@student.mit.edu',     @pwd, 'Maria',  'Garcia',    'active'),
('student',     'li.wei@student.nus.edu',         @pwd, 'Li',     'Wei',       'active'),
('student',     'sophie.martin@student.uot.ca',   @pwd, 'Sophie', 'Martin',      'active'),
('student',     'david.okonkwo@student.uot.ca',   @pwd, 'David',  'Okonkwo',   'active');

INSERT INTO coordinators (user_id, university_id, department) VALUES
(2, 1, 'International Programs Office'),
(3, 2, 'Global Mobility Centre');

INSERT INTO students (user_id, student_number, university_id, phone) VALUES
(4, 'MIT-2024-1042', 1, '+1-617-555-0142'),
(5, 'MIT-2024-1087', 1, '+1-617-555-0187'),
(6, 'NUS-2023-3310', 3, '+65-9123-4410'),
(7, 'UOT-2024-5521', 4, '+1-416-555-5521'),
(8, 'UOT-2023-4890', 4, '+1-416-555-4890');

INSERT INTO courses (university_id, code, title, credits, semester, capacity, status) VALUES
(1, 'CS-101', 'Introduction to Computer Science', 4.0, 'Fall 2026', 40, 'open'),
(1, 'ENG-205', 'Academic Writing for Exchange Students', 3.0, 'Fall 2026', 25, 'open'),
(2, 'HIS-310', 'European History & Culture', 3.0, 'Fall 2026', 30, 'open'),
(2, 'BUS-220', 'International Business Management', 3.0, 'Fall 2026', 35, 'open'),
(3, 'ECO-150', 'Principles of Economics', 3.0, 'Fall 2026', 50, 'open'),
(4, 'BIO-201', 'Molecular Biology', 4.0, 'Fall 2026', 20, 'open'),
(5, 'MECH-330', 'Advanced Mechanical Engineering', 4.0, 'Spring 2027', 15, 'open'),
(3, 'POL-110', 'Comparative Politics', 3.0, 'Fall 2026', 28, 'open');

INSERT INTO applications (student_id, home_university_id, host_university_id, semester, status, personal_statement, coordinator_notes, submitted_at) VALUES
(1, 1, 2, 'Fall 2026', 'approved',
 'I am eager to experience Oxford''s tutorial system and deepen my knowledge of European history while continuing my CS studies.',
 'Strong academic record. Approved for Fall 2026 exchange.', '2026-01-15 10:30:00'),
(2, 1, 3, 'Fall 2026', 'under_review',
 'Singapore offers an exceptional hub for technology and cross-cultural business exposure that aligns with my career goals.',
 NULL, '2026-02-20 14:00:00'),
(3, 3, 1, 'Fall 2026', 'submitted',
 'MIT''s research environment would allow me to collaborate on cutting-edge economics and data science projects.',
 NULL, '2026-03-01 09:15:00'),
(4, 4, 5, 'Spring 2027', 'draft',
 'I wish to study mechanical engineering at TUM to complement my biology background in biomedical device design.',
 NULL, NULL),
(5, 4, 2, 'Fall 2026', 'rejected',
 'Oxford represents my dream destination for philosophy and literature studies alongside my business minor.',
 'Application submitted after deadline. Please reapply next cycle.', '2026-01-05 16:45:00');

INSERT INTO documents (student_id, application_id, title, file_name, file_path, file_size, status) VALUES
(1, 1, 'Official Transcript', 'transcript_alex_johnson.pdf', 'transcript_alex_johnson.pdf', 245760, 'approved'),
(1, 1, 'Passport Copy', 'passport_alex_johnson.pdf', 'passport_alex_johnson.pdf', 189440, 'approved'),
(1, 1, 'Recommendation Letter', 'recommendation_alex.pdf', 'recommendation_alex.pdf', 98304, 'approved'),
(2, 2, 'Official Transcript', 'transcript_maria_garcia.pdf', 'transcript_maria_garcia.pdf', 312320, 'pending'),
(2, 2, 'Passport Copy', 'passport_maria_garcia.pdf', 'passport_maria_garcia.pdf', 204800, 'pending'),
(3, 3, 'Official Transcript', 'transcript_li_wei.pdf', 'transcript_li_wei.pdf', 278528, 'pending'),
(4, NULL, 'Draft Personal Statement', 'statement_sophie_draft.docx', 'statement_sophie_draft.docx', 45056, 'pending'),
(5, 5, 'Official Transcript', 'transcript_david_okonkwo.pdf', 'transcript_david_okonkwo.pdf', 225280, 'rejected');

INSERT INTO notifications (user_id, title, message, is_read) VALUES
(4, 'Application Approved', 'Congratulations! Your exchange application to University of Oxford for Fall 2026 has been approved.', 1),
(4, 'Document Verified', 'Your passport copy has been verified by the coordinator.', 1),
(5, 'Application Under Review', 'Your application to National University of Singapore is now being reviewed.', 0),
(5, 'Document Upload Reminder', 'Please ensure all required documents are uploaded before the deadline.', 0),
(6, 'Application Received', 'Your application to MIT has been received and is pending review.', 0),
(2, 'New Application to Review', 'Maria Garcia submitted an exchange application for your review.', 0),
(2, 'Pending Documents', '2 documents are awaiting verification in your queue.', 0),
(1, 'Weekly Analytics Ready', 'System report: 5 active applications, 3 pending document verifications.', 0);
