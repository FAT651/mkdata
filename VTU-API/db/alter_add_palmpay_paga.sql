-- Migration: add palmpay and paga columns to subscribers
-- Safe add: only create columns if they don't already exist

SET @table_name = 'subscribers';

-- Add sPagaBank column if missing
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    CONCAT('ALTER TABLE ', @table_name, ' ADD COLUMN sPagaBank VARCHAR(50) DEFAULT NULL;'),
    'SELECT "sPagaBank exists"'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'sPagaBank'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add sPalmpayBank column if missing
SET @sql = (
  SELECT IF(
    COUNT(*) = 0,
    CONCAT('ALTER TABLE ', @table_name, ' ADD COLUMN sPalmpayBank VARCHAR(250) DEFAULT NULL;'),
    'SELECT "sPalmpayBank exists"'
  )
  FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = @table_name AND COLUMN_NAME = 'sPalmpayBank'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
