-- Agent Performance System schema (does NOT modify existing employees table)
-- Database: wortuckd_attendance
-- Engine: InnoDB, Charset: utf8mb4

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS employees (
  employee_code VARCHAR(10) NOT NULL,
  employee_name VARCHAR(120) NOT NULL,
  PRIMARY KEY (employee_code),
  KEY idx_employees_name (employee_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  email VARCHAR(190) NULL,
  contact_phone VARCHAR(40) NULL,
  agent_name VARCHAR(120) NULL,
  rera_number VARCHAR(50) NULL,
  properties_scope ENUM('OFF_PLAN','SECONDARY','BOTH') NULL,
  photo_path VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  role ENUM('ADMIN','CEO','AGENT') NOT NULL,
  employee_code VARCHAR(10) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  KEY idx_users_role (role),
  KEY idx_users_employee_code (employee_code),
  CONSTRAINT fk_users_employee_code FOREIGN KEY (employee_code)
    REFERENCES employees(employee_code)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS leads (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_name VARCHAR(120) NOT NULL,
  contact_email VARCHAR(190) NOT NULL,
  contact_phone VARCHAR(40) NOT NULL,
  interested_in_property VARCHAR(190) NOT NULL,
  property_type ENUM('OFF_PLAN','READY_TO_MOVE') NOT NULL,
  assigned_agent_user_id INT UNSIGNED NOT NULL,
  created_by_user_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status_overall ENUM('NEW','IN_PROGRESS','CLOSED') NOT NULL DEFAULT 'NEW',
  PRIMARY KEY (id),
  KEY idx_leads_assigned_agent (assigned_agent_user_id),
  KEY idx_leads_created_at (created_at),
  KEY idx_leads_status (status_overall),
  CONSTRAINT fk_leads_assigned_agent FOREIGN KEY (assigned_agent_user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_leads_created_by FOREIGN KEY (created_by_user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS lead_followups (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  agent_user_id INT UNSIGNED NOT NULL,
  attempt_no INT UNSIGNED NOT NULL,
  contact_datetime DATETIME NOT NULL,
  next_followup_at DATETIME NULL,
  call_status ENUM('NO_RESPONSE','RESPONDED','ASK_CONTACT_LATER') NOT NULL,
  interested_status ENUM('INTERESTED','NOT_INTERESTED') NOT NULL,
  intent ENUM('RENT','BUY') NULL,
  buy_property_type ENUM('READY_TO_MOVE','OFF_PLAN') NULL,
  if_not_interested_property_type ENUM('OFF_PLAN','READY_TO_MOVE') NULL,
  unit_type ENUM('VILLA','APARTMENT') NULL,
  size_sqft INT UNSIGNED NULL,
  location VARCHAR(190) NULL,
  building VARCHAR(190) NULL,
  beds TINYINT UNSIGNED NULL,
  budget DECIMAL(12,2) NULL,
  downpayment DECIMAL(12,2) NULL,
  cheques TINYINT UNSIGNED NULL,
  rent_per_month DECIMAL(12,2) NULL,
  rent_per_year_budget DECIMAL(12,2) NULL,
  notes TEXT NOT NULL,
  call_screenshot_path VARCHAR(255) NOT NULL,
  whatsapp_contacted TINYINT(1) NOT NULL DEFAULT 0,
  whatsapp_screenshot_path VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_followup_attempt (lead_id, agent_user_id, attempt_no),
  KEY idx_followups_lead_id (lead_id),
  KEY idx_followups_agent_id (agent_user_id),
  KEY idx_followups_created_at (created_at),
  CONSTRAINT fk_followups_lead FOREIGN KEY (lead_id)
    REFERENCES leads(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_followups_agent FOREIGN KEY (agent_user_id)
    REFERENCES users(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS audit_logs (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT UNSIGNED NULL,
  action VARCHAR(80) NOT NULL,
  meta_json JSON NULL,
  ip_address VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_audit_user (user_id),
  KEY idx_audit_created (created_at),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username VARCHAR(50) NOT NULL,
  ip_address VARCHAR(64) NOT NULL,
  attempts INT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_attempt_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_login_attempts (username, ip_address),
  KEY idx_login_locked (locked_until),
  KEY idx_login_last_attempt (last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
