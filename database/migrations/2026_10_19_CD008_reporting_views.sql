-- CD-008: SQL Views for Dynamic Reporting Engine (Data Mart)
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
DROP VIEW IF EXISTS vw_evaluation_facts;
CREATE VIEW vw_evaluation_facts AS
SELECT
  e.id AS evaluation_id,
  e.template_id,
  ft.template_name,
  e.evaluated_employee_id,
  emp.full_name AS evaluated_name,
  emp.emp_code AS evaluated_code,
  emp.department_id,
  d.name AS department_name,
  d.branch_id,
  b.name AS branch_name,
  e.evaluator_employee_id,
  COALESCE(e.evaluator_name, evlr.full_name) AS evaluator_name,
  e.evaluation_date,
  YEAR(e.evaluation_date) AS eval_year,
  MONTH(e.evaluation_date) AS eval_month,
  QUARTER(e.evaluation_date) AS eval_quarter,
  MONTHNAME(e.evaluation_date) AS eval_month_name_en,
  e.status,
  c.id AS criterion_id,
  c.criterion_name,
  cs.id AS section_id,
  cs.section_name,
  ea.normalized_score AS score,
  e.final_score AS total_score,
  e.final_score AS normalized_total
FROM evaluations e
LEFT JOIN form_templates ft ON ft.id = e.template_id
LEFT JOIN employees emp ON emp.id = e.evaluated_employee_id
LEFT JOIN departments d ON d.id = emp.department_id
LEFT JOIN branches b ON b.id = emp.branch_id
LEFT JOIN employees evlr ON evlr.id = e.evaluator_employee_id
INNER JOIN evaluation_answers ea ON ea.evaluation_id = e.id
LEFT JOIN criteria_sections cs ON cs.id = ea.section_id
LEFT JOIN criteria c ON c.id = cs.criterion_id
WHERE e.status IN ('submitted','completed');