-- CD-020 / Sprint 1 — Drop legacy reporting objects
-- Idempotent: All statements use IF EXISTS.
-- Safe: Verified 0 references in app/ for all 6 objects.

DROP VIEW IF EXISTS vw_evaluation_facts;
DROP VIEW IF EXISTS vw_employee_scores;
DROP VIEW IF EXISTS vw_criteria_averages;

DROP TABLE IF EXISTS report_template_columns;
DROP TABLE IF EXISTS report_templates;
DROP TABLE IF EXISTS admin_schedules;

-- admin_notifications and admin_report_evaluators are KEPT (in use).
