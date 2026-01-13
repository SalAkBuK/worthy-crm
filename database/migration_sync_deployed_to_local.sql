-- Sync deployed schema with local schema changes.
-- Review before running in production; some ALTERs can be destructive if data is not compatible.

START TRANSACTION;

-- Leads: add follow-up scheduling fields
ALTER TABLE leads
  ADD COLUMN IF NOT EXISTS next_followup_at DATETIME NULL AFTER lead_status,
  ADD COLUMN IF NOT EXISTS next_followup_note VARCHAR(255) NULL AFTER next_followup_at,
  ADD COLUMN IF NOT EXISTS next_followup_set_by_user_id INT UNSIGNED NULL AFTER next_followup_note,
  ADD COLUMN IF NOT EXISTS next_followup_status ENUM('scheduled','cleared') NULL AFTER next_followup_set_by_user_id;

-- Leads: align column sizes/enums with local schema
UPDATE leads SET property_type = NULL WHERE property_type = 'NONE';

ALTER TABLE leads
  MODIFY property_type ENUM('OFF_PLAN','READY_TO_MOVE') NULL,
  MODIFY property_interest_types VARCHAR(190) NULL,
  MODIFY area VARCHAR(190) NULL,
  MODIFY budget_aed_range VARCHAR(80) NULL,
  MODIFY lead_status VARCHAR(50) NULL,
  MODIFY assigned_agent_user_id INT UNSIGNED NULL,
  MODIFY status_overall ENUM('NEW','IN_PROGRESS','50/50','ON_HOLD','CLOSED') NOT NULL DEFAULT 'NEW';

-- Leads: add next-followup index and FK
ALTER TABLE leads
  ADD KEY IF NOT EXISTS idx_leads_next_followup (next_followup_at);

ALTER TABLE leads
  ADD CONSTRAINT fk_leads_next_followup_set_by
    FOREIGN KEY (next_followup_set_by_user_id)
    REFERENCES users(id)
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- Lead followups: add launch_at and expand interested_status
ALTER TABLE lead_followups
  ADD COLUMN IF NOT EXISTS launch_at DATETIME NULL AFTER next_followup_at,
  MODIFY interested_status ENUM('INTERESTED','NOT_INTERESTED','50/50','FUTURE_INTEREST') NOT NULL;

-- Lead followup remarks: create if missing
CREATE TABLE IF NOT EXISTS lead_followup_remarks (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  lead_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NULL,
  remark TEXT NOT NULL,
  outcome VARCHAR(80) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_followup_remarks_lead (lead_id),
  KEY idx_followup_remarks_user (user_id),
  KEY idx_followup_remarks_created (created_at),
  CONSTRAINT fk_followup_remarks_lead FOREIGN KEY (lead_id)
    REFERENCES leads(id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_followup_remarks_user FOREIGN KEY (user_id)
    REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications: add read_at/dedup_key and indexes
ALTER TABLE notifications
  ADD COLUMN IF NOT EXISTS dedup_key VARCHAR(190) NULL AFTER meta_json,
  ADD COLUMN IF NOT EXISTS read_at DATETIME NULL AFTER dedup_key,
  ADD UNIQUE KEY IF NOT EXISTS uq_notifications_dedup (user_id, dedup_key),
  ADD KEY IF NOT EXISTS idx_notifications_read (read_at);

COMMIT;
