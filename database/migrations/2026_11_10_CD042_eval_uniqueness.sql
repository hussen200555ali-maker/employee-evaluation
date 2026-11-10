-- CD-042: unique constraint to prevent duplicate evaluations
-- Run ONLY after cleanup (duplicate groups == 0).
-- MariaDB 10.4.32 supported.
ALTER TABLE evaluations
  ADD COLUMN context_key INT AS (IFNULL(context_id,0)) STORED,
  ADD UNIQUE KEY uq_eval_once
    (evaluated_employee_id, template_id, evaluation_date, context_key);
