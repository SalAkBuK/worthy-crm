-- Add price variants and details JSON to listings
-- If JSON is unsupported in your MySQL version, change details_json to LONGTEXT.
ALTER TABLE listings
  ADD COLUMN price_furnished_raw VARCHAR(64) NULL AFTER price_amount,
  ADD COLUMN price_unfurnished_raw VARCHAR(64) NULL AFTER price_furnished_raw,
  ADD COLUMN price_furnished_amount BIGINT NULL AFTER price_unfurnished_raw,
  ADD COLUMN price_unfurnished_amount BIGINT NULL AFTER price_furnished_amount,
  ADD COLUMN price_display_type ENUM('FURNISHED','UNFURNISHED','SINGLE') NOT NULL DEFAULT 'SINGLE' AFTER price_unfurnished_amount,
  ADD COLUMN price_display_label VARCHAR(32) NOT NULL DEFAULT 'Price' AFTER price_display_type,
  ADD COLUMN details_json JSON NULL AFTER notes;
