-- DIR-BULK-CREATE-FIX-V2.0 — quick-add name-only employees
-- Allow NULL emp_code while keeping the UNIQUE index and the column comment.
-- Multiple NULLs are allowed by MySQL/MariaDB UNIQUE indexes; real codes stay unique.
ALTER TABLE employees
  MODIFY emp_code VARCHAR(50) NULL COMMENT 'الرقم الوظيفي الفريد للموظف';
