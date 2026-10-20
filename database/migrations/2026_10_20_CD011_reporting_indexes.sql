-- CD-011: Performance indexes for reporting views (load-test remediation)
-- Migration: 2026_10_20_CD011_reporting_indexes.sql
-- Idempotent: CREATE INDEX IF NOT EXISTS (MariaDB 10.1+).
--
-- Motivation (from CD-011 load test, 50,050 evaluations / 500,370 answers):
--   Unfiltered aggregates over vw_evaluation_facts / vw_employee_scores were
--   ~2.9-5.1s. The dominant cost was the fan-out join on evaluation_answers
--   plus the status filter on evaluations. These indexes reduced filtered
--   report queries to < 1.3s and improved unfiltered paths substantially.
--   No behavioral change: indexes only.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 1) evaluations: filter by reporting status (views use IN ('approved','completed'))
CREATE INDEX IF NOT EXISTS idx_eval_status ON evaluations (status);

-- 2) evaluations: status + date (year/month filtered reports)
CREATE INDEX IF NOT EXISTS idx_eval_status_date ON evaluations (status, evaluation_date);

-- 3) evaluations: per-employee reporting joins (scorecard / employee facts)
CREATE INDEX IF NOT EXISTS idx_eval_emp_status ON evaluations (evaluated_employee_id, status);

-- 4) evaluations: date-only range filters
CREATE INDEX IF NOT EXISTS idx_eval_date ON evaluations (evaluation_date);

-- 5) evaluation_answers: covering index for fact aggregation (index-only scans)
CREATE INDEX IF NOT EXISTS idx_ea_eval_section ON evaluation_answers (evaluation_id, section_id, normalized_score);
