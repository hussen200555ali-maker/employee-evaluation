-- CD-022: Bulk Evaluation — schema changes
-- Idempotent: uses IF NOT EXISTS

-- Add profile_status to employees
ALTER TABLE employees
  ADD COLUMN IF NOT EXISTS profile_status ENUM('complete','incomplete')
  NOT NULL DEFAULT 'complete'
  COMMENT 'CD-022: Quick-added employees are incomplete until profile is filled';

-- Note: position_id, join_date (NOT hire_date), manager_id, department_id
-- and branch_id are ALREADY DEFAULT NULL in the base schema (setup/install_schema.sql).
-- The original draft referenced a non-existent `hire_date` column and was
-- redundant — removed. No further ALTER needed.

-- Verify:
-- SHOW COLUMNS FROM employees LIKE 'profile_status';
-- Expected: one row with Default = 'complete'
