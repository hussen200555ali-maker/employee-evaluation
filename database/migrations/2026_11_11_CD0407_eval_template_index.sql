-- CD-040.7: index to support "participated in template" filter on /evaluations
CREATE INDEX IF NOT EXISTS idx_eval_template_emp ON evaluations(template_id, evaluated_employee_id);
