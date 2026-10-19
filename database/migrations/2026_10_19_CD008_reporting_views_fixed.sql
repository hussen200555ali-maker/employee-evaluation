-- CD-008: SQL Views for Dynamic Reporting Engine (Data Mart)
-- Migration: 2026_10_19_CD008_reporting_views.sql
-- Idempotent: DROP IF EXISTS + CREATE VIEW

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 1) vw_evaluation_facts: Flat fact at section/answer level
DROP VIEW IF EXISTS `vw_evaluation_facts`;
CREATE VIEW `vw_evaluation_facts` AS
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
WHERE e.status IN ('approved', 'completed');

-- 2) vw_employee_scores: Final score per evaluation
DROP VIEW IF EXISTS `vw_employee_scores`;
CREATE VIEW `vw_employee_scores` AS
SELECT
  e.id AS evaluation_id,
  e.evaluated_employee_id,
  emp.full_name AS evaluated_name,
  emp.emp_code AS evaluated_code,
  emp.department_id,
  d.name AS department_name,
  d.branch_id,
  b.name AS branch_name,
  e.template_id,
  ft.template_name,
  e.evaluation_date,
  YEAR(e.evaluation_date) AS eval_year,
  MONTH(e.evaluation_date) AS eval_month,
  e.final_score AS total_score,
  e.final_score AS normalized_total,
  e.status
FROM evaluations e
LEFT JOIN form_templates ft ON ft.id = e.template_id
LEFT JOIN employees emp ON emp.id = e.evaluated_employee_id
LEFT JOIN departments d ON d.id = emp.department_id
LEFT JOIN branches b ON b.id = emp.branch_id
WHERE e.status IN ('approved', 'completed');

-- 3) vw_criteria_averages: Criterion averages by dimensions
DROP VIEW IF EXISTS `vw_criteria_averages`;
CREATE VIEW `vw_criteria_averages` AS
SELECT
  e.template_id,
  ft.template_name,
  c.id AS criterion_id,
  c.criterion_name,
  emp.department_id,
  d.name AS department_name,
  emp.branch_id,
  b.name AS branch_name,
  YEAR(e.evaluation_date) AS eval_year,
  MONTH(e.evaluation_date) AS eval_month,
  ROUND(AVG(ea.normalized_score), 4) AS avg_score,
  COUNT(DISTINCT e.id) AS evaluations_count,
  COUNT(ea.id) AS answers_count
FROM evaluations e
INNER JOIN evaluation_answers ea ON ea.evaluation_id = e.id
LEFT JOIN criteria_sections cs ON cs.id = ea.section_id
LEFT JOIN criteria c ON c.id = cs.criterion_id
LEFT JOIN form_templates ft ON ft.id = e.template_id
LEFT JOIN employees emp ON emp.id = e.evaluated_employee_id
LEFT JOIN departments d ON d.id = emp.department_id
LEFT JOIN branches b ON b.id = emp.branch_id
WHERE e.status IN ('approved', 'completed')
GROUP BY
  e.template_id,
  ft.template_name,
  c.id,
  c.criterion_name,
  emp.department_id,
  d.name,
  emp.branch_id,
  b.name,
  YEAR(e.evaluation_date),
  MONTH(e.evaluation_date);
