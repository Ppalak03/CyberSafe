-- CyberSafe schema (MySQL 8 / MariaDB 10.4+). Import via phpMyAdmin > Import.
CREATE DATABASE IF NOT EXISTS cybersafe_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cybersafe_db;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS user_badges, badges, attempts, options, scenarios, categories, login_attempts, users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','admin') NOT NULL DEFAULT 'user',
  total_xp INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(50) NOT NULL DEFAULT 'bi-shield-check',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE scenarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  title VARCHAR(150) NOT NULL,
  content_type ENUM('email','sms','job_offer','url','login_page','chat') NOT NULL,
  content JSON NOT NULL,                 -- structured fields per content_type (sender, subject, body, link...)
  difficulty ENUM('Easy','Medium','Hard') NOT NULL DEFAULT 'Easy',
  explanation TEXT NOT NULL,
  red_flags TEXT NOT NULL,               -- one flag per line
  safety_tip VARCHAR(500) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_scenarios_cat (category_id, is_active),
  CONSTRAINT fk_scenarios_cat FOREIGN KEY (category_id) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  scenario_id INT UNSIGNED NOT NULL,
  option_text VARCHAR(40) NOT NULL,
  classification ENUM('SAFE','SUSPICIOUS','SCAM') NOT NULL,
  is_correct TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_option_class (scenario_id, classification),
  CONSTRAINT fk_options_scenario FOREIGN KEY (scenario_id) REFERENCES scenarios(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;
-- "Exactly one correct option" is enforced in PHP inside a transaction when a scenario is saved.

CREATE TABLE attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  scenario_id INT UNSIGNED NOT NULL,
  selected_option_id INT UNSIGNED NOT NULL,
  is_correct TINYINT(1) NOT NULL,
  score INT UNSIGNED NOT NULL DEFAULT 0,
  xp_earned INT UNSIGNED NOT NULL DEFAULT 0,
  response_time INT UNSIGNED NULL COMMENT 'seconds',
  attempt_token CHAR(64) NOT NULL,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_attempt_token (attempt_token),
  KEY idx_attempts_user (user_id, attempted_at),
  KEY idx_attempts_scenario (scenario_id),
  CONSTRAINT fk_att_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_att_scenario FOREIGN KEY (scenario_id) REFERENCES scenarios(id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_att_option FOREIGN KEY (selected_option_id) REFERENCES options(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE badges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NOT NULL,
  icon VARCHAR(50) NOT NULL,
  xp_reward INT UNSIGNED NOT NULL DEFAULT 0,
  requirement_type ENUM('first_attempt','correct_total','phishing_correct','scam_correct','accuracy','xp_total') NOT NULL,
  requirement_value INT UNSIGNED NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_badge_name (name)
) ENGINE=InnoDB;

CREATE TABLE user_badges (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  badge_id INT UNSIGNED NOT NULL,
  earned_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_badge (user_id, badge_id),
  CONSTRAINT fk_ub_user FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_ub_badge FOREIGN KEY (badge_id) REFERENCES badges(id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

-- Extra table: DB-backed login rate limiting
CREATE TABLE login_attempts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL,
  ip_address VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_la_email (email, attempted_at),
  KEY idx_la_ip (ip_address, attempted_at)
) ENGINE=InnoDB;

INSERT INTO categories (name, description, icon) VALUES
('Phishing Emails','Spot deceptive emails that try to steal your details.','bi-envelope-exclamation'),
('SMS Scams','Detect fraudulent text messages and fake delivery alerts.','bi-chat-left-text'),
('Fake Job Offers','Identify fake recruitment offers and upfront-fee traps.','bi-briefcase'),
('Suspicious URLs','Inspect risky links and look-alike domains.','bi-link-45deg'),
('Fake Login Pages','Notice credential traps that imitate real sign-in pages.','bi-box-arrow-in-right'),
('Social Engineering','Recognize manipulation that targets people, not systems.','bi-people');

INSERT INTO badges (name, description, icon, xp_reward, requirement_type, requirement_value) VALUES
('First Shield','Complete your first scenario.','bi-shield-check',5,'first_attempt',1),
('Threat Spotter','Get 5 correct answers.','bi-binoculars',10,'correct_total',5),
('Phishing Hunter','Correctly identify 5 phishing scenarios.','bi-envelope-exclamation',15,'phishing_correct',5),
('Scam Buster','Correctly identify 5 scam scenarios.','bi-slash-circle',15,'scam_correct',5),
('Cyber Detective','Reach 80% accuracy after at least 20 attempts.','bi-search',25,'accuracy',80),
('CyberSafe Master','Reach 1000 XP.','bi-trophy',0,'xp_total',1000);
