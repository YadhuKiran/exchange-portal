-- Enterprise upgrade (additive) — run after database/database.sql
USE exchange_portal;

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS passports (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL UNIQUE,
  passport_number VARCHAR(50)  NOT NULL,
  issuing_country VARCHAR(100) NOT NULL,
  issue_date      DATE         NOT NULL,
  expiry_date     DATE         NOT NULL,
  document_id     INT UNSIGNED NULL,
  status          ENUM('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending',
  verified_by     INT UNSIGNED NULL,
  verified_at     DATETIME NULL,
  notes           TEXT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_passports_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_passports_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL,
  CONSTRAINT fk_passports_verified FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS visas (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  application_id  INT UNSIGNED NULL,
  visa_type       VARCHAR(100) NOT NULL,
  visa_number     VARCHAR(50) NULL,
  issuing_country VARCHAR(100) NOT NULL,
  issue_date      DATE NULL,
  expiry_date     DATE NOT NULL,
  document_id     INT UNSIGNED NULL,
  status          ENUM('pending','verified','rejected','expired') NOT NULL DEFAULT 'pending',
  verified_by     INT UNSIGNED NULL,
  verified_at     DATETIME NULL,
  notes           TEXT NULL,
  created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_visas_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_visas_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
  CONSTRAINT fk_visas_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL,
  CONSTRAINT fk_visas_verified FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_visas_student (student_id),
  INDEX idx_visas_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS transcripts (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id       INT UNSIGNED NOT NULL,
  institution_name VARCHAR(255) NOT NULL,
  degree_program   VARCHAR(255) NULL,
  gpa              DECIMAL(4,2) NULL,
  grading_scale    VARCHAR(50) NULL,
  issue_date       DATE NULL,
  document_id      INT UNSIGNED NULL,
  is_official      TINYINT(1) NOT NULL DEFAULT 0,
  status           ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  verified_by      INT UNSIGNED NULL,
  verified_at      DATETIME NULL,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_transcripts_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_transcripts_document FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL,
  CONSTRAINT fk_transcripts_verified FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_transcripts_student (student_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS enrollments (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  student_id      INT UNSIGNED NOT NULL,
  course_id       INT UNSIGNED NOT NULL,
  application_id  INT UNSIGNED NULL,
  status          ENUM('pending','approved','dropped','completed','rejected') NOT NULL DEFAULT 'pending',
  approved_by     INT UNSIGNED NULL,
  enrolled_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  dropped_at      DATETIME NULL,
  UNIQUE KEY uq_enrollment_student_course (student_id, course_id),
  CONSTRAINT fk_enrollments_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_enrollments_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE SET NULL,
  CONSTRAINT fk_enrollments_approved FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_enrollments_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS application_status_history (
  id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id       INT UNSIGNED NOT NULL,
  from_status          VARCHAR(50) NULL,
  to_status            VARCHAR(50) NOT NULL,
  changed_by_user_id   INT UNSIGNED NOT NULL,
  comment              TEXT NULL,
  created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_ash_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  CONSTRAINT fk_ash_user FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_ash_application (application_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS activity_logs (
  id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NULL,
  action       VARCHAR(100) NOT NULL,
  entity_type  VARCHAR(50) NULL,
  entity_id    INT UNSIGNED NULL,
  description  VARCHAR(500) NOT NULL,
  ip_address   VARCHAR(45) NULL,
  user_agent   VARCHAR(255) NULL,
  metadata     JSON NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  INDEX idx_activity_created (created_at),
  INDEX idx_activity_user (user_id, created_at),
  INDEX idx_activity_action (action)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS system_settings (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key   VARCHAR(100) NOT NULL UNIQUE,
  setting_value TEXT NOT NULL,
  setting_group VARCHAR(50) NOT NULL DEFAULT 'general',
  data_type     ENUM('string','integer','boolean','json') NOT NULL DEFAULT 'string',
  label         VARCHAR(255) NOT NULL,
  updated_by    INT UNSIGNED NULL,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;

INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_group, data_type, label) VALUES
('site_name', 'Global Exchange Portal', 'branding', 'string', 'Site Name'),
('max_upload_mb', '5', 'uploads', 'integer', 'Max Upload Size (MB)'),
('enrollment_requires_approved_application', '1', 'enrollment', 'boolean', 'Require Approved Application to Enroll'),
('academic_year', '2025-2026', 'general', 'string', 'Academic Year'),
('chart_months_range', '12', 'general', 'integer', 'Chart Months Range'),
('application_prefix', 'APP', 'general', 'string', 'Application Number Prefix');

-- Backfill status history for existing applications
INSERT INTO application_status_history (application_id, from_status, to_status, changed_by_user_id, comment, created_at)
SELECT a.id, NULL, a.status, (SELECT id FROM users WHERE role='admin' LIMIT 1), 'Migrated from MVP', a.updated_at
FROM applications a
WHERE NOT EXISTS (SELECT 1 FROM application_status_history h WHERE h.application_id = a.id);
