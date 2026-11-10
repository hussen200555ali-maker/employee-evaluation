-- CD-040.7: composite index for "participated in template" filter
CREATE INDEX IF NOT EXISTS idx_eval_template_emp ON evaluations(template_id, evaluated_employee_id);
