CREATE DATABASE IF NOT EXISTS alumni_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE alumni_platform;

CREATE TABLE IF NOT EXISTS alumni (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(80) NOT NULL,
  last_name VARCHAR(80) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  email_verified TINYINT(1) NOT NULL DEFAULT 0,
  verification_token VARCHAR(255),
  verification_expires DATETIME,
  reset_token VARCHAR(255),
  reset_expires DATETIME,
  program VARCHAR(120),
  graduation_year INT,
  industry_sector VARCHAR(120),
  current_job_title VARCHAR(120),
  company VARCHAR(120),
  linkedin_url VARCHAR(255),
  profile_image VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_program (program),
  INDEX idx_graduation_year (graduation_year),
  INDEX idx_industry_sector (industry_sector)
);

CREATE TABLE IF NOT EXISTS alumni_profile_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumni_id INT NOT NULL,
  item_type ENUM('degree', 'certification', 'license', 'course') NOT NULL,
  title VARCHAR(160) NOT NULL,
  institution VARCHAR(160),
  field_of_study VARCHAR(160),
  start_date DATE,
  end_date DATE,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_profile_items_alumni FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
  INDEX idx_profile_item_type (item_type)
);

CREATE TABLE IF NOT EXISTS employment_history (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumni_id INT NOT NULL,
  job_title VARCHAR(160) NOT NULL,
  company VARCHAR(160) NOT NULL,
  industry_sector VARCHAR(120),
  start_date DATE,
  end_date DATE,
  is_current TINYINT(1) DEFAULT 0,
  description TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_employment_alumni FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
  INDEX idx_employment_industry (industry_sector)
);

CREATE TABLE IF NOT EXISTS bids (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumni_id INT NOT NULL,
  bid_amount DECIMAL(10,2) NOT NULL,
  bid_date DATE NOT NULL,
  month_key CHAR(7) NOT NULL,
  status ENUM('active', 'won', 'lost') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_bids_alumni FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
  CONSTRAINT uq_alumni_one_bid_per_day UNIQUE (alumni_id, bid_date),
  INDEX idx_bid_date (bid_date),
  INDEX idx_bid_month (month_key),
  INDEX idx_bid_amount (bid_amount)
);

CREATE TABLE IF NOT EXISTS alumni_of_day (
  id INT AUTO_INCREMENT PRIMARY KEY,
  alumni_id INT NOT NULL,
  winning_bid_id INT NOT NULL,
  feature_date DATE NOT NULL UNIQUE,
  winning_amount DECIMAL(10,2) NOT NULL,
  selected_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aod_alumni FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
  CONSTRAINT fk_aod_bid FOREIGN KEY (winning_bid_id) REFERENCES bids(id) ON DELETE CASCADE,
  INDEX idx_feature_date (feature_date)
);

CREATE TABLE IF NOT EXISTS api_keys (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  api_key_hash CHAR(64) NOT NULL UNIQUE,
  permissions JSON NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  last_used_at DATETIME NULL
);

role ENUM('alumni', 'developer') NOT NULL DEFAULT 'alumni',
email_verified TINYINT(1) NOT NULL DEFAULT 0,
role ENUM('alumni', 'developer') NOT NULL DEFAULT 'alumni',
verification_token VARCHAR(255),
UPDATE alumni
SET role = 'developer', email_verified = 1
WHERE email = 'your_email@westminster.ac.uk';
