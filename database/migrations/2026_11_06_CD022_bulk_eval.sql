-- CD-022: Bulk Evaluation — schema changes
-- Idempotent: uses IF NOT EXISTS / IF EXISTS

-- Add profile_status to employees
ALTER TABLE employees
  ADD COLUMN IF NOT EXISTS profile_status ENUM('complete','incomplete')
  NOT NULL DEFAULT 'complete'
  COMMENT 'CD-022: Quick-added employees are incomplete until profile is filled';

-- Make fields nullable for quick-add (idempotent — MODIFY is safe)
ALTER TABLE employees
  MODIFY COLUMN position_id INT DEFAULT NULL,
  MODIFY COLUMN hire_date DATE DEFAULT NULL,
  MODIFY COLUMN manager_id INT DEFAULT NULL;

-- Note: department_id and branch_id are already DEFAULT NULL in the schema.

-- Verify:
-- SHOW COLUMNS FROM employees LIKE 'profile_status';
-- Expected: one row
