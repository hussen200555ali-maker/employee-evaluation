-- CD-023: Template Builder — descriptive evaluations support
-- Adds: textarea + overall_grade section types, grading_scales.template_id
-- Idempotent: uses IF NOT EXISTS where supported; MODIFY is safe to re-run.

-- 1) Extend criteria_sections.evaluation_type ENUM
--    'text' already exists in the enum (orphaned); we add 'textarea' (multi-line
--    descriptive answer) and 'overall_grade' (the manual overall grade picker).
--    Note: MariaDB ENUM MODIFY rewrites the column in-place; existing rows keep
--    their current values.
ALTER TABLE criteria_sections
  MODIFY COLUMN evaluation_type
  ENUM('percentage','numeric','grade','boolean','scale','text','textarea',
       'multiple_choice','formula','auto','helper','overall_grade')
  NOT NULL DEFAULT 'percentage';

-- 2) grading_scales: optional template linkage (for custom scales created
--    inside the builder). NULL = central/shared scale (default, backward-compatible).
ALTER TABLE grading_scales
  ADD COLUMN IF NOT EXISTS template_id INT DEFAULT NULL COMMENT 'CD-023: NULL=shared scale; non-null=template-specific custom scale';

-- Index for template-scoped lookups (IF NOT EXISTS keeps it idempotent)
-- Note: MariaDB 10.4 supports IF NOT EXISTS on ADD INDEX inside ALTER.
-- Using a standalone CREATE INDEX IF NOT EXISTS for portability.
CREATE INDEX IF NOT EXISTS idx_grading_scales_template ON grading_scales (template_id);

-- Verify:
-- SHOW COLUMNS FROM criteria_sections LIKE 'evaluation_type';
--   Expected: enum includes 'textarea' and 'overall_grade'
-- SHOW COLUMNS FROM grading_scales LIKE 'template_id';
--   Expected: one row, Default NULL
